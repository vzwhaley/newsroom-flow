<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Dropdown from '@/Components/Dropdown.vue';
import TopicSection from '@/Components/TopicSection.vue';
import LocalNews from '@/Components/LocalNews.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps({
    topics: { type: Array, default: () => [] },
    savedFingerprints: { type: Array, default: () => [] },
    watchlist: { type: Array, default: () => [] },
    watchKeywords: { type: Array, default: () => [] },
    reading: { type: Object, default: () => ({ streak: 0, read_today: false, total_reads: 0 }) },
    areas: { type: Array, default: () => [] },
    geoOptions: { type: Object, default: () => ({ states: {}, countries: {} }) },
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

// Local News quick link — jump to the always-present Local News section.
const localRef = ref(null);
function scrollToLocal() {
    const el = localRef.value;
    if (!el) return;
    const y = el.getBoundingClientRect().top + window.scrollY - 100; // offset for the sticky header
    window.scrollTo({ top: y, behavior: 'smooth' });
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

// --- Re-parent: drag-and-drop + the per-topic "Move under…" menu ---
const dragId = ref(null);
const dropTargetId = ref(null); // a topic id, or 'toplevel'

function onDragStart(id, e) {
    dragId.value = id;
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', String(id)); } catch { /* ignore */ }
    }
}
function onDragEnd() {
    dragId.value = null;
    dropTargetId.value = null;
}
function onDragOverTopic(id, e) {
    if (dragId.value && dragId.value !== id) {
        e.preventDefault();
        dropTargetId.value = id;
    }
}
function onDropOnTopic(targetId, e) {
    e.preventDefault();
    const id = dragId.value;
    onDragEnd();
    if (!id || id === targetId) return;
    moveTo(id, targetId); // nest the dragged topic under the target
}
function onDragOverTop(e) {
    if (dragId.value) {
        e.preventDefault();
        dropTargetId.value = 'toplevel';
    }
}
function onDropTop(e) {
    e.preventDefault();
    const id = dragId.value;
    onDragEnd();
    if (!id) return;
    moveTo(id, null); // promote to top level
}

