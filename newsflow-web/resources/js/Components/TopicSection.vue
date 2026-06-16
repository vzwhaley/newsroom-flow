<script setup>
import ArticleCard from '@/Components/ArticleCard.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    topic: { type: Object, required: true },
    groupName: { type: String, default: null },
    isPro: { type: Boolean, default: false },
    savedFingerprints: { type: Array, default: () => [] },
    canMoveUp: { type: Boolean, default: false },
    canMoveDown: { type: Boolean, default: false },
});

const emit = defineEmits(['move']);

const refreshing = ref(false);
const removing = ref(false);
const showMutes = ref(false);
const newWord = ref('');
const keywords = ref([...(props.topic.mute_keywords ?? [])]);

const savedSet = computed(() => new Set(props.savedFingerprints));

const lastRefreshed = computed(() => {
    if (!props.topic.last_refreshed_at) return 'Not yet refreshed';
    const d = new Date(props.topic.last_refreshed_at);
    return 'Updated ' + d.toLocaleString(undefined, {
        month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
    });
});

function refresh() {
    refreshing.value = true;
    router.post(route('topics.refresh', props.topic.id), {}, {
        preserveScroll: true,
        onFinish: () => (refreshing.value = false),
    });
}

function remove() {
    if (!confirm(`Stop following "${props.topic.name}"? This removes its feed.`)) return;
    removing.value = true;
    router.delete(route('topics.destroy', props.topic.id), {
        preserveScroll: true,
        onFinish: () => (removing.value = false),
    });
}

function addWord() {
    const w = newWord.value.trim().toLowerCase();
    if (w && !keywords.value.includes(w)) keywords.value.push(w);
    newWord.value = '';
}
function removeWord(w) {
    keywords.value = keywords.value.filter((k) => k !== w);
}
function saveMutes() {
    router.patch(route('topics.mutes', props.topic.id), { mute_keywords: keywords.value }, {
        preserveScroll: true,
        onSuccess: () => (showMutes.value = false),
    });
}
</script>

<template>
    <section class="mb-12">
        <!-- Topic masthead -->
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2 border-b-2 border-ink pb-2">
            <div>
                <p v-if="groupName" class="text-xs font-semibold uppercase tracking-wide text-brand-600">
                    {{ groupName }}
                </p>
                <h2 class="font-serif text-2xl font-bold tracking-tight text-ink">
                    {{ topic.name }}
                </h2>
                <p class="text-xs text-gray-400">{{ lastRefreshed }}</p>
            </div>
            <div class="flex items-center gap-1">
                <!-- Reorder -->
                <button v-if="canMoveUp" @click="emit('move', { id: topic.id, dir: -1 })" title="Move up"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-ink">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" /></svg>
                </button>
                <button v-if="canMoveDown" @click="emit('move', { id: topic.id, dir: 1 })" title="Move down"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-ink">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>

                <!-- Mute keywords (Pro) -->
                <button v-if="isPro" @click="showMutes = !showMutes" title="Mute keywords"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /><path stroke-linecap="round" stroke-linejoin="round" d="M17 9l4 4m0-4l-4 4" /></svg>
                    <span v-if="keywords.length" class="rounded-full bg-brand-100 px-1.5 text-brand-700">{{ keywords.length }}</span>
                </button>

                <button @click="refresh" :disabled="refreshing"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50">
                    <svg class="h-4 w-4" :class="{ 'animate-spin': refreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    {{ refreshing ? 'Refreshing…' : 'Refresh' }}
                </button>
                <button @click="remove" :disabled="removing" title="Stop following"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <!-- Mute editor -->
        <div v-if="showMutes" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-sm font-medium text-ink">Mute keywords for “{{ topic.name }}”</p>
            <p class="text-xs text-gray-500">Articles mentioning these words are hidden from this topic.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <span v-for="w in keywords" :key="w" class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs text-gray-700 ring-1 ring-gray-200">
                    {{ w }}
                    <button @click="removeWord(w)" class="text-gray-400 hover:text-red-600">×</button>
                </span>
                <span v-if="!keywords.length" class="text-xs text-gray-400">No muted words yet.</span>
            </div>
            <div class="mt-3 flex gap-2">
                <input v-model="newWord" @keydown.enter.prevent="addWord" type="text" placeholder="e.g. injury"
                    class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500" />
                <button @click="addWord" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">Add</button>
                <button @click="saveMutes" class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">Save</button>
            </div>
        </div>

        <!-- Articles grid -->
        <div v-if="topic.articles.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <ArticleCard
                v-for="(a, i) in topic.articles"
                :key="a.id"
                :article="a"
                :rank="i + 1"
                :topic-name="topic.name"
                :can-save="isPro"
                :is-saved="savedSet.has(a.fingerprint)"
            />
        </div>
        <p v-else class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-500">
            No articles yet — hit Refresh to pull the latest stories.
        </p>
    </section>
</template>
