<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            streak: 0, read_today: false, total_reads: 0,
            longest_streak: 0, days_active: 0, heatmap: {}, from: null, to: null,
        }),
    },
});

// --- Heatmap grid: columns = weeks, rows = Mon..Sun, GitHub-style. ---
const weeks = computed(() => {
    if (!props.stats.from || !props.stats.to) return [];

    const out = [];
    const cursor = new Date(props.stats.from + 'T00:00:00');
    const end = new Date(props.stats.to + 'T00:00:00');
    let week = [];

    while (cursor <= end) {
        const key = cursor.toISOString().slice(0, 10);
        week.push({ date: key, reads: props.stats.heatmap[key] ?? 0 });
        if (week.length === 7) {
            out.push(week);
            week = [];
        }
        cursor.setDate(cursor.getDate() + 1);
    }
    if (week.length) out.push(week);

    return out;
});

const maxReads = computed(() =>
    Math.max(1, ...Object.values(props.stats.heatmap ?? {}))
);

function cellClass(reads) {
    if (!reads) return 'bg-gray-100';
    const ratio = reads / maxReads.value;
    if (ratio <= 0.25) return 'bg-brand-200';
    if (ratio <= 0.5) return 'bg-brand-400';
    if (ratio <= 0.75) return 'bg-brand-600';
    return 'bg-brand-800';
}

function cellTitle(cell) {
    const d = new Date(cell.date + 'T00:00:00').toLocaleDateString(undefined, {
        month: 'short', day: 'numeric', year: 'numeric',
    });
    return cell.reads === 1 ? `1 article read on ${d}` : `${cell.reads} articles read on ${d}`;
}

// --- Share the streak (mint the public brag card). ---
const shareState = ref(null); // null | 'sharing' | 'copied' | 'shared' | 'error'

async function shareStreak() {
    if (shareState.value === 'sharing') return;
    shareState.value = 'sharing';
    try {
        const { data } = await window.axios.post(route('stats.share'));
        if (navigator.share) {
            await navigator.share({ title: `My ${props.stats.streak}-day NewsFlow™ streak`, url: data.url });
            shareState.value = 'shared';
        } else {
            await navigator.clipboard.writeText(data.url);
            shareState.value = 'copied';
        }
    } catch {
        shareState.value = null;
        return;
    }
    setTimeout(() => (shareState.value = null), 2500);
}

const cards = computed(() => [
    { label: 'Current Streak', value: `${props.stats.streak}`, suffix: props.stats.streak === 1 ? 'day' : 'days', hot: props.stats.streak > 0 },
    { label: 'Longest Streak', value: `${props.stats.longest_streak}`, suffix: props.stats.longest_streak === 1 ? 'day' : 'days' },
    { label: 'Articles Read', value: `${props.stats.total_reads}`, suffix: 'all-time' },
    { label: 'Days Active', value: `${props.stats.days_active}`, suffix: 'total' },
]);
</script>

<template>
    <Head title="Reading Stats" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="font-serif text-2xl font-bold text-ink">Reading Stats</h1>
                <button
                    v-if="stats.streak > 0"
                    @click="shareStreak"
                    :disabled="shareState === 'sharing'"
                    class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-orange-500 to-red-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-orange-600 hover:to-red-600 disabled:opacity-60"
                >
                    <span aria-hidden="true">🔥</span>
                    {{ shareState === 'copied' ? 'Link Copied!' : shareState === 'shared' ? 'Shared!' : 'Share My Streak' }}
                </button>
            </div>
        </template>

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Stat cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div
                    v-for="c in cards"
                    :key="c.label"
                    class="rounded-2xl border border-gray-100 bg-white p-4 text-center shadow-sm"
                >
                    <p class="font-serif text-3xl font-bold" :class="c.hot ? 'text-orange-600' : 'text-ink'">
                        <span v-if="c.hot" aria-hidden="true">🔥 </span>{{ c.value }}
                    </p>
                    <p class="text-xs text-gray-400">{{ c.suffix }}</p>
                    <p class="mt-1 text-sm font-medium text-gray-600">{{ c.label }}</p>
                </div>
            </div>

            <p v-if="!stats.read_today && stats.streak > 0" class="mt-4 rounded-lg bg-orange-50 px-4 py-3 text-sm text-orange-800">
                You haven’t read anything today — open a story to keep your {{ stats.streak }}-day streak alive!
            </p>

            <!-- Heatmap -->
            <section class="mt-8 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm" aria-label="Reading activity heatmap">
                <h2 class="font-serif text-lg font-bold text-ink">Last 6 Months</h2>
                <p class="mb-4 text-xs text-gray-400">Each square is a day — darker means more articles read.</p>

                <div v-if="weeks.length" class="overflow-x-auto pb-1">
                    <div class="flex gap-[3px]" role="img" :aria-label="`Reading activity: ${stats.days_active} active days, ${stats.total_reads} articles read all-time`">
                        <div v-for="(week, wi) in weeks" :key="wi" class="flex flex-col gap-[3px]">
                            <div
                                v-for="cell in week"
                                :key="cell.date"
                                class="h-3 w-3 rounded-[3px]"
                                :class="cellClass(cell.reads)"
                                :title="cellTitle(cell)"
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-1.5 text-xs text-gray-400">
                    Less
                    <span class="h-3 w-3 rounded-[3px] bg-gray-100"></span>
                    <span class="h-3 w-3 rounded-[3px] bg-brand-200"></span>
                    <span class="h-3 w-3 rounded-[3px] bg-brand-400"></span>
                    <span class="h-3 w-3 rounded-[3px] bg-brand-600"></span>
                    <span class="h-3 w-3 rounded-[3px] bg-brand-800"></span>
                    More
                </div>
            </section>

            <p v-if="!stats.days_active" class="mt-6 rounded-2xl border-2 border-dashed border-gray-200 p-10 text-center text-sm text-gray-500">
                No reading activity yet — open any article from your feed and your
                streak starts today.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
