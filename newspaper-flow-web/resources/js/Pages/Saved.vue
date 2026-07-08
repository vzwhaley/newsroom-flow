<script setup>
import AdSlot from '@/Components/AdSlot.vue';
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
            <h1 class="font-serif text-2xl font-bold text-ink">Saved to Read Later</h1>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <AdSlot slot="saved_top" format="horizontal" />

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
                <article v-for="a in articles" :key="a.id" class="group relative flex items-start justify-between gap-4 overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-brand-50/60 p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-300/40">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500 opacity-0 transition group-hover:opacity-100"></div>
                    <div class="min-w-0">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
                            <span v-if="a.topic_name" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                                <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                                {{ a.topic_name }}
                            </span>
                            <span v-if="a.source" class="text-gray-400">{{ a.source }}</span>
                        </div>
                        <h3 class="font-serif text-lg font-semibold leading-snug text-ink transition-colors group-hover:text-brand-700">{{ a.headline }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ a.description }}</p>
                        <a :href="a.url" target="_blank" rel="noopener noreferrer"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:from-brand-700 hover:to-indigo-700 hover:shadow-md">
                            Read More
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </a>
                    </div>
                    <button @click="unsave(a.id)" title="Remove" class="relative shrink-0 rounded-md p-2 text-gray-400 hover:bg-red-50 hover:text-red-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </article>
            </div>

            <div v-else class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <h3 class="font-serif text-xl font-semibold text-ink">Nothing Saved Yet</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    Tap the bookmark icon on any article in your feed to save it here for later.
                </p>
                <Link :href="route('dashboard')" class="mt-4 inline-block rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    Back to My Feed
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
