<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TagInput from '@/Components/TagInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    topics: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const timezones = (() => {
    try {
        if (typeof Intl.supportedValuesOf === 'function') {
            return Intl.supportedValuesOf('timeZone');
        }
    } catch (e) { /* fall through */ }
    return [
        'UTC',
        'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
        'America/Indiana/Indianapolis', 'America/Phoenix', 'America/Anchorage', 'Pacific/Honolulu',
        'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Tokyo', 'Asia/Kolkata', 'Australia/Sydney',
    ];
})();

const hours = Array.from({ length: 24 }, (_, h) => ({
    value: h,
    label: new Date(2000, 0, 1, h).toLocaleTimeString(undefined, { hour: 'numeric' }),
}));

const isPro = computed(() => user.value.is_pro);

const form = useForm({
    refresh_hour: user.value.refresh_hour ?? 6,
    timezone: user.value.timezone ?? 'UTC',
    digest_enabled: user.value.digest_enabled ?? false,
    digest_new_only: user.value.digest_new_only ?? false,
    digest_topic_ids: props.topics.filter((t) => t.include_in_digest).map((t) => t.id),
    blocked_sources: [...(user.value.blocked_sources ?? [])],
    watch_keywords: [...(user.value.watch_keywords ?? [])],
    watchlist_push_enabled: user.value.watchlist_push_enabled ?? true,
});

function topicLabel(t) {
    return t.parent_name ? `${t.parent_name} › ${t.name}` : t.name;
}

function submit() {
    form.patch(route('preferences.update'), { preserveScroll: true });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">News Preferences</h2>
            <p class="mt-1 text-sm text-gray-600">
                Choose when your feed refreshes each day. We’ll gather the latest
                popular stories on your topics at this hour, in your timezone.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <InputLabel for="refresh_hour" value="Daily Refresh Time" />
                <select id="refresh_hour" v-model="form.refresh_hour" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <option v-for="h in hours" :key="h.value" :value="h.value">{{ h.label }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.refresh_hour" />
            </div>

            <div>
                <InputLabel for="timezone" value="Timezone" />
                <select id="timezone" v-model="form.timezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz.replace(/_/g, ' ') }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.timezone" />
            </div>

            <!-- Daily digest -->
            <div class="rounded-lg border border-gray-200 p-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox" v-model="form.digest_enabled" class="mt-1 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                    <span class="text-sm text-gray-700">
                        <span class="font-medium text-gray-900">Email Me a Daily Digest</span><br />
                        A “Your NewsroomFlow™ is ready” email with the morning’s headlines, delivered at your refresh time.
                    </span>
                </label>

                <!-- Digest options (only when enabled) -->
                <div v-if="form.digest_enabled" class="mt-4 space-y-4 border-t border-gray-100 pt-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" v-model="form.digest_new_only" class="mt-1 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                        <span class="text-sm text-gray-700">
                            <span class="font-medium text-gray-900">Only New Headlines</span><br />
                            Include just the stories added since your last digest, not the full list. (You’ll get no email on days with nothing new.)
                        </span>
                    </label>

                    <div v-if="topics.length">
                        <p class="text-sm font-medium text-gray-900">Topics to Include</p>
                        <p class="text-xs text-gray-500">Pick which topics appear in your digest.</p>
                        <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                            <label v-for="t in topics" :key="t.id" class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" :value="t.id" v-model="form.digest_topic_ids" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                                <span class="truncate">{{ topicLabel(t) }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro: blocked sources + watchlist -->
            <div v-if="isPro" class="space-y-5 rounded-lg border border-gray-200 p-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-gray-900">Sources &amp; Watchlist</h3>
                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">Pro</span>
                </div>

                <div>
                    <InputLabel value="Blocked Publishers" />
                    <p class="mb-2 text-xs text-gray-500">Hide articles from these publishers everywhere. Match by name or domain (e.g. “tabloid.com”).</p>
                    <TagInput v-model="form.blocked_sources" placeholder="e.g. Daily Tabloid" />
                </div>

                <div>
                    <InputLabel value="Watch Keywords" />
                    <p class="mb-2 text-xs text-gray-500">Stories across any topic that mention these words get pinned to a “Watchlist” at the top of your feed.</p>
                    <TagInput v-model="form.watch_keywords" placeholder="e.g. recall, merger" />
                </div>

                <label class="flex items-start gap-3 border-t border-gray-100 pt-4">
                    <input type="checkbox" v-model="form.watchlist_push_enabled" class="mt-1 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" />
                    <span class="text-sm text-gray-700">
                        <span class="font-medium text-gray-900">Priority Watchlist Push</span><br />
                        Get a push notification the moment a fresh story matches one of your
                        watch keywords — no waiting for the daily notification. (Requires
                        push notifications to be enabled on a device.)
                    </span>
                </label>
            </div>
            <div v-else class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-brand-50 px-4 py-3">
                <p class="text-sm text-brand-800">Block publishers and set keyword watchlists with <strong>Pro</strong>.</p>
                <Link :href="route('billing')" class="rounded-md bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">Upgrade</Link>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
