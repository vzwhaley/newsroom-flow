<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TopicSection from '@/Components/TopicSection.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    topics: { type: Array, default: () => [] },
    savedFingerprints: { type: Array, default: () => [] },
});

function move({ id, dir }) {
    const ids = props.topics.map((t) => t.id);
    const idx = ids.indexOf(id);
    const swap = idx + dir;
    if (swap < 0 || swap >= ids.length) return;
    [ids[idx], ids[swap]] = [ids[swap], ids[idx]];
    router.post(route('topics.reorder'), { order: ids }, { preserveScroll: true });
}

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});

const atLimit = computed(() => {
    const limit = user.value.topic_limit;
    return limit !== null && props.topics.length >= limit;
});

const limitLabel = computed(() => {
    if (user.value.topic_limit === null) return 'Unlimited topics';
    return `${props.topics.length} of ${user.value.topic_limit} topics used`;
});

const form = useForm({ name: '' });

function addTopic() {
    form.post(route('topics.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
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
            <!-- Flash messages -->
            <div
                v-if="flash.success"
                class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                {{ flash.error }}
            </div>

            <!-- Add topic -->
            <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <form @submit.prevent="addTopic" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="flex-1">
                        <label for="topic" class="sr-only">Add a topic</label>
                        <input
                            id="topic"
                            v-model="form.name"
                            type="text"
                            :disabled="atLimit"
                            placeholder="Add a topic — e.g. World News, your team, a company, a hobby…"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing || atLimit || !form.name"
                        class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Adding…' : 'Add topic' }}
                    </button>
                </form>

                <!-- Suggestions -->
                <div v-if="!atLimit" class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-400">Try:</span>
                    <button
                        v-for="s in suggestions"
                        :key="s"
                        @click="quickAdd(s)"
                        :disabled="form.processing"
                        class="rounded-full border border-gray-200 px-3 py-1 text-xs text-gray-600 hover:border-brand-300 hover:text-brand-700 disabled:opacity-50"
                    >
                        + {{ s }}
                    </button>
                </div>

                <!-- Upgrade prompt at limit -->
                <div
                    v-if="atLimit"
                    class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-brand-50 px-4 py-3"
                >
                    <p class="text-sm text-brand-800">
                        You’ve reached the Free limit of {{ user.topic_limit }} topics.
                        Upgrade to Pro for <strong>unlimited topics</strong>.
                    </p>
                    <Link
                        :href="route('billing')"
                        class="rounded-md bg-brand-600 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-700"
                    >
                        Upgrade to Pro
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="!topics.length"
                class="rounded-2xl border-2 border-dashed border-gray-200 p-12 text-center"
            >
                <h3 class="font-serif text-xl font-semibold text-ink">Your newsroom is empty</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    Add your first topic above and we’ll pull the day’s most
                    popular stories on it right away.
                </p>
            </div>

            <!-- Topic feeds -->
            <TopicSection
                v-for="(topic, i) in topics"
                :key="topic.id"
                :topic="topic"
                :is-pro="user.is_pro"
                :saved-fingerprints="savedFingerprints"
                :can-move-up="i > 0"
                :can-move-down="i < topics.length - 1"
                @move="move"
            />
        </div>
    </AuthenticatedLayout>
</template>
