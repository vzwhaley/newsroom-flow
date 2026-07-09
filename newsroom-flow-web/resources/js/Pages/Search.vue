<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    q: { type: String, default: '' },
    locked: { type: Boolean, default: false },
    feed: { type: Array, default: () => [] },
    saved: { type: Array, default: () => [] },
});

const query = ref(props.q);

function search() {
    router.get(route('search'), { q: query.value }, { preserveState: true, preserveScroll: true });
}

const hasResults = () => props.feed.length || props.saved.length;
</script>

<template>
    <Head title="Search" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="font-serif text-2xl font-bold text-ink">Search</h1>
        </template>

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <AdSlot slot="search_top" format="horizontal" />

            <!-- Pro upsell -->
            <div v-if="locked" class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <h2 class="font-serif text-xl font-semibold text-ink">Search Is a Pro Feature</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    Upgrade to search across all your topic feeds and saved articles at once.
                </p>
                <Link :href="route('billing')" class="mt-4 inline-block rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Upgrade to Pro
                </Link>
            </div>

            <template v-else>
                <form @submit.prevent="search" class="flex gap-2">
                    <label for="feed-search" class="sr-only">Search your feeds and saved articles</label>
                    <input
                        id="feed-search"
                        v-model="query"
                        type="search"
                        autofocus
                        placeholder="Search your feeds and saved articles…"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Search</button>
                </form>

                <!-- Results -->
                <div v-if="q" class="mt-8">
                    <p v-if="!hasResults()" class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-500">
                        No matches for “{{ q }}”.
                    </p>

                    <section v-if="feed.length" class="mb-8">
                        <h2 class="mb-3 font-serif text-lg font-bold text-ink">In Your Feeds <span class="text-sm font-normal text-gray-400">({{ feed.length }})</span></h2>
                        <ul class="space-y-3">
                            <li
                                v-for="a in feed"
                                :key="'f'+a.id"
                                class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-brand-50/60 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-300/40"
                            >
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500 opacity-0 transition group-hover:opacity-100"></div>
                                <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                                        {{ a.topic_name }}
                                    </span>
                                    <span v-if="a.source" class="text-gray-400">{{ a.source }}</span>
                                    <span v-if="a.is_read" class="font-medium text-green-600">· Read</span>
                                </div>
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" class="font-serif text-base font-semibold leading-snug text-ink transition-colors group-hover:text-brand-700">{{ a.headline }}</a>
                                <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ a.description }}</p>
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:from-brand-700 hover:to-indigo-700">
                                    Read More
                                    <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </a>
                            </li>
                        </ul>
                    </section>

                    <section v-if="saved.length">
                        <h2 class="mb-3 font-serif text-lg font-bold text-ink">In Your Saved <span class="text-sm font-normal text-gray-400">({{ saved.length }})</span></h2>
                        <ul class="space-y-3">
                            <li
                                v-for="a in saved"
                                :key="'s'+a.id"
                                class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-brand-50/60 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-300/40"
                            >
                                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500 opacity-0 transition group-hover:opacity-100"></div>
                                <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
                                    <span v-if="a.topic_name" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                                        {{ a.topic_name }}
                                    </span>
                                    <span v-if="a.source" class="text-gray-400">{{ a.source }}</span>
                                </div>
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" class="font-serif text-base font-semibold leading-snug text-ink transition-colors group-hover:text-brand-700">{{ a.headline }}</a>
                                <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ a.description }}</p>
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:from-brand-700 hover:to-indigo-700">
                                    Read More
                                    <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </a>
                            </li>
                        </ul>
                    </section>
                </div>

                <p v-else class="mt-8 text-center text-sm text-gray-400">
                    Type a word or phrase to search across every topic you follow and everything you’ve saved.
                </p>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