// Valid destinations for a topic's "Move under…" menu.
function moveOptions(node) {
    const isChild = !!node.parent_id;
    const hasChildren = (node.children || []).length > 0;
    const opts = [];
    if (isChild) opts.push({ label: '↑ Move to top level', parentId: null });
    if (!hasChildren) {
        for (const t of props.topics) {
            if (t.id === node.id) continue;            // not under itself
            if (isChild && t.id === node.parent_id) continue; // already there
            opts.push({ label: `Under “${t.name}”`, parentId: t.id });
        }
    }
    return opts;
}
function moveTo(id, parentId) {
    router.post(route('topics.move', id), { parent_id: parentId }, { preserveScroll: true });
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

// --- AI daily briefing (Pro) — one Claude-written front page per day. ---
const briefing = ref(null);
const briefingLoading = ref(false);

onMounted(async () => {
    if (!user.value.is_pro || !props.topics.length) return;
    briefingLoading.value = true;
    try {
        const { data } = await window.axios.get(route('briefing'));
        briefing.value = data;
    } catch {
        briefing.value = null; // quietly hide the card (e.g. no articles yet)
    } finally {
        briefingLoading.value = false;
    }
});
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
                <div class="flex items-center gap-3">
                    <Link
                        v-if="reading.streak > 0"
                        :href="route('stats')"
                        class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700 ring-1 ring-inset ring-orange-200 hover:bg-orange-100"
                        :title="`${reading.total_reads} articles read all-time${reading.read_today ? '' : ' — read one today to keep your streak!'}`"
                    >
                        <span aria-hidden="true">🔥</span>
                        {{ reading.streak }}-day streak
                    </Link>
                    <span class="text-sm text-gray-400">
                        {{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }) }}
                    </span>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <AdSlot slot="dashboard_top" format="horizontal" />

            <div class="lg:flex lg:gap-8">
                <!-- Left-column topic navigation (desktop) -->
                <aside v-if="topics.length" class="hidden lg:block lg:w-64 lg:shrink-0">
                    <nav class="sticky top-28 space-y-1 rounded-2xl bg-slate-800 p-3 text-slate-200 shadow-sm ring-1 ring-black/10" aria-label="Topics">
                        <!-- Local News — first, always available -->
                        <button
                            @click="scrollToLocal"
                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-semibold text-slate-100 hover:bg-white/10"
                        >
                            <span aria-hidden="true">📍</span>
                            <span class="truncate">Local News</span>
                        </button>

                        <div class="my-1 border-t border-white/10"></div>

                        <!-- All Topics (also the drop zone to promote a topic to top level) -->
                        <button
                            @click="select('all')"
                            @dragover="onDragOverTop"
                            @drop="onDropTop"
                            @dragleave="dropTargetId === 'toplevel' ? (dropTargetId = null) : null"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-semibold transition"
                            :class="[
                                selected === 'all' ? 'bg-brand-600 text-white' : 'text-slate-300 hover:bg-white/10',
                                dropTargetId === 'toplevel' ? 'ring-2 ring-brand-400' : '',
                            ]"
                        >
                            <span>{{ dragId ? 'Drop here → top level' : 'All Topics' }}</span>
                            <span v-if="!dragId" class="text-xs text-slate-400">{{ user.topic_count }}</span>
                        </button>

                        <template v-for="t in topics" :key="t.id">
                            <div
                                class="group flex items-center rounded-md"
                                draggable="true"
                                @dragstart="onDragStart(t.id, $event)"
                                @dragend="onDragEnd"
                                @dragover="onDragOverTopic(t.id, $event)"
                                @dragleave="dropTargetId === t.id ? (dropTargetId = null) : null"
                                @drop="onDropOnTopic(t.id, $event)"
                                :class="dropTargetId === t.id ? 'bg-white/5 ring-2 ring-brand-400' : ''"
                            >
                                <!-- Drag handle -->
                                <span class="shrink-0 cursor-grab px-0.5 text-slate-500 group-hover:text-slate-300" title="Drag to move" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM13 4a1 1 0 11-2 0 1 1 0 012 0zM13 10a1 1 0 11-2 0 1 1 0 012 0zM13 16a1 1 0 11-2 0 1 1 0 012 0z" /></svg>
                                </span>
                                <!-- Expand/collapse toggle (parents with children) -->
                                <button
                                    v-if="t.children && t.children.length"
                                    @click="toggle(t.id)"
                                    class="rounded p-1 text-slate-400 hover:bg-white/10 hover:text-white"
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
                                    :class="selected === t.id ? 'bg-brand-600 font-semibold text-white' : 'text-slate-300 hover:bg-white/10'"
                                >
                                    <span class="truncate">{{ t.name }}</span>
                                    <span v-if="t.children && t.children.length && isCollapsed(t.id)" class="ml-auto text-xs text-slate-400">{{ t.children.length }}</span>
                                </button>

                                <!-- Options menu (add subtopic + move under…) -->
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button
                                            title="Topic options"
                                            aria-label="Topic options"
                                            class="rounded p-1 text-slate-400 opacity-0 hover:bg-white/10 hover:text-white focus:opacity-100 group-hover:opacity-100"
                                        >
                                            <svg class="h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <button @click="startSubtopic(t.id)" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Add subtopic</button>
                                        <template v-if="moveOptions(t).length">
                                            <div class="my-1 border-t border-gray-100"></div>
                                            <p class="px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Move</p>
                                            <button
                                                v-for="opt in moveOptions(t)"
                                                :key="`${t.id}-${opt.parentId}`"
                                                @click="moveTo(t.id, opt.parentId)"
                                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                            >{{ opt.label }}</button>
                                        </template>
                                    </template>
                                </Dropdown>
                            </div>
                            <!-- Children (hidden when the category is collapsed) -->
                            <template v-if="!isCollapsed(t.id)">
                                <div
                                    v-for="c in (t.children || [])"
                                    :key="c.id"
                                    class="group flex items-center rounded-md pl-6"
                                    draggable="true"
                                    @dragstart="onDragStart(c.id, $event)"
                                    @dragend="onDragEnd"
                                >
                                    <span class="shrink-0 cursor-grab px-0.5 text-slate-500 group-hover:text-slate-300" title="Drag to move" aria-hidden="true">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM13 4a1 1 0 11-2 0 1 1 0 012 0zM13 10a1 1 0 11-2 0 1 1 0 012 0zM13 16a1 1 0 11-2 0 1 1 0 012 0z" /></svg>
                                    </span>
                                    <button
                                        @click="select(c.id)"
                                        class="flex flex-1 items-center gap-2 rounded-md py-1.5 pl-1 pr-2 text-left text-sm"
                                        :class="selected === c.id ? 'bg-brand-600 font-semibold text-white' : 'text-slate-400 hover:bg-white/10'"
                                    >
                                        <span class="truncate">{{ c.name }}</span>
                                    </button>
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button
                                                title="Move subtopic"
                                                aria-label="Move subtopic"
                                                class="rounded p-1 text-slate-400 opacity-0 hover:bg-white/10 hover:text-white focus:opacity-100 group-hover:opacity-100"
                                            >
                                                <svg class="h-4 w-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <p class="px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Move</p>
                                            <button
                                                v-for="opt in moveOptions(c)"
                                                :key="`${c.id}-${opt.parentId}`"
                                                @click="moveTo(c.id, opt.parentId)"
                                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                            >{{ opt.label }}</button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </template>
                        </template>

                        <p class="px-3 pt-2 text-[11px] text-slate-400">Drag a topic onto another to nest it, or onto “All Topics” to move it back out.</p>
                    </nav>
                </aside>

                <!-- Main column -->
                <div class="min-w-0 flex-1">
                    <!-- Flash -->
                    <div v-if="flash.success" role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ flash.success }}</div>
                    <div v-if="flash.error" role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ flash.error }}</div>

                    <!-- AI daily briefing (Pro) -->
                    <section
                        v-if="briefingLoading || briefing"
                        class="relative mb-8 overflow-hidden rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-indigo-50 p-5 shadow-sm"
                        aria-label="Your daily briefing"
                    >
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-indigo-500 to-violet-500"></div>
                        <div class="mb-2 flex items-center gap-2">
                            <svg class="h-5 w-5 text-brand-600" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <h2 class="font-serif text-lg font-bold text-ink">Your Daily Briefing</h2>
                            <span v-if="briefing && !briefing.ai" class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500">Preview</span>
                        </div>
                        <p v-if="briefingLoading" role="status" class="text-sm text-gray-500">Writing your front page…</p>
                        <p v-else class="text-sm leading-relaxed text-gray-700">{{ briefing.briefing }}</p>
                    </section>

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
                        <button
                            @click="scrollToLocal"
                            class="shrink-0 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700"
                        >📍 Local</button>
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

                    <!-- Local News (area-tailored feeds) — always shown; it's a
                         separate section from topics and must never vanish when a
                         topic is focused or added. -->
                    <div ref="localRef">
                        <LocalNews
                            :areas="areas"
                            :geo-options="geoOptions"
                            :saved-fingerprints="savedFingerprints"
                        />
                    </div>
                </div>
            </div>

            <AdSlot slot="dashboard_bottom" format="horizontal" />
        </div>
    </AuthenticatedLayout>
</template>
