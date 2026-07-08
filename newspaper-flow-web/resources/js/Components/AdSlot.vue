<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCookieConsent } from '@/composables/useCookieConsent';

/**
 * Google AdSense ad placement. Renders nothing for Pro users (whose Inertia
 * shared props omit the AdSense client + slot IDs entirely). For ad-eligible
 * visitors with the client configured, lazily injects the AdSense script and
 * emits a responsive banner. When unconfigured (local dev), renders a dashed
 * placeholder so layout review still works.
 *
 * Server is source of truth — the client + slot IDs are NOT present in the page
 * payload for Pro users, so a tampered client literally can't load anything.
 *
 * Mirrors the AdSlot pattern from the sibling apps (FileFlow / My Emergency
 * Screen), adapted to NewsroomFlow's brand.
 */

const props = defineProps({
    /** Slot key under config/adsense.php → slots[] (e.g. 'home_top'). */
    slot: { type: String, required: true },
    /** AdSense ad format. Default 'horizontal' = fixed 728 × 90 leaderboard
     *  (the standard placement across NewsroomFlow and the sibling sites). */
    format: { type: String, default: 'horizontal' },
});

const page = usePage();
const adsense = computed(() => page.props.adsense ?? { shows_ads: false, client: null, slots: {} });

const slotId = computed(() => adsense.value.slots?.[props.slot] ?? null);
const showsAds = computed(() => !!adsense.value.shows_ads);
const liveAdAvailable = computed(() => showsAds.value && !!adsense.value.client && !!slotId.value);

// Leaderboard = fixed 728 × 90 (centered, shrinks on narrow screens). Matches
// the sibling sites (FileFlow / AstroFlow / My Emergency Screen / Cardback Cantina).
const isLeaderboard = computed(() => props.format === 'horizontal');

const placeholder = computed(() => {
    switch (props.format) {
        case 'rectangle':
            return { size: 'Medium Rectangle · 300 × 250', cls: 'mx-auto h-[250px] w-[300px] max-w-full' };
        case 'vertical':
            return { size: 'Skyscraper · 160 × 600', cls: 'mx-auto h-[600px] w-[160px] max-w-full' };
        case 'horizontal':
            return { size: 'Leaderboard · 728 × 90', cls: 'mx-auto h-[90px] w-[728px] max-w-full' };
        default:
            return { size: 'Responsive Unit', cls: 'min-h-[120px]' };
    }
});

const mode = ref('hidden'); // 'hidden' | 'live' | 'placeholder'
const insEl = ref(null);

function ensureScript(client) {
    if (document.querySelector('script[src*="adsbygoogle.js"]')) return;
    const s = document.createElement('script');
    s.async = true;
    s.src = `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${client}`;
    s.crossOrigin = 'anonymous';
    s.setAttribute('data-newspaperflow-adsense', '');
    document.head.appendChild(s);
}

const { consent } = useCookieConsent();
const useGoogleCmp = computed(() => !!adsense.value.use_google_cmp);

async function activate() {
    if (!showsAds.value) {
        mode.value = 'hidden';
        return;
    }
    if (!liveAdAvailable.value) {
        mode.value = 'placeholder';
        return;
    }
    if (!useGoogleCmp.value && consent.value === 'unknown') {
        mode.value = 'placeholder';
        return;
    }
    if (mode.value === 'live') return;

    mode.value = 'live';
    ensureScript(adsense.value.client);
    await nextTick();
    window.adsbygoogle = window.adsbygoogle || [];
    try {
        window.adsbygoogle.push({});
    } catch {
        // No-op: the script will retry on load.
    }
}

onMounted(activate);
watch(consent, activate);
</script>

<template>
    <!-- Live AdSense ad -->
    <div v-if="mode === 'live'" class="my-6 text-center" role="complementary" aria-label="Advertisement">
        <p class="mb-1 text-center text-[10px] uppercase tracking-widest text-gray-400">Advertisement</p>
        <!-- Leaderboard = fixed 728×90; other formats stay responsive. -->
        <ins
            v-if="isLeaderboard"
            ref="insEl"
            class="adsbygoogle mx-auto"
            style="display: inline-block; width: 728px; height: 90px; max-width: 100%"
            :data-ad-client="adsense.client"
            :data-ad-slot="slotId"
        />
        <ins
            v-else
            ref="insEl"
            class="adsbygoogle block"
            style="display: block"
            :data-ad-client="adsense.client"
            :data-ad-slot="slotId"
            :data-ad-format="format"
            data-full-width-responsive="true"
        />
        <p class="mt-2 text-center text-xs text-gray-500">
            <a href="/pricing" class="text-link font-medium">
                Remove Ads — Upgrade To Pro
            </a>
        </p>
    </div>

    <!-- Placeholder — ad-eligible viewer, AdSense not configured yet (dev). -->
    <div v-else-if="mode === 'placeholder'" class="my-6" role="complementary" aria-label="Advertisement placeholder">
        <p class="mb-1 text-center text-[10px] uppercase tracking-widest text-gray-400">Advertisement</p>
        <div
            :class="placeholder.cls"
            class="relative grid place-items-center overflow-hidden rounded-lg border border-dashed border-brand-300 bg-gradient-to-br from-brand-50 via-white to-indigo-50 px-4 text-center"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 opacity-[0.06] [background-image:repeating-linear-gradient(45deg,#4f46e5_0,#4f46e5_1px,transparent_1px,transparent_10px)]"
            />
            <span class="absolute left-2 top-2 rounded bg-white/80 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-brand-500 ring-1 ring-inset ring-brand-200">
                Ad
            </span>
            <div class="relative">
                <p class="text-sm font-semibold text-brand-700">Your Ad Here</p>
                <p class="mt-0.5 text-xs text-brand-400">Sample placement · {{ placeholder.size }}</p>
            </div>
        </div>
        <p class="mt-2 text-center text-xs text-gray-500">
            <a href="/pricing" class="text-link font-medium">
                Remove Ads — Upgrade To Pro
            </a>
        </p>
    </div>

    <!-- mode === 'hidden' renders nothing (Pro) -->
</template>
