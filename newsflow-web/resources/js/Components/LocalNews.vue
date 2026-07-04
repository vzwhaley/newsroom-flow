<script setup>
import ArticleCard from '@/Components/ArticleCard.vue';
import AreaForm from '@/Components/AreaForm.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    areas: { type: Array, default: () => [] },
    geoOptions: { type: Object, default: () => ({ states: {}, countries: {} }) },
    savedFingerprints: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isPro = computed(() => !!user.value?.is_pro);

const savedSet = computed(() => new Set(props.savedFingerprints));

// Free tier: one area, locked after the grace window. Pro: unlimited.
const canAdd = computed(() =>
    user.value?.area_limit === null || (props.areas.length < (user.value?.area_limit ?? 1))
);

const addingOpen = ref(false);
const editingId = ref(null);

// Local read-state overrides (optimistic).
const overrides = reactive({});
function isRead(a) {
    return a.id in overrides ? overrides[a.id] : !!a.is_read;
}
function markRead(id) {
    if (overrides[id] === true) return;
    overrides[id] = true;
    window.axios.post(route('articles.read', id)).catch(() => { overrides[id] = false; });
}
function toggleRead(id, article) {
    const next = !isRead(article);
    overrides[id] = next;
    const req = next
        ? window.axios.post(route('articles.read', id))
        : window.axios.delete(route('articles.unread', id));
    req.catch(() => { overrides[id] = !next; });
}

function removeArea(area) {
    if (!confirm(`Remove local news for "${area.name}"?`)) return;
    router.delete(route('areas.destroy', area.id), { preserveScroll: true });
}

function subtitle(area) {
    if (area.country_code === 'US') {
        return [area.locality, area.region, area.postal_code].filter(Boolean).join(', ');
    }
    return area.name;
}
</script>

<template>
    <section v-if="areas.length || canAdd" class="mb-12">
        <!-- Section masthead -->
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2 border-b-2 border-ink pb-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Your Area</p>
                <h2 class="font-serif text-2xl font-bold tracking-tight text-ink">📍 Local News</h2>
            </div>
            <button
                v-if="canAdd && !addingOpen"
                @click="addingOpen = true"
                class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50"
            >
                <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Add a local area
            </button>
        </div>

        <!-- Free-tier explainer / upsell -->
        <p v-if="!isPro" class="mb-4 text-xs text-gray-500">
            Free accounts include <strong>one</strong> local area. It’s permanent after a short window to fix typos —
            <template v-if="!areas.length">choose carefully!</template>
            <template v-else>upgrade to Pro to follow more places and edit anytime.</template>
        </p>

        <!-- Add form -->
        <div v-if="addingOpen" class="mb-6">
            <AreaForm
                :countries="geoOptions.countries"
                :states="geoOptions.states"
                @done="addingOpen = false"
                @cancel="addingOpen = false"
            />
        </div>

        <!-- Empty state -->
        <p v-if="!areas.length && !addingOpen" class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-500">
            Add your city to get news tailored to just your area.
        </p>

        <!-- Each area feed -->
        <div v-for="area in areas" :key="area.id" class="mb-10">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <h3 class="font-serif text-xl font-bold text-ink">{{ area.name }}</h3>
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-brand-700 ring-1 ring-inset ring-brand-100">Local</span>
                    <span v-if="area.locked" class="inline-flex items-center gap-1 text-xs text-gray-400" title="This area is locked. Upgrade to Pro to change it.">
                        <svg class="h-3.5 w-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Locked
                    </span>
                </div>
                <div v-if="!area.locked" class="flex items-center gap-1">
                    <button
                        @click="editingId = editingId === area.id ? null : area.id"
                        :aria-label="`Edit ${area.name}`"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-ink"
                    >
                        <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button
                        @click="removeArea(area)"
                        :aria-label="`Remove ${area.name}`"
                        class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                    >
                        <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <!-- Edit form -->
            <div v-if="editingId === area.id" class="mb-4">
                <AreaForm
                    :area="area"
                    :countries="geoOptions.countries"
                    :states="geoOptions.states"
                    @done="editingId = null"
                    @cancel="editingId = null"
                />
            </div>

            <!-- Articles -->
            <div v-if="area.articles.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <ArticleCard
                    v-for="(a, i) in area.articles"
                    :key="a.id"
                    :article="a"
                    :rank="i + 1"
                    :topic-name="subtitle(area)"
                    :is-pro="isPro"
                    :can-save="isPro"
                    :is-saved="savedSet.has(a.fingerprint)"
                    :is-read="isRead(a)"
                    @mark-read="markRead(a.id)"
                    @toggle-read="toggleRead(a.id, a)"
                />
            </div>
            <p v-else class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-500">
                Gathering local stories for {{ area.name }} — check back after the next refresh.
            </p>
        </div>
    </section>
</template>
