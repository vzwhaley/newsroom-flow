<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    locked: { type: Boolean, default: false },
    q: { type: String, default: '' },
    articles: { type: Object, default: null },
});

const query = ref(props.q);

function search() {
    router.get(route('archive'), { q: query.value }, { preserveState: true, preserveScroll: true });
}

function when(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

// Laravel paginator labels arrive as HTML entities ("&laquo; Previous").
// Render plain, screen-reader-friendly text instead of v-html.
function pageLabel(label) {
    return label
        .replace(/&laquo;\s*/g, '')
        .replace(/\s*&raquo;/g, '')
        .replace(/&hellip;/g, '…');
}
</script>

<template>
    <Head title="Archive" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="font-serif text-2xl font-bold text-ink">Archive</h1>
        </template>

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <AdSlot slot="archive_top" format="horizontal" />

            <!-- Pro upsell -->
            <div v-if="locked" class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                <h2 class="font-serif text-xl font-semibold text-ink">Archive Is a Pro Feature</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    With Pro, stories that rotate out of your feeds are kept here so you can always catch up on what you missed.
                </p>
                <Link :href="route('billing')" class="mt-4 inline-block rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Upgrade to Pro
                </Link>
            </div>

            <template v-else>
                <p class="text-sm text-gray-500">
                    Stories that have rotated out of your feeds. We keep your history here so nothing is lost.
                </p>

                <form @submit.prevent="search" class="mt-4 flex gap-2">
                    <label for="archive-search" class="sr-only">Search your archive</label>
                    <input id="archive-search" v-model="query" type="search" placeholder="Search your archive…"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" />
                    <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Search</button>
                </form>

                <div v-if="articles && articles.data.length" class="mt-6">
                    <ul class="space-y-3">
                        <li
                            v-for="a in articles.data"
                            :key="a.id"
                            class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-brand-50/60 p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-300/40"
                        >
                            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500 opacity-0 transition group-hover:opacity-100"></div>
                            <div class="mb-1.5 flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                                    {{ a.topic_name }}
                                </span>
                                <span v-if="a.source" class="text-gray-400">{{ a.source }}</span>
                                <span class="text-gray-400">· Archived {{ when(a.archived_at) }}</span>
                            </div>
                            <a :href="a.url" target="_blank" rel="noopener noreferrer" class="font-serif text-base font-semibold leading-snug text-ink transition-colors group-hover:text-brand-700">{{ a.headline }}</a>
                            <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ a.description }}</p>
                            <a :href="a.url" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition hover:from-brand-700 hover:to-indigo-700">
                                Read More
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </a>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <nav v-if="articles.links.length > 3" aria-label="Archive pagination" class="mt-6 flex flex-wrap justify-center gap-1">
                        <template v-for="(link, i) in articles.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-md px-3 py-1.5 text-sm"
                                :class="link.active ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                :aria-current="link.active ? 'page' : undefined"
                                preserve-scroll
                            >{{ pageLabel(link.label) }}</Link>
                            <span v-else class="rounded-md px-3 py-1.5 text-sm text-gray-300">{{ pageLabel(link.label) }}</span>
                        </template>
                    </nav>
                </div>

                <div v-else class="mt-6 rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                    <h3 class="font-serif text-lg font-semibold text-ink">{{ q ? 'No Matches in Your Archive' : 'Your Archive Is Empty' }}</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                        {{ q ? 'Try a different search.' : 'As your feeds refresh each day, older stories will collect here automatically.' }}
                    </p>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
