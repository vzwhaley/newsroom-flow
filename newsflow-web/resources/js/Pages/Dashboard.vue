<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TopicSection from '@/Components/TopicSection.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    topics: { type: Array, default: () => [] },
    savedFingerprints: { type: Array, default: () => [] },
    watchlist: { type: Array, default: () => [] },
    watchKeywords: { type: Array, default: () => [] },
});

function openWatch(id) {
    window.axios.post(route('articles.read', id)).catch(() => {});
}

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});

// Which topic is being viewed: 'all' or a topic id.
const selected = ref('all');
const nameInput = ref(null);

// Show only unread articles across the feed.
const unreadOnly = ref(false);

const atLimit = computed(() =>
    user.value.topic_limit !== null && user.value.topic_count >= user.value.topic_limit
);

const limitLabel = computed(() =>
    user.value.topic_limit === null
        ? 'Unlimited topics'
        : `${user.value.topic_count} of ${user.value.topic_limit} topics used`
);

// Flatten the tree into the list of feed sections to render, honoring the
// current selection. Each entry carries its sibling-reorder affordances.
const renderList = computed(() => {
    const out = [];
    const tops = props.topics;

    const found = selected.value !== 'all' && flatIds().includes(selected.value);

    if (found) {
        for (const t of tops) {
            if (t.id === selected.value) {
                out.push({ topic: t, groupName: null, canUp: false, canDown: false });
                return out;
            }
            const c = (t.children || []).find((c) => c.id === selected.value);
            if (c) {
                out.push({ topic: c, groupName: t.name, canUp: false, canDown: false });
                return out;
            }
        }
    }

    // 'all' (or selection no longer exists) → everything, in tree order.
    tops.forEach((t, i) => {
        out.push({ topic: t, groupName: null, canUp: i > 0, canDown: i < tops.length - 1 });
        const kids = t.children || [];
        kids.forEach((c, j) => {
            out.push({ topic: c, groupName: t.name, canUp: j > 0, canDown: j < kids.length - 1 });
        });
    });
    return out;
});

function flatIds() {
    return props.topics.flatMap((t) => [t.id, ...(t.children || []).map((c) => c.id)]);
}

