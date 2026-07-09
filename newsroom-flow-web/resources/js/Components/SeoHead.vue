<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';

/**
 * Centralized SEO head tags for every public page — title, meta description,
 * canonical URL, Open Graph + Twitter cards, and optional Schema.org JSON-LD.
 *
 * Usage:
 *   <SeoHead
 *     title="Pricing"
 *     description="Free for 2 topics. NewsroomFlow Pro from $4.99/mo…"
 *     path="/pricing"
 *     :jsonLd="..."   <!-- optional Object or Array of Objects -->
 *   />
 *
 * The document <title> is left bare (just `title`); the global Inertia title
 * callback in app.js appends " - NewsroomFlow", so we don't double-brand. JSON-LD
 * is injected directly into <head> (Inertia's <Head> can't render <script>
 * content) — fine for Google, which executes JS; JS-less social scrapers read
 * the server-rendered defaults in app.blade.php instead.
 */
const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    path: { type: String, required: true },
    ogImage: { type: String, default: null },
    jsonLd: { type: [Object, Array], default: null },
    ogType: { type: String, default: 'website' },
});

const SITE_URL = 'https://newsroomflow.app';
const SITE_NAME = 'NewsroomFlow™';
const DEFAULT_OG_IMAGE = `${SITE_URL}/img/og-default.png`;

const brandedTitle = computed(() => `${props.title} — ${SITE_NAME}`);
const canonicalUrl = computed(() => `${SITE_URL}${props.path}`);
const ogImageUrl = computed(() => props.ogImage ?? DEFAULT_OG_IMAGE);

// --- JSON-LD injected straight into <head> (reliable; Google runs JS) ---
let injected = [];

function renderJsonLd() {
    if (typeof document === 'undefined') return;
    injected.forEach((el) => el.remove());
    injected = [];

    const blocks = !props.jsonLd ? [] : Array.isArray(props.jsonLd) ? props.jsonLd : [props.jsonLd];
    for (const block of blocks) {
        const el = document.createElement('script');
        el.type = 'application/ld+json';
        el.setAttribute('data-seo-jsonld', '');
        el.textContent = JSON.stringify(block);
        document.head.appendChild(el);
        injected.push(el);
    }
}

onMounted(renderJsonLd);
watch(() => props.jsonLd, renderJsonLd, { deep: true });
onBeforeUnmount(() => {
    injected.forEach((el) => el.remove());
    injected = [];
});
</script>

<template>
    <Head>
        <!-- Bare title — app.js appends " - NewsroomFlow". -->
        <title>{{ title }}</title>
        <meta name="description" :content="description" />
        <link rel="canonical" :href="canonicalUrl" />

        <!-- Open Graph -->
        <meta property="og:type" :content="ogType" />
        <meta property="og:site_name" :content="SITE_NAME" />
        <meta property="og:title" :content="brandedTitle" />
        <meta property="og:description" :content="description" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:image" :content="ogImageUrl" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="brandedTitle" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="ogImageUrl" />
    </Head>
</template>
