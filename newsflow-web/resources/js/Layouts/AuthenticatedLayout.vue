<script setup>
import { computed, ref } from 'vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import CookieConsent from '@/Components/CookieConsent.vue';
import SiteFooter from '@/Components/SiteFooter.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const page = usePage();
const user = computed(() => page.props.auth.user);
const isPro = computed(() => user.value?.is_pro);
const tierLabel = computed(() => {
    if (!user.value) return '';
    if (!user.value.is_pro) return 'Free';
    return {
        lifetime: 'Pro · Lifetime',
        yearly: 'Pro · Yearly',
        monthly: 'Pro · Monthly',
    }[user.value.tier] ?? 'Pro';
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <!-- Skip link for keyboard/screen-reader users (WCAG 2.4.1) -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
        >
            Skip to content
        </a>
        <nav class="sticky top-0 z-40 border-b border-gray-200 bg-white/80 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-24 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <BrandLogo href="/" />
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                :href="route('dashboard')"
                                :active="route().current('dashboard')"
                            >
                                My NewsroomFlow™
                            </NavLink>
                            <NavLink
                                :href="route('search')"
                                :active="route().current('search')"
                            >
                                Search
                            </NavLink>
                            <NavLink
                                :href="route('saved.index')"
                                :active="route().current('saved.index')"
                            >
                                Saved
                            </NavLink>
                            <NavLink
                                :href="route('archive')"
                                :active="route().current('archive')"
                            >
                                Archive
                            </NavLink>
                            <NavLink
                                :href="route('billing')"
                                :active="route().current('billing')"
                            >
                                Billing
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                        <!-- Plan badge -->
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="isPro ? 'bg-brand-50 text-brand-700' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ tierLabel }}
                        </span>
                        <Link
                            v-if="!isPro"
                            :href="route('billing')"
                            class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700"
                        >
                            Upgrade
                        </Link>

                        <div class="relative ms-1">
                            <Dropdown align="right" width="48">
                                <template #trigger="{ open }">
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            aria-haspopup="true"
                                            :aria-expanded="open"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                                        >
                                            {{ user.name }}
                                            <svg class="-me-0.5 ms-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('stats')">Reading Stats</DropdownLink>
                                    <DropdownLink :href="route('billing')">Billing</DropdownLink>
                                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                    <DropdownLink :href="route('how-to-use')">How to Use</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            aria-label="Toggle navigation menu"
                            aria-controls="mobile-menu"
                            :aria-expanded="showingNavigationDropdown"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                        >
                            <svg class="h-6 w-6" aria-hidden="true" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive menu -->
            <div id="mobile-menu" :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        My NewsroomFlow™
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('search')" :active="route().current('search')">
                        Search
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('saved.index')" :active="route().current('saved.index')">
                        Saved
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('archive')" :active="route().current('archive')">
                        Archive
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('billing')" :active="route().current('billing')">
                        Billing
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-gray-200 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">{{ user.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                        <span
                            class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="isPro ? 'bg-brand-50 text-brand-700' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ tierLabel }}
                        </span>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('stats')">Reading Stats</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main id="main-content" class="flex-1">
            <slot />
        </main>

        <SiteFooter />

        <CookieConsent />
    </div>
</template>
