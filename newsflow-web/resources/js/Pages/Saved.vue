<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    articles: { type: Array, default: () => [] },
});

const page = usePage();
const isPro = computed(() => page.props.auth.user.is_pro);

function unsave(id) {
    router.delete(route('saved.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Saved articles" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="font-serif text-2xl font-bold text-ink">Saved to read later</h1>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Pro upsell for free users -->
            <div v-if="!isPro" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-brand-50 px-4 py-3">
                <p class="text-sm text-brand-800">
                    Saving articles is a <strong>Pro feature</strong>. Upgrade to bookmark stories and read them any time.
                </p>
                <Link :href="route('billing')" class="rounded-md bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">
                    Upgrade to Pro
                </Link>
            </div>

            <div v-if="articles.length" class="space-y-3">
                <article v-for="a in articles" :key="a.id" class="flex items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4">
                    <div class="min-w-0">
                        <div class="mb-1 flex items-center gap-2 text-xs text-gray-400">
                            <span v-if="a.topic_name" class="rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500">{{ a.topic_name }}</span>
                            <span v-if="a.source">{{ a.source }}</span>
                        </div>
                        <h3 class="font-serif text-lg font-semibold leading-snug text-ink">{{ a.headline }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ a.description }}</p>
                        <a :href="a.url" target="_blank" rel="noopener noreferrer"
                            class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                            Read more
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </a>
                    </div>
                    <button @click="unsave(a.id)" title="Remove" class="shrink-0 rounded-md p-2 text-gray-400 hover:bg-red-50 hover:text-red-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </article>
            </div>

            <div v-else class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <h3 class="font-serif text-xl font-semibold text-ink">Nothing saved yet</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    Tap the bookmark icon on any article in your feed to save it here for later.
                </p>
                <Link :href="route('dashboard')" class="mt-4 inline-block rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Back to my feed
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
