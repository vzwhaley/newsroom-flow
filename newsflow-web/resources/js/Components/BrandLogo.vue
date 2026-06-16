<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    // 'light' for light backgrounds (header), 'dark' for the footer.
    variant: { type: String, default: 'light' },
    // Icon height utility — larger by default than the old logo.
    iconClass: { type: String, default: 'h-14 w-auto' },
    // Hide the "by moon whale media, llc" tagline (e.g. tight spaces).
    tagline: { type: Boolean, default: true },
    // Where the logo links to.
    href: { type: String, default: '/' },
});

const dark = computed(() => props.variant === 'dark');
</script>

<template>
    <div class="flex items-center gap-3">
        <Link :href="href" aria-label="NewsFlow home" class="no-hover-underline shrink-0">
            <ApplicationLogo :class="[iconClass, dark ? 'text-brand-300' : 'text-brand-600']" />
        </Link>
        <span class="flex flex-col leading-tight">
            <Link
                :href="href"
                class="font-serif text-3xl font-bold tracking-tight"
                :class="dark ? 'text-white' : 'text-ink'"
            >
                News<span class="text-brand-600" :class="{ '!text-brand-400': dark }">Flow</span><span class="align-super text-sm">™</span>
            </Link>
            <span v-if="tagline" class="text-sm" :class="dark ? 'text-brand-200' : 'text-gray-500'">
                by
                <a
                    href="https://moonwhale.media"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-brand transition-colors"
                    :class="dark ? 'text-brand-200 hover:text-white' : 'text-brand-900 hover:text-gray-600'"
                >moon whale media, llc</a>
            </span>
        </span>
    </div>
</template>
