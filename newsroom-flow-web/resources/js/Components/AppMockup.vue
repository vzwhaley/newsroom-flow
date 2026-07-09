<script setup>
/**
 * A lightweight, self-contained "browser screenshot" illustration of the
 * NewsroomFlow dashboard, used on the How-to-Use page. It mirrors the current
 * layout: a dark left sidebar (Local News + topics), the AI Daily Briefing card,
 * a topic masthead with Unread-Only / Mark-All-Read / Refresh, and the
 * two-column article grid. Real screenshots can replace this later by swapping
 * the <svg> for an <img>.
 */
defineProps({
    caption: { type: String, default: null },
});

// Two-column article grid (4 cards) — top-left origin per card.
const cards = [
    { n: 1, src: 'Global Wire', x: 244, y: 274 },
    { n: 2, src: 'World Desk', x: 520, y: 274 },
    { n: 3, src: 'Newsline', x: 244, y: 396 },
    { n: 4, src: 'The Dispatch', x: 520, y: 396 },
];
</script>

<template>
    <figure class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
        <svg viewBox="0 0 800 520" class="w-full" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="NewsroomFlow dashboard illustration: a dark topic sidebar, a daily briefing card, and a two-column grid of ranked articles.">
            <defs>
                <linearGradient id="briefGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#eff6ff" />
                    <stop offset="55%" stop-color="#ffffff" />
                    <stop offset="100%" stop-color="#eef2ff" />
                </linearGradient>
                <linearGradient id="accentGrad" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#3b82f6" />
                    <stop offset="50%" stop-color="#6366f1" />
                    <stop offset="100%" stop-color="#8b5cf6" />
                </linearGradient>
            </defs>

            <!-- page + window chrome -->
            <rect width="800" height="520" fill="#f8fafc" />
            <rect width="800" height="40" fill="#ffffff" />
            <line x1="0" y1="40" x2="800" y2="40" stroke="#e5e7eb" />
            <circle cx="20" cy="20" r="5" fill="#f87171" />
            <circle cx="38" cy="20" r="5" fill="#fbbf24" />
            <circle cx="56" cy="20" r="5" fill="#34d399" />
            <rect x="84" y="11" width="360" height="18" rx="9" fill="#f1f5f9" />
            <text x="98" y="24" font-family="sans-serif" font-size="11" fill="#94a3b8">newsroomflow.test/dashboard</text>

            <!-- app nav -->
            <rect x="0" y="40" width="800" height="40" fill="#ffffff" />
            <line x1="0" y1="80" x2="800" y2="80" stroke="#e5e7eb" />
            <rect x="20" y="52" width="16" height="16" rx="4" fill="#2563eb" />
            <text x="44" y="65" font-family="Georgia, serif" font-size="14" font-weight="700" fill="#0f172a">Newsroom<tspan fill="#2563eb">Flow</tspan></text>
            <text x="150" y="65" font-family="sans-serif" font-size="12" fill="#2563eb" font-weight="600">My NewsroomFlow™</text>
            <text x="272" y="65" font-family="sans-serif" font-size="12" fill="#64748b">Saved</text>
            <text x="324" y="65" font-family="sans-serif" font-size="12" fill="#64748b">Archive</text>
            <text x="386" y="65" font-family="sans-serif" font-size="12" fill="#64748b">Billing</text>
            <rect x="694" y="50" width="90" height="20" rx="10" fill="#eff6ff" />
            <text x="706" y="64" font-family="sans-serif" font-size="10" fill="#1d4ed8" font-weight="600">Pro · Lifetime</text>

            <!-- page header band -->
            <rect x="0" y="80" width="800" height="44" fill="#ffffff" />
            <line x1="0" y1="124" x2="800" y2="124" stroke="#e5e7eb" />
            <text x="20" y="103" font-family="Georgia, serif" font-size="17" font-weight="700" fill="#0f172a">My NewsroomFlow™</text>
            <text x="20" y="117" font-family="sans-serif" font-size="10" fill="#94a3b8">Unlimited Topics</text>
            <!-- streak chip -->
            <rect x="600" y="92" width="96" height="20" rx="10" fill="#fff7ed" stroke="#fed7aa" />
            <text x="612" y="106" font-family="sans-serif" font-size="10" fill="#c2410c" font-weight="600">🔥 3-day streak</text>
            <text x="784" y="106" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">Tue, Jul 7, 2026</text>

            <!-- ===================== LEFT SIDEBAR (dark) ===================== -->
            <rect x="16" y="140" width="208" height="368" rx="14" fill="#1e293b" />

            <!-- Local News -->
            <text x="34" y="166" font-family="sans-serif" font-size="12" fill="#f1f5f9" font-weight="600">📍 Local News</text>
            <text x="206" y="166" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">1</text>
            <!-- state group -->
            <path d="M36 179 l5 5 l5 -5" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <text x="52" y="186" font-family="sans-serif" font-size="11" fill="#cbd5e1">Illinois</text>
            <text x="206" y="186" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">1</text>
            <!-- active city -->
            <rect x="28" y="196" width="184" height="22" rx="6" fill="#2563eb" />
            <text x="64" y="211" font-family="sans-serif" font-size="11" fill="#ffffff" font-weight="600">Springfield</text>

            <line x1="28" y1="232" x2="212" y2="232" stroke="#334155" stroke-width="1" />

            <!-- All Topics -->
            <text x="34" y="250" font-family="sans-serif" font-size="12" fill="#e2e8f0" font-weight="600">All Topics</text>
            <text x="206" y="250" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">4</text>

            <!-- World News (selected) -->
            <rect x="28" y="260" width="184" height="22" rx="6" fill="#2563eb" />
            <text x="40" y="275" font-family="sans-serif" font-size="11" fill="#c7d2fe">⋮⋮</text>
            <text x="58" y="275" font-family="sans-serif" font-size="12" fill="#ffffff" font-weight="600">World News</text>
            <!-- Technology (has subtopic) -->
            <path d="M36 293 l5 5 l5 -5" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <text x="58" y="300" font-family="sans-serif" font-size="12" fill="#cbd5e1">Technology</text>
            <text x="74" y="322" font-family="sans-serif" font-size="11" fill="#94a3b8">↳ OpenAI</text>
            <!-- more topics -->
            <text x="58" y="346" font-family="sans-serif" font-size="12" fill="#cbd5e1">Business</text>
            <text x="58" y="370" font-family="sans-serif" font-size="12" fill="#cbd5e1">Sports</text>

            <!-- ===================== MAIN CONTENT ===================== -->
            <!-- AI daily briefing card -->
            <rect x="244" y="140" width="540" height="76" rx="14" fill="url(#briefGrad)" stroke="#dbeafe" />
            <rect x="244" y="140" width="540" height="4" fill="url(#accentGrad)" />
            <path d="M264 159 L257 174 L263 174 L261 185 L272 169 L265 169 Z" fill="#2563eb" />
            <text x="282" y="172" font-family="Georgia, serif" font-size="13" font-weight="700" fill="#0f172a">Your Daily Briefing — Today Across Your Newsroom</text>
            <text x="262" y="192" font-family="sans-serif" font-size="9.5" fill="#4b5563">Markets steadied on upbeat jobs data, the EU advanced its AI rules, and both teams you follow won</text>
            <text x="262" y="205" font-family="sans-serif" font-size="9.5" fill="#4b5563">overnight — here's the one-minute version across your whole newsroom.</text>

            <!-- topic masthead: World News + controls -->
            <text x="244" y="252" font-family="Georgia, serif" font-size="18" font-weight="700" fill="#0f172a">World News</text>
            <!-- controls (right-aligned) -->
            <rect x="556" y="237" width="82" height="20" rx="10" fill="#ffffff" stroke="#e5e7eb" />
            <circle cx="567" cy="247" r="4" fill="#cbd5e1" />
            <text x="576" y="250" font-family="sans-serif" font-size="9" fill="#4b5563">Unread Only</text>
            <text x="718" y="250" text-anchor="end" font-family="sans-serif" font-size="9" fill="#4b5563">Mark All Read</text>
            <rect x="726" y="237" width="58" height="20" rx="6" fill="#2563eb" />
            <text x="755" y="250" text-anchor="middle" font-family="sans-serif" font-size="9" fill="#ffffff" font-weight="600">Refresh</text>
            <line x1="244" y1="262" x2="784" y2="262" stroke="#0f172a" stroke-width="2" />

            <!-- article cards: two columns -->
            <g v-for="c in cards" :key="c.n">
                <rect :x="c.x" :y="c.y" width="256" height="108" rx="10" fill="#ffffff" stroke="#e5e7eb" />
                <circle :cx="c.x + 18" :cy="c.y + 20" r="7" fill="#f1f5f9" />
                <text :x="c.x + 18" :y="c.y + 23" text-anchor="middle" font-family="sans-serif" font-size="9" fill="#64748b" font-weight="600">{{ c.n }}</text>
                <text :x="c.x + 34" :y="c.y + 23" font-family="sans-serif" font-size="9" fill="#94a3b8">{{ c.src }}</text>
                <rect :x="c.x + 16" :y="c.y + 36" width="166" height="9" rx="3" fill="#0f172a" opacity="0.85" />
                <rect :x="c.x + 16" :y="c.y + 50" width="132" height="9" rx="3" fill="#0f172a" opacity="0.85" />
                <rect :x="c.x + 16" :y="c.y + 70" width="216" height="6" rx="3" fill="#cbd5e1" />
                <rect :x="c.x + 16" :y="c.y + 82" width="176" height="6" rx="3" fill="#cbd5e1" />
                <text :x="c.x + 16" :y="c.y + 100" font-family="sans-serif" font-size="10" fill="#2563eb" font-weight="600">Read more →</text>
            </g>
        </svg>
        <figcaption v-if="caption" class="bg-white px-4 py-2 text-center text-xs text-gray-500">
            {{ caption }}
        </figcaption>
    </figure>
</template>
