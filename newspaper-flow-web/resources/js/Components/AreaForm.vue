<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Country-aware local-area form. USA → city + state (+ optional ZIP);
 * everywhere else → city + country. Handles both create and edit.
 */
const props = defineProps({
    area: { type: Object, default: null }, // null = create
    countries: { type: Object, required: true }, // { US: 'United States', ... }
    states: { type: Object, required: true },    // { OH: 'Ohio', ... }
});
const emit = defineEmits(['done', 'cancel']);

const editing = computed(() => !!props.area);

const form = useForm({
    country_code: props.area?.country_code ?? 'US',
    city: props.area?.locality ?? '',
    state: props.area?.region ?? '',
    zip: props.area?.postal_code ?? '',
});

const isUs = computed(() => form.country_code === 'US');

// Sorted country options with the US pinned to the top.
const countryOptions = computed(() => {
    const entries = Object.entries(props.countries);
    entries.sort((a, b) => (a[0] === 'US' ? -1 : b[0] === 'US' ? 1 : a[1].localeCompare(b[1])));
    return entries;
});
const stateOptions = computed(() => Object.entries(props.states).sort((a, b) => a[1].localeCompare(b[1])));

function submit() {
    const opts = {
        preserveScroll: true,
        onSuccess: () => { form.reset(); emit('done'); },
    };
    if (editing.value) {
        form.transform((d) => ({ ...d, _method: 'patch' }))
            .post(route('areas.update', props.area.id), opts);
    } else {
        form.post(route('areas.store'), opts);
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label :for="`area-country-${area?.id ?? 'new'}`" class="block text-xs font-medium text-gray-600">Country</label>
                <select
                    :id="`area-country-${area?.id ?? 'new'}`"
                    v-model="form.country_code"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                >
                    <option v-for="[code, name] in countryOptions" :key="code" :value="code">{{ name }}</option>
                </select>
                <InputError class="mt-1" :message="form.errors.country_code" />
            </div>

            <div>
                <label :for="`area-city-${area?.id ?? 'new'}`" class="block text-xs font-medium text-gray-600">City</label>
                <input
                    :id="`area-city-${area?.id ?? 'new'}`"
                    v-model="form.city"
                    type="text"
                    :placeholder="isUs ? 'e.g. Cleveland' : 'e.g. Manchester'"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                />
                <InputError class="mt-1" :message="form.errors.city" />
            </div>

            <template v-if="isUs">
                <div>
                    <label :for="`area-state-${area?.id ?? 'new'}`" class="block text-xs font-medium text-gray-600">State</label>
                    <select
                        :id="`area-state-${area?.id ?? 'new'}`"
                        v-model="form.state"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Choose a state…</option>
                        <option v-for="[abbr, name] in stateOptions" :key="abbr" :value="abbr">{{ name }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.state" />
                </div>

                <div>
                    <label :for="`area-zip-${area?.id ?? 'new'}`" class="block text-xs font-medium text-gray-600">
                        ZIP <span class="text-gray-400">(optional)</span>
                    </label>
                    <input
                        :id="`area-zip-${area?.id ?? 'new'}`"
                        v-model="form.zip"
                        type="text"
                        inputmode="numeric"
                        maxlength="5"
                        placeholder="e.g. 44113"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <InputError class="mt-1" :message="form.errors.zip" />
                </div>
            </template>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
            >
                {{ editing ? 'Save changes' : 'Add local area' }}
            </button>
            <button type="button" @click="emit('cancel')" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                Cancel
            </button>
        </div>
    </form>
</template>
