import { ref } from 'vue';

/**
 * First-party cookie/ads consent state, shared across every component that
 * imports it (module-level ref → one source of truth, reactive everywhere).
 * Drives CookieConsent.vue and gates ad loading in AdSlot.vue.
 *
 * States:
 *   'unknown' — no choice yet. Ads do NOT load (AdSlot shows its placeholder);
 *               the banner is shown.
 *   'granted' — accepted. Personalized ads load.
 *   'denied'  — rejected non-essential. Ads still load but non-personalized.
 *
 * Pro users get no ad cookies at all, so the banner is gated on ad-eligibility
 * by the caller and never shown to them.
 */

const STORAGE_KEY = 'nf_cookie_consent_v1';

// 'unknown' | 'granted' | 'denied'
const consent = ref('unknown');
let initialized = false;

function readStored() {
    try {
        const v = window.localStorage.getItem(STORAGE_KEY);
        if (v === 'granted' || v === 'denied') return v;
    } catch {
        // localStorage unavailable (private mode etc.) — treat as unknown.
    }
    return 'unknown';
}

function persist(value) {
    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Best-effort; if we can't persist, the banner reappears next load.
    }
}

/**
 * Push the decision into Google's ad stack. Sets the AdSense non-personalized
 * flag and, if a gtag is present, updates Google Consent Mode v2 signals. Both
 * are no-ops when the ad scripts aren't loaded (e.g. local dev).
 */
function applyToAdStack(value) {
    if (typeof window === 'undefined') return;
    const granted = value === 'granted';

    window.adsbygoogle = window.adsbygoogle || [];
    // 1 = request non-personalized ads; 0 = personalized.
    window.adsbygoogle.requestNonPersonalizedAds = granted ? 0 : 1;

    if (typeof window.gtag === 'function') {
        window.gtag('consent', 'update', {
            ad_storage: granted ? 'granted' : 'denied',
            ad_user_data: granted ? 'granted' : 'denied',
            ad_personalization: granted ? 'granted' : 'denied',
            analytics_storage: 'denied',
        });
    }
}

export function useCookieConsent() {
    if (!initialized && typeof window !== 'undefined') {
        initialized = true;
        consent.value = readStored();
        if (consent.value !== 'unknown') applyToAdStack(consent.value);
    }

    function set(value) {
        consent.value = value;
        persist(value);
        applyToAdStack(value);
    }

    return {
        consent,
        accept: () => set('granted'),
        reject: () => set('denied'),
        /**
         * Re-open the consent UI. If Google's certified CMP is live (googlefc
         * present), ask it to re-show; otherwise re-open the first-party banner.
         */
        reopen: () => {
            if (typeof window === 'undefined') return;
            if (window.googlefc?.showRevocationMessage) {
                window.googlefc.showRevocationMessage();
            } else {
                window.dispatchEvent(new CustomEvent('nf:open-cookie-settings'));
            }
        },
    };
}
