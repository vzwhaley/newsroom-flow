<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const providers = computed(() => page.props.socialProviders ?? {});
const anyEnabled = computed(() => Object.values(providers.value).some(Boolean));

// Social redirects are full-page navigations (they leave the SPA), so use
// plain anchors rather than Inertia <Link>.
function url(provider) {
    return route('social.redirect', provider);
}
</script>

<template>
    <div v-if="anyEnabled" class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white px-2 text-gray-400">or continue with</span>
            </div>
        </div>

        <div class="mt-4 grid gap-2">
            <a
                v-if="providers.google"
                :href="url('google')"
                class="inline-flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.65l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.11a6.6 6.6 0 0 1 0-4.22V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.05l3.66 2.84C6.71 7.3 9.14 5.38 12 5.38z"/>
                </svg>
                Google
            </a>

            <a
                v-if="providers.apple"
                :href="url('apple')"
                class="inline-flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-900"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M16.36 12.78c.02 2.5 2.19 3.33 2.21 3.34-.02.06-.35 1.2-1.15 2.37-.69 1.02-1.41 2.03-2.54 2.05-1.11.02-1.47-.66-2.74-.66s-1.66.64-2.71.68c-1.09.04-1.92-1.1-2.62-2.11-1.42-2.07-2.51-5.85-1.05-8.4.72-1.27 2.02-2.07 3.42-2.09 1.07-.02 2.08.72 2.74.72.65 0 1.88-.89 3.17-.76.54.02 2.06.22 3.03 1.64-.08.05-1.81 1.06-1.79 3.16M14.3 4.6c.58-.7.97-1.68.86-2.66-.84.03-1.85.56-2.45 1.26-.53.62-1 1.61-.88 2.57.94.07 1.89-.48 2.47-1.17"/>
                </svg>
                Apple
            </a>

            <a
                v-if="providers.discord"
                :href="url('discord')"
                class="inline-flex items-center justify-center gap-2 rounded-md border border-transparent bg-[#5865F2] px-4 py-2 text-sm font-medium text-white hover:opacity-90"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M20.32 4.37A19.8 19.8 0 0 0 15.43 3c-.2.36-.43.85-.6 1.24a18.3 18.3 0 0 0-5.66 0C9 3.85 8.76 3.36 8.56 3a19.7 19.7 0 0 0-4.9 1.37C.55 9.06-.3 13.64.13 18.15a19.9 19.9 0 0 0 6 3.04c.49-.66.92-1.36 1.29-2.1-.71-.27-1.39-.6-2.04-.99.17-.13.34-.26.5-.4a14.2 14.2 0 0 0 12.23 0c.16.14.33.27.5.4-.65.39-1.33.72-2.04.99.37.74.8 1.44 1.29 2.1a19.9 19.9 0 0 0 6-3.04c.5-5.23-.84-9.77-3.55-13.78M8.02 15.33c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42c1.2 0 2.17 1.09 2.15 2.42 0 1.33-.95 2.42-2.15 2.42m7.96 0c-1.18 0-2.15-1.09-2.15-2.42s.95-2.42 2.15-2.42c1.2 0 2.17 1.09 2.15 2.42 0 1.33-.95 2.42-2.15 2.42"/>
                </svg>
                Discord
            </a>
        </div>
    </div>
</template>
