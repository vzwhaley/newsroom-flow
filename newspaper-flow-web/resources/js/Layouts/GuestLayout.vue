<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-8 sm:justify-center sm:pt-0"
    >
        <BrandLogo />
        <p class="mt-3 font-serif text-lg font-semibold text-ink">Build Your Own Newsroom</p>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
