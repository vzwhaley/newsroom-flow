<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    code: { type: [String, Number], required: true },
    title: { type: String, required: true },
    message: { type: String, required: true },
});
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-6 text-center">
        <Head :title="`${code} — ${title}`" />

        <div class="mb-8 flex justify-center">
            <BrandLogo />
        </div>

        <p class="font-serif text-7xl font-bold text-brand-600">{{ code }}</p>
        <h1 class="mt-4 font-serif text-3xl font-bold text-ink">{{ title }}</h1>
        <p class="mt-3 max-w-md text-gray-600">{{ message }}</p>

        <div class="mt-8 flex gap-3">
            <Link href="/" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                Go Home
            </Link>
            <Link :href="route('dashboard')" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50">
                My NewsFlow™
            </Link>
        </div>
    </div>
</template>
