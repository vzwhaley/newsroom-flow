<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    article: { type: Object, required: true },
    rank: { type: Number, default: null },
    topicName: { type: String, default: null },
    isPro: { type: Boolean, default: false },
    canSave: { type: Boolean, default: false }, // Pro
    isSaved: { type: Boolean, default: false },
    isRead: { type: Boolean, default: false },
});

const emit = defineEmits(['mark-read', 'toggle-read']);

const saving = ref(false);

const host = computed(() => {
    try {
        return new URL(props.article.url).hostname.replace(/^www\./, '');
    } catch {
        return props.article.source ?? '';
    }
});

const when = computed(() => {
    if (!props.article.published_at) return '';
    const d = new Date(props.article.published_at);
    const diffH = Math.round((Date.now() - d.getTime()) / 36e5);
    if (diffH < 1) return 'Just now';
    if (diffH < 24) return `${diffH}h ago`;
    return `${Math.round(diffH / 24)}d ago`;
});

function save() {
    if (saving.value || props.isSaved) return;
    saving.value = true;
    router.post(route('saved.store'), {
        headline: props.article.headline,
        description: props.article.description,
        url: props.article.url,
        source: props.article.source,
        image_url: props.article.image_url,
        topic_name: props.topicName,
    }, {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
}

function openArticle() {
    emit('mark-read', props.article.id); // marks read; the anchor still opens
}

// Branded share link — mint /s/{code}, then the native share sheet (or
// clipboard fallback). Free feature: every share is marketing.
const shareState = ref(null); // null | 'sharing' | 'shared' | 'copied'

async function share() {
    if (shareState.value === 'sharing') return;
    shareState.value = 'sharing';
    try {
        const { data } = await window.axios.post(route('articles.share', props.article.id));
        if (navigator.share) {
            await navigator.share({ title: props.article.headline, url: data.url });
            shareState.value = 'shared';
        } else {
            await navigator.clipboard.writeText(data.url);
            shareState.value = 'copied';
        }
    } catch {
        shareState.value = null;
        return;
    }
    setTimeout(() => (shareState.value = null), 2000);
}
</script>

<template>
    <article
        class="group relative flex h-full flex-col overflow-hidden rounded-2xl border bg-gradient-to-br from-white via-white to-brand-50/70 p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-300/40"
        :class="isRead ? 'border-gray-100 opacity-80' : 'border-gray-100'"
    >
        <!-- Accent bar revealed on hover -->
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500 opacity-0 transition duration-200 group-hover:opacity-100"></div>

        <div class="mb-2.5 flex items-center gap-2 text-xs text-gray-400">
            <span
                v-if="rank !== null"
                class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-indigo-600 text-[11px] font-bold text-white shadow-sm"
            >
                {{ rank }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                {{ article.source || host }}
            </span>
            <span v-if="when" class="text-gray-400">· {{ when }}</span>

            <span class="ml-auto flex items-center gap-1">
                <!-- Share (branded link) -->
                <button
                    @click="share"
                    :disabled="shareState === 'sharing'"
                    :title="shareState === 'copied' ? 'Link copied!' : 'Share this article'"
                    :aria-label="shareState === 'copied' ? 'Link copied to clipboard' : 'Share this article'"
                    class="rounded p-1 hover:bg-gray-100"
                    :class="shareState === 'copied' || shareState === 'shared' ? 'text-green-600' : 'text-gray-300 hover:text-gray-500'"
                >
                    <svg v-if="shareState === 'copied' || shareState === 'shared'" class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <svg v-else class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                </button>
                <span v-if="shareState === 'copied'" role="status" class="sr-only">Link copied to clipboard</span>

                <!-- Read / unread toggle -->
                <button
                    @click="emit('toggle-read', article.id)"
                    :title="isRead ? 'Mark as unread' : 'Mark as read'"
                    :aria-label="isRead ? 'Mark as unread' : 'Mark as read'"
                    :aria-pressed="isRead"
                    class="rounded p-1 hover:bg-gray-100"
                    :class="isRead ? 'text-green-600' : 'text-gray-300 hover:text-gray-500'"
                >
                    <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                <!-- Save / bookmark -->
                <button
                    v-if="canSave"
                    @click="save"
                    :disabled="saving || isSaved"
                    :title="isSaved ? 'Saved' : 'Save to read later'"
                    :aria-label="isSaved ? 'Saved' : 'Save to read later'"
                    :aria-pressed="isSaved"
                    class="rounded p-1 hover:bg-gray-100"
                    :class="isSaved ? 'text-brand-600' : 'text-gray-400 hover:text-brand-600'"
                >
                    <svg class="h-4 w-4" aria-hidden="true" :fill="isSaved ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </button>
                <Link
                    v-else
                    :href="route('billing')"
                    title="Saving articles is a Pro feature"
                    aria-label="Saving articles is a Pro feature — see billing"
                    class="rounded p-1 text-gray-300 hover:bg-gray-100 hover:text-gray-400"
                >
                    <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </Link>
            </span>
        </div>

        <h4 class="font-serif text-lg font-semibold leading-snug transition-colors" :class="isRead ? 'text-gray-400' : 'text-ink group-hover:text-brand-700'">
            {{ article.headline }}
        </h4>

        <p class="mt-2 line-clamp-3 flex-1 text-sm text-gray-600">
            {{ article.description }}
        </p>

        <div class="mt-4 flex items-center justify-between gap-2">
            <a
                :href="article.url"
                target="_blank"
                rel="noopener noreferrer"
                @click="openArticle"
                class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-500/20 transition duration-200 hover:from-brand-700 hover:to-indigo-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
            >
                Read More
                <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </article>
</template>
