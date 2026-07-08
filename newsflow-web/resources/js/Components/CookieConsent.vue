<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useCookieConsent } from '@/composables/useCookieConsent';

/**
 * Cookie / ads consent banner. Shown to non-Pro visitors (the only people who
 * get ad cookies) until they choose, and re-openable via the footer "Cookie
 * Preferences" link (which dispatches `nf:open-cookie-settings`).
 *
 * Accept → personalized ads. Reject → non-essential off, ads still show but
 * non-personalized. Pro users never see this (no ad cookies).
 */

const page = usePage();
const adEligible = computed(() => !!page.props.adsense?.shows_ads);
const useGoogleCmp = computed(() => !!page.props.adsense?.use_google_cmp);

const { consent, accept, reject } = useCookieConsent();

const forceOpen = ref(false);

const visible = computed(
    () => !useGoogleCmp.value && adEligible.value && (consent.value === 'unknown' || forceOpen.value),
);

function onAccept() {
    accept();
    forceOpen.value = false;
}
function onReject() {
    reject();
    forceOpen.value = false;
}
function openSettings() {
    forceOpen.value = true;
}

onMounted(() => window.addEventListener('nf:open-cookie-settings', openSettings));
onBeforeUnmount(() => window.removeEventListener('nf:open-cookie-settings', openSettings));
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed inset-x-0 bottom-0 z-50 px-3 pb-3 sm:px-4 sm:pb-4"
            role="region"
            aria-label="Cookie consent"
        >
            <div class="mx-auto max-w-4xl rounded-2xl border border-gray-200 bg-white/95 p-5 shadow-2xl ring-1 ring-black/5 backdrop-blur sm:p-6">
                <div class="sm:flex sm:items-start sm:gap-5">
                    <div class="flex-1 text-sm leading-6 text-gray-700">
                        <p class="text-base font-semibold text-gray-900">Cookies On NewsroomFlow™</p>
                        <p class="mt-1">
                            We use cookies and local storage. Some are essential — they keep you
                            signed in and remember your choices. With your consent, the free plan
                            also uses advertising cookies to support the app. See our
                            <Link href="/privacy" class="text-link font-medium">Privacy Policy</Link>.
                        </p>
                    </div>

                    <div class="mt-4 flex shrink-0 flex-col gap-2 sm:mt-0 sm:w-56">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                            @click="onAccept"
                        >
                            Accept All
                        </button>
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                            @click="onReject"
                        >
                            Necessary Only
                        </button>
                        <Link href="/pricing" class="text-link text-center text-xs font-medium">
                            Or Go Ad-Free With Pro
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
