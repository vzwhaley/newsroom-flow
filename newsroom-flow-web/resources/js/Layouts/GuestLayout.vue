<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-8 sm:justify-center sm:pt-0"
    >
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
        >
            Skip to content
        </a>
        <BrandLogo />
        <p class="mt-3 font-serif text-lg font-semibold text-ink">Build Your Own Newsroom</p>

        <main
            id="main-content"
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </main>
    </div>
</template>
