<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-8 sm:justify-center sm:pt-0"
    >
        <BrandLogo :icon-class="'h-12 w-auto'" />

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
