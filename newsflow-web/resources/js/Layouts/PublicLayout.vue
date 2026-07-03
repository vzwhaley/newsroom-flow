<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import CookieConsent from '@/Components/CookieConsent.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const year = new Date().getFullYear();
const mobileOpen = ref(false);

const navLinks = [
    { href: '/how-to-use', label: 'How It Works' },
    { href: '/faq', label: 'FAQ' },
    { href: '/pricing', label: 'Pricing' },
    { href: '/about', label: 'About' },
];

function isActive(href) {
    try {
        return new URL(page.url, 'http://x').pathname === href;
    } catch {
        return false;
    }
}

// Re-open the cookie/ads consent banner (CookieConsent.vue listens).
function openCookieSettings() {
    window.dispatchEvent(new CustomEvent('nf:open-cookie-settings'));
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-ink">
        <!-- Skip link for keyboard/screen-reader users (WCAG 2.4.1) -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
        >
            Skip to content
        </a>
        <!-- Sticky nav -->
        <header class="sticky top-0 z-40 border-b border-gray-100 bg-white/80 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-24 items-center justify-between">
                    <BrandLogo />

                    <!-- Desktop nav -->
                    <nav class="hidden items-center gap-8 md:flex" aria-label="Primary">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            :class="[
                                'text-sm font-semibold transition',
                                isActive(link.href) ? 'text-brand-600' : 'text-gray-600 hover:text-ink',
                            ]"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>

                    <!-- Auth CTAs -->
                    <div class="hidden items-center gap-3 md:flex">
                        <template v-if="user">
                            <Link :href="route('dashboard')" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                                My NewsFlow
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-medium text-gray-600 hover:text-ink">Log In</Link>
                            <Link :href="route('register')" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                                Get Started
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile toggle -->
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 md:hidden"
                        @click="mobileOpen = !mobileOpen"
                        :aria-expanded="mobileOpen"
                        aria-label="Toggle navigation"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <nav v-show="mobileOpen" class="border-t border-gray-100 md:hidden" aria-label="Primary (mobile)">
                <div class="space-y-1 px-4 py-3">
                    <Link v-for="link in navLinks" :key="link.href" :href="link.href" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ link.label }}
                    </Link>
                    <div class="mt-2 border-t border-gray-100 pt-2">
                        <Link v-if="user" :href="route('dashboard')" class="block rounded-md bg-brand-600 px-3 py-2 text-center text-sm font-semibold text-white">My NewsFlow</Link>
                        <template v-else>
                            <Link :href="route('login')" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Log In</Link>
                            <Link :href="route('register')" class="mt-1 block rounded-md bg-brand-600 px-3 py-2 text-center text-sm font-semibold text-white">Get Started</Link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Page content -->
        <main id="main-content" class="flex-1">
            <slot />
        </main>

        <!-- Site-wide marketing ad (non-Pro visitors only) -->
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <AdSlot slot="marketing" format="horizontal" />
        </div>

        <!-- Footer -->
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
                                    <Link v-else :href="route('dashboard')" class="text-brand-200 transition-colors hover:text-white">My NewsFlow</Link>
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

        <CookieConsent />
    </div>
</template>