function select(id) {
    selected.value = id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Expand/collapse of parent categories in the sidebar (expanded by default).
const collapsed = ref({});
function toggle(id) {
    collapsed.value = { ...collapsed.value, [id]: !collapsed.value[id] };
}
function isCollapsed(id) {
    return !!collapsed.value[id];
}

// Sibling-aware reorder.
function move({ id, dir }) {
    let siblings = props.topics;
    if (!props.topics.find((t) => t.id === id)) {
        const parent = props.topics.find((t) => (t.children || []).some((c) => c.id === id));
        if (!parent) return;
        siblings = parent.children;
    }
    const ids = siblings.map((s) => s.id);
    const idx = ids.indexOf(id);
    const swap = idx + dir;
    if (swap < 0 || swap >= ids.length) return;
    [ids[idx], ids[swap]] = [ids[swap], ids[idx]];
    router.post(route('topics.reorder'), { order: ids }, { preserveScroll: true });
}

// --- Add topic (optionally under a parent) ---
const form = useForm({ name: '', parent_id: '' });

function addTopic() {
    form.post(route('topics.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}

function startSubtopic(parentId) {
    form.parent_id = parentId;
    nextTick(() => nameInput.value?.focus());
}

const suggestions = ['World News', 'Technology', 'Business', 'Sports', 'Science', 'Entertainment'];
function quickAdd(name) {
    if (atLimit.value) return;
    form.name = name;
    addTopic();
}
</script>

<template>
    <Head title="My NewsFlow" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-serif text-2xl font-bold text-ink">My NewsFlow</h1>
                    <p class="text-sm text-gray-500">{{ limitLabel }}</p>
                </div>
                <span class="text-sm text-gray-400">
                    {{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }) }}
                </span>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <AdSlot slot="dashboard_top" format="horizontal" />

            <div class="lg:flex lg:gap-8">
                <!-- Left-column topic navigation (desktop) -->
                <aside v-if="topics.length" class="hidden lg:block lg:w-60 lg:shrink-0">
                    <nav class="sticky top-28 space-y-1" aria-label="Topics">
                        <button
                            @click="select('all')"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-semibold"
                            :class="selected === 'all' ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-100'"
                        >
                            All Topics
                            <span class="text-xs text-gray-400">{{ user.topic_count }}</span>
                        </button>

                        <template v-for="t in topics" :key="t.id">
                            <div class="group flex items-center">
                                <!-- Expand/collapse toggle (parents with children) -->
                                <button
                                    v-if="t.children && t.children.length"
                                    @click="toggle(t.id)"
                                    class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                                    :aria-expanded="!isCollapsed(t.id)"
                                    :aria-label="`${isCollapsed(t.id) ? 'Expand' : 'Collapse'} ${t.name} subtopics`"
                                    :title="isCollapsed(t.id) ? 'Expand' : 'Collapse'"
                                >
                                    <svg class="h-4 w-4 transition-transform" :class="{ '-rotate-90': isCollapsed(t.id) }" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <span v-else class="w-6 shrink-0" aria-hidden="true"></span>

                                <button
                                    @click="select(t.id)"
                                    class="flex flex-1 items-center gap-2 rounded-md px-2 py-2 text-left text-sm"
                                    :class="selected === t.id ? 'bg-brand-50 font-semibold text-brand-700' : 'text-gray-700 hover:bg-gray-100'"
                                >
                                    <span class="truncate">{{ t.name }}</span>
                                    <span v-if="t.children && t.children.length && isCollapsed(t.id)" class="ml-auto text-xs text-gray-400">{{ t.children.length }}</span>
                                </button>
                                <button
                                    @click="startSubtopic(t.id)"
                                    title="Add a subtopic"
                                    :aria-label="`Add a subtopic under ${t.name}`"
                                    class="ml-1 rounded p-1 text-gray-400 opacity-0 hover:bg-gray-100 hover:text-brand-600 focus:opacity-100 focus-visible:ring-2 focus-visible:ring-brand-500 group-hover:opacity-100"
                                >
                                    <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                </button>
                            </div>
                            <!-- Children (hidden when the category is collapsed) -->
                            <template v-if="!isCollapsed(t.id)">
                                <button
                                    v-for="c in (t.children || [])"
                                    :key="c.id"
                                    @click="select(c.id)"
                                    class="flex w-full items-center gap-2 rounded-md py-1.5 pl-11 pr-3 text-left text-sm"
                                    :class="selected === c.id ? 'bg-brand-50 font-semibold text-brand-700' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    <span class="truncate">{{ c.name }}</span>
                                </button>
                            </template>
                        </template>
                    </nav>
                </aside>

                <!-- Main column -->
                <div class="min-w-0 flex-1">
                    <!-- Flash -->
                    <div v-if="flash.success" role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ flash.success }}</div>
                    <div v-if="flash.error" role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ flash.error }}</div>

                    <!-- Mobile topic selector -->
                    <div v-if="topics.length" class="mb-6 flex gap-2 overflow-x-auto pb-1 lg:hidden">
                        <button
                            @click="select('all')"
                            class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold"
                            :class="selected === 'all' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'"
                        >All</button>
                        <template v-for="t in topics" :key="t.id">
                            <button
                                @click="select(t.id)"
                                class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold"
                                :class="selected === t.id ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700'"
                            >{{ t.name }}</button>
                            <button
                                v-for="c in (t.children || [])"
                                :key="c.id"
                                @click="select(c.id)"
                                class="shrink-0 rounded-full px-3 py-1.5 text-xs"
                                :class="selected === c.id ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600'"
                            >↳ {{ c.name }}</button>
                        </template>
                    </div>

                    <!-- Add topic -->
                    <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <form @submit.prevent="addTopic" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <div class="flex-1">
                                <label for="topic" class="sr-only">Add a topic</label>
                                <input
                                    id="topic"
                                    ref="nameInput"
                                    v-model="form.name"
                                    type="text"
                                    :disabled="atLimit"
                                    placeholder="Add a topic — e.g. World News, Information Technology, your team…"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
                                />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <select
                                v-if="topics.length"
                                v-model="form.parent_id"
                                :disabled="atLimit"
                                class="rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100 sm:w-52"
                                title="Add as a top-level topic or nest it under a category"
                            >
                                <option value="">Top-Level Topic</option>
                                <option v-for="t in topics" :key="t.id" :value="t.id">Under “{{ t.name }}”</option>
                            </select>
                            <button
                                type="submit"
                                :disabled="form.processing || atLimit || !form.name"
                                class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Adding…' : 'Add Topic' }}
                            </button>
                        </form>
                        <InputError :message="form.errors.parent_id" class="mt-1" />

                        <!-- Suggestions -->
                        <div v-if="!atLimit" class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-gray-400">Try:</span>
                            <button
                                v-for="s in suggestions"
                                :key="s"
                                @click="quickAdd(s)"
                                :disabled="form.processing"
                                class="rounded-full border border-gray-200 px-3 py-1 text-xs text-gray-600 hover:border-brand-300 hover:text-brand-700 disabled:opacity-50"
                            >+ {{ s }}</button>
                        </div>

                        <!-- Upgrade prompt at limit -->
                        <div v-if="atLimit" class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-brand-50 px-4 py-3">
                            <p class="text-sm text-brand-800">
                                You’ve reached the Free limit of {{ user.topic_limit }} topics.
                                Upgrade to Pro for <strong>unlimited topics</strong> and subtopics.
                            </p>
                            <Link :href="route('billing')" class="rounded-md bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700">Upgrade to Pro</Link>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="!topics.length" class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center">
                        <h3 class="font-serif text-xl font-semibold text-ink">Your Newsroom Is Empty</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                            Add your first topic above and we’ll pull the day’s most popular stories on it right away.
                            Tip: add a broad topic like “Information Technology”, then nest subtopics like “OpenAI” under it.
                        </p>
                    </div>

                    <!-- Watchlist (Pro) — stories matching the user's keywords -->
                    <div v-if="watchlist.length && selected === 'all'" class="mb-8 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l7.1-1.01L12 2z" /></svg>
                            <h2 class="font-serif text-lg font-bold text-ink">On Your Watchlist</h2>
                            <span class="text-xs text-gray-500">matching: {{ watchKeywords.join(', ') }}</span>
                        </div>
                        <ul class="divide-y divide-amber-100">
                            <li v-for="a in watchlist" :key="a.id" class="flex items-start justify-between gap-4 py-2.5">
                                <div class="min-w-0">
                                    <a :href="a.url" target="_blank" rel="noopener noreferrer" @click="openWatch(a.id)"
                                        class="font-serif text-base font-semibold leading-snug text-ink hover:text-brand-700">
                                        {{ a.headline }}
                                    </a>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500">
                                        <span class="rounded-full bg-white px-2 py-0.5 font-medium text-gray-600 ring-1 ring-gray-200">{{ a.topic_name }}</span>
                                        <span v-if="a.source">· {{ a.source }}</span>
                                        <span v-for="kw in a.matches" :key="kw" class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700">{{ kw }}</span>
                                    </div>
                                </div>
                                <a :href="a.url" target="_blank" rel="noopener noreferrer" @click="openWatch(a.id)"
                                    class="shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700">Read →</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Reading toolbar -->
                    <div v-if="topics.length" class="mb-6 flex items-center justify-end">
                        <button
                            @click="unreadOnly = !unreadOnly"
                            class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                            :class="unreadOnly ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                        >
                            <span class="inline-block h-2 w-2 rounded-full" :class="unreadOnly ? 'bg-brand-600' : 'bg-gray-300'"></span>
                            Unread Only
                        </button>
                    </div>

                    <!-- Topic feeds -->
                    <TopicSection
                        v-for="entry in renderList"
                        :key="entry.topic.id"
                        :topic="entry.topic"
                        :group-name="entry.groupName"
                        :is-pro="user.is_pro"
                        :saved-fingerprints="savedFingerprints"
                        :unread-only="unreadOnly"
                        :can-move-up="entry.canUp"
                        :can-move-down="entry.canDown"
                        @move="move"
                    />
                </div>
            </div>

            <AdSlot slot="dashboard_bottom" />
        </div>
    </AuthenticatedLayout>
</template>
