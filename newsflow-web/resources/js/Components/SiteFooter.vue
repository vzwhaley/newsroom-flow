<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Shared site footer — used on every page (public marketing pages AND the
// signed-in app) so the brand, links, and copyright are consistent everywhere.
const page = usePage();
const user = computed(() => page.props.auth?.user);
const year = new Date().getFullYear();

// Re-open the cookie/ads consent banner (CookieConsent.vue listens).
function openCookieSettings() {
    window.dispatchEvent(new CustomEvent('nf:open-cookie-settings'));
}
</script>

<template>
    <footer class="bg-ink text-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[1fr_2fr] lg:gap-24">
                <!-- Brand block -->
                <div>
                    <BrandLogo variant="dark" />
                    <p class="mt-4 max-w-md text-sm leading-6 text-brand-200">
                        Build your own newsroom. Follow only the topics you care
                        about and get the day’s most popular headlines on each,
                        every morning.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-brand-300">Available on</span>
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium text-white ring-1 ring-inset ring-white/15">Web</span>
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium text-brand-200 ring-1 ring-inset ring-white/15">iOS · soon</span>
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-medium text-brand-200 ring-1 ring-inset ring-white/15">Android · soon</span>
                    </div>
                </div>

                <!-- Link columns -->
                <div class="grid grid-cols-2 gap-x-8 gap-y-10 sm:grid-cols-3">
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Product</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><Link href="/pricing" class="text-brand-200 transition-colors hover:text-white">Pricing</Link></li>
                            <li><Link href="/how-to-use" class="text-brand-200 transition-colors hover:text-white">How It Works</Link></li>
                            <li>
                                <Link v-if="!user" href="/register" class="text-brand-200 transition-colors hover:text-white">Create Account</Link>
                                <Link v-else :href="route('dashboard')" class="text-brand-200 transition-colors hover:text-white">My NewsroomFlow™</Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Resources</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><Link href="/how-to-use" class="text-brand-200 transition-colors hover:text-white">How to Use</Link></li>
                            <li><Link href="/faq" class="text-brand-200 transition-colors hover:text-white">FAQ</Link></li>
                            <li><a href="mailto:vzwhaley4709@gmail.com" class="text-brand-200 transition-colors hover:text-white">Contact Support</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-white">Legal</h3>
                        <ul class="mt-4 space-y-3 text-sm">
                            <li><Link href="/about" class="text-brand-200 transition-colors hover:text-white">About</Link></li>
                            <li><Link href="/privacy" class="text-brand-200 transition-colors hover:text-white">Privacy</Link></li>
                            <li><Link href="/terms" class="text-brand-200 transition-colors hover:text-white">Terms</Link></li>
                            <li><button type="button" @click="openCookieSettings" class="text-brand-200 transition-colors hover:text-white">Cookie Preferences</button></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="mt-14 flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-brand-300">
                    © {{ year }}
                    <a href="https://moonwhale.media" target="_blank" rel="noopener noreferrer" class="font-medium text-white transition-colors hover:text-brand-200">Moon Whale Media, LLC</a>. All rights reserved.
                </p>
                <p class="flex items-center gap-1.5 text-xs text-brand-400">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400" aria-hidden="true" />
                    Your own customized news topics, every day
                </p>
            </div>
        </div>
    </footer>
</template>
