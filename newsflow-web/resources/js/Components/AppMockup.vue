<script setup>
/**
 * A lightweight, self-contained "browser screenshot" illustration of the
 * NewsFlow feed, used on the How-to-Use page. Real screenshots can replace
 * this later by swapping the <svg> for an <img>.
 */
defineProps({
    caption: { type: String, default: null },
});
</script>

<template>
    <figure class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
        <svg viewBox="0 0 800 480" class="w-full" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="NewsFlow feed illustration">
            <!-- window chrome -->
            <rect width="800" height="480" fill="#f8fafc" />
            <rect width="800" height="44" fill="#ffffff" />
            <line x1="0" y1="44" x2="800" y2="44" stroke="#e5e7eb" />
            <circle cx="22" cy="22" r="5" fill="#f87171" />
            <circle cx="40" cy="22" r="5" fill="#fbbf24" />
            <circle cx="58" cy="22" r="5" fill="#34d399" />
            <rect x="90" y="13" width="420" height="18" rx="9" fill="#f1f5f9" />
            <text x="104" y="26" font-family="sans-serif" font-size="11" fill="#94a3b8">newsflow.test/dashboard</text>

            <!-- app nav -->
            <rect x="0" y="44" width="800" height="40" fill="#ffffff" />
            <line x1="0" y1="84" x2="800" y2="84" stroke="#e5e7eb" />
            <rect x="20" y="56" width="16" height="16" rx="4" fill="#2563eb" />
            <text x="44" y="69" font-family="Georgia, serif" font-size="14" font-weight="700" fill="#0f172a">News<tspan fill="#2563eb">Flow</tspan></text>
            <text x="150" y="69" font-family="sans-serif" font-size="12" fill="#2563eb" font-weight="600">My NewsFlow™</text>
            <text x="250" y="69" font-family="sans-serif" font-size="12" fill="#64748b">Saved</text>
            <text x="310" y="69" font-family="sans-serif" font-size="12" fill="#64748b">Billing</text>
            <rect x="690" y="54" width="90" height="20" rx="10" fill="#eff6ff" />
            <text x="702" y="68" font-family="sans-serif" font-size="10" fill="#1d4ed8" font-weight="600">Pro · Lifetime</text>

            <!-- add topic bar -->
            <rect x="20" y="100" width="760" height="44" rx="10" fill="#ffffff" stroke="#e5e7eb" />
            <text x="36" y="127" font-family="sans-serif" font-size="12" fill="#94a3b8">Add a topic — e.g. World News, your team, a company…</text>
            <rect x="660" y="110" width="104" height="24" rx="6" fill="#2563eb" />
            <text x="681" y="126" font-family="sans-serif" font-size="11" fill="#ffffff" font-weight="600">Add topic</text>

            <!-- topic masthead -->
            <text x="20" y="178" font-family="Georgia, serif" font-size="18" font-weight="700" fill="#0f172a">World News</text>
            <line x1="20" y1="188" x2="780" y2="188" stroke="#0f172a" stroke-width="2" />

            <!-- article cards -->
            <g v-for="(col, i) in [0,1,2,3]" :key="i">
                <rect :x="20 + i*192" y="202" width="176" height="120" rx="10" fill="#ffffff" stroke="#e5e7eb" />
                <circle :cx="36 + i*192" cy="220" r="7" fill="#f1f5f9" />
                <text :x="32 + i*192" y="224" font-family="sans-serif" font-size="9" fill="#94a3b8">{{ i + 1 }}</text>
                <text :x="50 + i*192" y="224" font-family="sans-serif" font-size="9" fill="#94a3b8">Global Wire</text>
                <rect :x="36 + i*192" y="236" width="140" height="9" rx="3" fill="#0f172a" opacity="0.85" />
                <rect :x="36 + i*192" y="250" width="120" height="9" rx="3" fill="#0f172a" opacity="0.85" />
                <rect :x="36 + i*192" y="270" width="144" height="6" rx="3" fill="#cbd5e1" />
                <rect :x="36 + i*192" y="282" width="120" height="6" rx="3" fill="#cbd5e1" />
                <text :x="36 + i*192" y="312" font-family="sans-serif" font-size="10" fill="#2563eb" font-weight="600">Read more →</text>
            </g>

            <!-- second topic hint -->
            <text x="20" y="368" font-family="Georgia, serif" font-size="18" font-weight="700" fill="#0f172a">Technology</text>
            <line x1="20" y1="378" x2="780" y2="378" stroke="#0f172a" stroke-width="2" />
            <g v-for="(col, i) in [0,1,2,3]" :key="'b'+i">
                <rect :x="20 + i*192" y="392" width="176" height="70" rx="10" fill="#ffffff" stroke="#e5e7eb" />
                <rect :x="36 + i*192" y="408" width="140" height="9" rx="3" fill="#0f172a" opacity="0.8" />
                <rect :x="36 + i*192" y="422" width="110" height="9" rx="3" fill="#0f172a" opacity="0.8" />
                <text :x="36 + i*192" y="450" font-family="sans-serif" font-size="10" fill="#2563eb" font-weight="600">Read more →</text>
            </g>
        </svg>
        <figcaption v-if="caption" class="bg-white px-4 py-2 text-center text-xs text-gray-500">
            {{ caption }}
        </figcaption>
    </figure>
</template>
