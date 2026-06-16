<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    article: { type: Object, required: true },
    rank: { type: Number, default: null },
    topicName: { type: String, default: null },
    canSave: { type: Boolean, default: false }, // Pro
    isSaved: { type: Boolean, default: false },
});

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
    const diffD = Math.round(diffH / 24);
    return `${diffD}d ago`;
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
</script>

<template>
    <article
        class="group flex h-full flex-col rounded-xl border border-gray-200 bg-white p-4 transition hover:border-brand-300 hover:shadow-md"
    >
        <div class="mb-2 flex items-center gap-2 text-xs text-gray-400">
            <span
                v-if="rank !== null"
                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-[11px] font-semibold text-gray-500"
            >
                {{ rank }}
            </span>
            <span class="font-medium text-gray-500">{{ article.source || host }}</span>
            <span v-if="when">· {{ when }}</span>

            <!-- Save / bookmark -->
            <span class="ml-auto">
                <button
                    v-if="canSave"
                    @click="save"
                    :disabled="saving || isSaved"
                    :title="isSaved ? 'Saved' : 'Save to read later'"
                    class="rounded p-1 hover:bg-gray-100"
                    :class="isSaved ? 'text-brand-600' : 'text-gray-400 hover:text-brand-600'"
                >
                    <svg class="h-4 w-4" :fill="isSaved ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </button>
                <Link
                    v-else
                    :href="route('billing')"
                    title="Saving articles is a Pro feature"
                    class="rounded p-1 text-gray-300 hover:bg-gray-100 hover:text-gray-400"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </Link>
            </span>
        </div>

        <h4 class="font-serif text-lg font-semibold leading-snug text-ink">
            {{ article.headline }}
        </h4>

        <p class="mt-2 line-clamp-3 flex-1 text-sm text-gray-600">
            {{ article.description }}
        </p>

        <a
            :href="article.url"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700"
        >
            Read more
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>
    </article>
</template>
