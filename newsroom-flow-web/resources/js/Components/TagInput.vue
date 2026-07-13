<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Add…' },
    // Accessible name for the text input (there is no visible <label> tied to it).
    label: { type: String, default: 'Add an item' },
});
const emit = defineEmits(['update:modelValue']);

const draft = ref('');

function add() {
    const v = draft.value.trim();
    if (v && !props.modelValue.some((x) => x.toLowerCase() === v.toLowerCase())) {
        emit('update:modelValue', [...props.modelValue, v]);
    }
    draft.value = '';
}
function remove(tag) {
    emit('update:modelValue', props.modelValue.filter((t) => t !== tag));
}
</script>

<template>
    <div>
        <div v-if="modelValue.length" class="mb-2 flex flex-wrap gap-2">
            <span v-for="tag in modelValue" :key="tag" class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700">
                {{ tag }}
                <button type="button" @click="remove(tag)" :aria-label="`Remove ${tag}`" class="text-gray-400 hover:text-red-600"><span aria-hidden="true">×</span></button>
            </span>
        </div>
        <div class="flex gap-2">
            <input
                v-model="draft"
                @keydown.enter.prevent="add"
                type="text"
                :placeholder="placeholder"
                :aria-label="label"
                class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
            />
            <button type="button" @click="add" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">Add</button>
        </div>
    </div>
</template>
