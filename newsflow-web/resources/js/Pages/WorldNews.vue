<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    topic: { type: String, default: 'World News' },
    articles: { type: Array, default: () => [] },
});

function when(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (isNaN(d)) return '';
    const diffH = Math.round((Date.now() - d.getTime()) / 36e5);
    if (diffH < 1) return 'Just now';
    if (diffH < 24) return `${diffH}h ago`;
    return `${Math.round(diffH / 24)}d ago`;
}
</script>

<template>
    <SeoHead
        title="World News — A Live Feed"
        description="A live example of a NewsFlow topic feed — today's most popular World News headlines from a dozen publishers, refreshed daily. Build your own free."
        path="/world-news"
    />

    <PublicLayout>
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
            <!-- Topic header -->
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-indigo-700 p-6 text-white shadow-xl sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-inset ring-white/25">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                        </span>
                        <div>
                            <h1 class="font-serif text-3xl font-bold leading-tight">{{ topic }}</h1>
                            <p class="text-sm text-brand-100">A live example of a NewsFlow™ topic feed</p>
                        </div>
                    </div>
                    <span v-if="articles.length" class="hidden items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide ring-1 ring-inset ring-white/25 sm:inline-flex">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full motion-safe:animate-ping rounded-full bg-green-300 opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        </span>
                        Today · {{ articles.length }} Stories
                    </span>
                </div>
                <p class="mt-5 max-w-xl text-sm text-brand-50">
                    These are real, current headlines ranked by popularity — exactly what
                    you'd see for any topic you follow. Click any story to read the full
                    article at its source.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <Link :href="route('register')" class="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50">
                        Build Your Own Feed — Start Free
                    </Link>
                    <Link :href="route('pricing')" class="rounded-lg bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/30 transition hover:bg-white/20">
                        See Pro Plans
                    </Link>
                </div>
            </div>

            <AdSlot slot="world_news_top" format="horizontal" />

            <!-- Article list -->
            <div v-if="articles.length" class="mt-6 space-y-3">
                <article
                    v-for="(a, i) in articles"
                    :key="i"
                    class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-brand-50/70 p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-300/40"
                >
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500 opacity-0 transition group-hover:opacity-100"></div>
                    <div class="flex gap-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-indigo-600 text-sm font-bold text-white shadow-sm">
                            {{ i + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
                                <span v-if="a.source" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                                    {{ a.source }}
                                </span>
                                <span v-if="when(a.published_at)" class="text-gray-400">· {{ when(a.published_at) }}</span>
                            </div>
                            <a :href="a.url" target="_blank" rel="noopener noreferrer" class="font-serif text-lg font-semibold leading-snug text-ink transition-colors group-hover:text-brand-700">
                                {{ a.headline }}
                            </a>
                            <p v-if="a.description" class="mt-1 line-clamp-2 text-sm text-gray-600">{{ a.description }}</p>
                            <div class="mt-3 flex items-center gap-2">
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:from-brand-700 hover:to-indigo-700 hover:shadow-md">
                                    Read More
                                    <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </a>
                                <Link :href="route('register')" title="Saving and AI TL;DR summaries are Pro features — sign up free" class="inline-flex items-center gap-1 rounded-full border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-400 transition hover:border-brand-300 hover:text-brand-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                                    Save
                                </Link>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty / warming up -->
            <div v-else class="mt-6 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <h2 class="font-serif text-xl font-semibold text-ink">Live Headlines Are Warming Up</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    We're gathering today's most popular World News stories. Check back in a
                    moment — or sign up and add your own topics.
                </p>
                <Link :href="route('register')" class="mt-4 inline-block rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Start Free
                </Link>
            </div>

            <!-- Bottom CTA -->
            <div class="mt-8 rounded-2xl bg-gradient-to-br from-brand-50 to-indigo-50 p-6 text-center ring-1 ring-brand-100">
                <h2 class="font-serif text-xl font-bold text-ink">Like What You See?</h2>
                <p class="mx-auto mt-1.5 max-w-lg text-sm text-gray-600">
                    World News is just one topic. Follow your team, a company you watch, a
                    hobby — anything — and get a feed like this for each, every morning.
                </p>
                <Link :href="route('register')" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:from-brand-700 hover:to-indigo-700">
                    Build Your Own Newsroom — Free
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
