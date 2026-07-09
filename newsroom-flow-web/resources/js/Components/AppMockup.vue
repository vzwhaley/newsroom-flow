<script setup>
/**
 * A lightweight, self-contained "browser screenshot" illustration of the
 * NewsroomFlow dashboard, used on the How-to-Use page. It mirrors the current
 * layout: the real logo lockup (newspaper mark + NewsroomFlow™ wordmark +
 * "by moon whale media, llc" tagline), a dark left sidebar (Local News +
 * topics), the AI Daily Briefing card, a topic masthead with Unread-Only /
 * Mark-All-Read / Refresh, and the two-column article grid. Real screenshots can
 * replace this later by swapping the <svg> for an <img>.
 */
defineProps({
    caption: { type: String, default: null },
});

// Two-column article grid (4 cards) — top-left origin per card.
const cards = [
    { n: 1, src: 'Global Wire', x: 244, y: 290 },
    { n: 2, src: 'World Desk', x: 520, y: 290 },
    { n: 3, src: 'Newsline', x: 244, y: 412 },
    { n: 4, src: 'The Dispatch', x: 520, y: 412 },
];
</script>

<template>
    <figure class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
        <svg viewBox="0 0 800 540" class="w-full" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="NewsroomFlow dashboard illustration: the NewsroomFlow logo, a dark topic sidebar, a daily briefing card, and a two-column grid of ranked articles.">
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
            <rect width="800" height="540" fill="#f8fafc" />
            <rect width="800" height="40" fill="#ffffff" />
            <line x1="0" y1="40" x2="800" y2="40" stroke="#e5e7eb" />
            <circle cx="20" cy="20" r="5" fill="#f87171" />
            <circle cx="38" cy="20" r="5" fill="#fbbf24" />
            <circle cx="56" cy="20" r="5" fill="#34d399" />
            <rect x="84" y="11" width="360" height="18" rx="9" fill="#f1f5f9" />
            <text x="98" y="24" font-family="sans-serif" font-size="11" fill="#94a3b8">newsroomflow.test/dashboard</text>

            <!-- ===================== APP NAV (logo lockup + links) ===================== -->
            <rect x="0" y="40" width="800" height="56" fill="#ffffff" />
            <line x1="0" y1="96" x2="800" y2="96" stroke="#e5e7eb" />
            <!-- real newspaper logo mark (from ApplicationLogo.vue) -->
            <g transform="translate(20,53) scale(1.25)" fill="none" stroke="#2563eb" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
            </g>
            <!-- wordmark + tagline -->
            <text x="58" y="70" font-family="Georgia, serif" font-size="16" font-weight="700" fill="#0f172a">Newsroom<tspan fill="#2563eb">Flow</tspan><tspan font-size="9" dy="-6">™</tspan></text>
            <text x="59" y="85" font-family="Figtree, sans-serif" font-size="9" fill="#6b7280">by <tspan font-family="Spantaran, cursive" font-size="10.5" fill="#1e3a8a">moon whale media, llc</tspan></text>
            <!-- nav links (clear of the lockup) -->
            <text x="234" y="72" font-family="sans-serif" font-size="11" fill="#64748b">Search</text>
            <text x="290" y="72" font-family="sans-serif" font-size="11" fill="#64748b">Saved</text>
            <text x="338" y="72" font-family="sans-serif" font-size="11" fill="#64748b">Archive</text>
            <text x="394" y="72" font-family="sans-serif" font-size="11" fill="#64748b">Billing</text>
            <rect x="694" y="58" width="90" height="20" rx="10" fill="#eff6ff" />
            <text x="706" y="72" font-family="sans-serif" font-size="10" fill="#1d4ed8" font-weight="600">Pro · Lifetime</text>

            <!-- ===================== PAGE HEADER BAND ===================== -->
            <rect x="0" y="96" width="800" height="44" fill="#ffffff" />
            <line x1="0" y1="140" x2="800" y2="140" stroke="#e5e7eb" />
            <text x="20" y="119" font-family="Georgia, serif" font-size="17" font-weight="700" fill="#0f172a">My NewsroomFlow™</text>
            <text x="20" y="133" font-family="sans-serif" font-size="10" fill="#94a3b8">Unlimited Topics</text>
            <rect x="600" y="107" width="96" height="20" rx="10" fill="#fff7ed" stroke="#fed7aa" />
            <text x="612" y="121" font-family="sans-serif" font-size="10" fill="#c2410c" font-weight="600">🔥 3-day streak</text>
            <text x="784" y="121" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">Tue, Jul 7, 2026</text>

            <!-- ===================== LEFT SIDEBAR (dark) ===================== -->
            <rect x="16" y="156" width="208" height="368" rx="14" fill="#1e293b" />
            <text x="34" y="182" font-family="sans-serif" font-size="12" fill="#f1f5f9" font-weight="600">📍 Local News</text>
            <text x="206" y="182" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">1</text>
            <path d="M36 197 l5 5 l5 -5" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <text x="52" y="204" font-family="sans-serif" font-size="11" fill="#cbd5e1">Illinois</text>
            <text x="206" y="204" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">1</text>
            <rect x="28" y="212" width="184" height="22" rx="6" fill="#2563eb" />
            <text x="64" y="227" font-family="sans-serif" font-size="11" fill="#ffffff" font-weight="600">Springfield</text>

            <line x1="28" y1="248" x2="212" y2="248" stroke="#334155" stroke-width="1" />

            <text x="34" y="266" font-family="sans-serif" font-size="12" fill="#e2e8f0" font-weight="600">All Topics</text>
            <text x="206" y="266" text-anchor="end" font-family="sans-serif" font-size="10" fill="#94a3b8">4</text>

            <rect x="28" y="276" width="184" height="22" rx="6" fill="#2563eb" />
            <text x="40" y="291" font-family="sans-serif" font-size="11" fill="#c7d2fe">⋮⋮</text>
            <text x="58" y="291" font-family="sans-serif" font-size="12" fill="#ffffff" font-weight="600">World News</text>
            <path d="M36 311 l5 5 l5 -5" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            <text x="58" y="318" font-family="sans-serif" font-size="12" fill="#cbd5e1">Technology</text>
            <text x="74" y="340" font-family="sans-serif" font-size="11" fill="#94a3b8">↳ OpenAI</text>
            <text x="58" y="364" font-family="sans-serif" font-size="12" fill="#cbd5e1">Business</text>
            <text x="58" y="388" font-family="sans-serif" font-size="12" fill="#cbd5e1">Sports</text>

            <!-- ===================== MAIN CONTENT ===================== -->
            <!-- AI daily briefing card -->
            <rect x="244" y="156" width="540" height="76" rx="14" fill="url(#briefGrad)" stroke="#dbeafe" />
            <rect x="244" y="156" width="540" height="4" fill="url(#accentGrad)" />
            <path d="M264 175 L257 190 L263 190 L261 201 L272 185 L265 185 Z" fill="#2563eb" />
            <text x="282" y="188" font-family="Georgia, serif" font-size="13" font-weight="700" fill="#0f172a">Your Daily Briefing — Today Across Your Newsroom</text>
            <text x="262" y="208" font-family="sans-serif" font-size="9.5" fill="#4b5563">Markets steadied on upbeat jobs data, the EU advanced its AI rules, and both teams you follow won</text>
            <text x="262" y="221" font-family="sans-serif" font-size="9.5" fill="#4b5563">overnight — here's the one-minute version across your whole newsroom.</text>

            <!-- topic masthead: World News + controls -->
            <text x="244" y="268" font-family="Georgia, serif" font-size="18" font-weight="700" fill="#0f172a">World News</text>
            <rect x="556" y="253" width="82" height="20" rx="10" fill="#ffffff" stroke="#e5e7eb" />
            <circle cx="567" cy="263" r="4" fill="#cbd5e1" />
            <text x="576" y="266" font-family="sans-serif" font-size="9" fill="#4b5563">Unread Only</text>
            <text x="718" y="266" text-anchor="end" font-family="sans-serif" font-size="9" fill="#4b5563">Mark All Read</text>
            <rect x="726" y="253" width="58" height="20" rx="6" fill="#2563eb" />
            <text x="755" y="266" text-anchor="middle" font-family="sans-serif" font-size="9" fill="#ffffff" font-weight="600">Refresh</text>
            <line x1="244" y1="278" x2="784" y2="278" stroke="#0f172a" stroke-width="2" />

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
