<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-ink">
        <!-- Top nav -->
        <header class="border-b border-gray-200">
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <Link href="/" class="flex items-center">
                    <ApplicationLogo with-wordmark />
                </Link>

                <nav class="flex items-center gap-1 sm:gap-3">
                    <Link
                        :href="route('how-to-use')"
                        class="hidden rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-ink sm:block"
                    >
                        How it works
                    </Link>
                    <Link
                        :href="route('faq')"
                        class="hidden rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-ink sm:block"
                    >
                        FAQ
                    </Link>
                    <Link
                        :href="route('pricing')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-ink"
                    >
                        Pricing
                    </Link>

                    <template v-if="user">
                        <Link
                            :href="route('dashboard')"
                            class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                        >
                            My NewsFlow
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-ink"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700"
                        >
                            Get started
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-gray-50">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-2">
                    <ApplicationLogo with-wordmark />
                </div>
                <p class="text-sm text-gray-500">
                    &copy; {{ year }} NewsFlow — a Moon Whale Media product. Build your own newsroom.
                </p>
                <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-500">
                    <Link :href="route('how-to-use')" class="hover:text-ink">How it works</Link>
                    <Link :href="route('faq')" class="hover:text-ink">FAQ</Link>
                    <Link :href="route('pricing')" class="hover:text-ink">Pricing</Link>
                    <Link :href="route('about')" class="hover:text-ink">About</Link>
                    <Link :href="route('privacy')" class="hover:text-ink">Privacy</Link>
                    <Link :href="route('terms')" class="hover:text-ink">Terms</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
