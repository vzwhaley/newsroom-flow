<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import AppMockup from '@/Components/AppMockup.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const pricing = computed(() => page.props.pricing ?? {});
const freeTopics = computed(() => pricing.value.free_topics ?? 2);

const steps = [
    { n: '1', title: 'Create Your Free Account', body: 'Sign up with email, or one tap with Google, Apple, or Discord. Confirm your email and you’re in.' },
    { n: '2', title: 'Add Your First Topics', body: 'Type any subject in plain English — “World News”, your sports team, a company you watch, a hobby. We immediately gather a full feed.' },
    { n: '3', title: 'Read Your Personal Newspaper', body: 'Each topic shows up to 12 of the day’s most popular articles. Tap Read More to open the full story at the source.' },
    { n: '4', title: 'Make It Yours', body: 'Reorder topics, save articles for later, mute words you don’t want, and pick when your feed refreshes each day.' },
];

const sections = [
    {
        id: 'topics',
        title: 'Adding & Managing Topics',
        points: [
            'Type a topic into the “Add a topic” bar on your dashboard and press Add — or tap a suggestion chip.',
            `Free accounts can follow up to ${freeTopics} topics. Pro accounts are unlimited.`,
            'Use the move ↑ / ↓ buttons on a topic to arrange your newspaper in the order you like.',
            'Tap the ✕ on a topic to stop following it and remove its feed.',
        ],
    },
    {
        id: 'reading',
        title: 'Reading Your Feed',
        points: [
            'Articles are ranked by popularity — a blend of news coverage and public engagement signals like Hacker News.',
            'Each card shows the headline, source, a short summary, and a Read More button that opens the original article in a new tab.',
            'Hit Refresh on any topic to pull the latest stories on demand.',
            'Every morning at your chosen time, NewsFlow™ automatically gathers fresh stories and keeps each topic at a full 12.',
        ],
    },
    {
        id: 'pro',
        title: 'Pro Power Features',
        pro: true,
        points: [
            'TL;DR this: tap “TL;DR this” on any article for an instant AI summary, without leaving NewsFlow.',
            'Watchlist: add keywords (e.g. “merger”, “recall”) and matching stories from any topic get pinned to the top of your feed.',
            'Search: search across every topic you follow and everything you’ve saved.',
            'Archive: stories that rotate out of your feeds are kept in your archive so you never miss a day.',
            'Save / read later: bookmark any article to your Saved page — it stays even after the feed rotates.',
            'Mute keywords & block publishers: hide stories with words you don’t care about, or from sources you don’t trust.',
            'Unlimited topics & subtopics, a customizable daily email digest, and refresh at the exact time you want.',
        ],
    },
    {
        id: 'preferences',
        title: 'Your Refresh Time & Digest',
        points: [
            'Open Profile → News preferences to choose the hour your feed refreshes and your timezone.',
            'Turn on the daily email digest to get a “Your NewsFlow™ is ready” email with your top headlines each morning.',
        ],
    },
    {
        id: 'billing',
        title: 'Upgrading & Billing',
        points: [
            `Go Pro from the Billing page: Monthly ($${pricing.monthly ?? '4.99'}), Yearly ($${pricing.annual ?? '49.99'}), or Lifetime ($${pricing.lifetime ?? '149.99'}, one-time).`,
            'Payments are handled securely by Stripe. Manage or cancel any time from the Stripe billing portal.',
            'Lifetime unlocks every Pro feature in the current version with no recurring billing.',
        ],
    },
];
</script>

<template>
    <SeoHead
        title="How to Use NewsFlow"
        description="How NewsFlow works: pick your topics, and every morning we gather the day's most popular headlines on each into your own personal newspaper."
        path="/how-to-use"
    />

    <PublicLayout>
        <div class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <AdSlot slot="how_to_use_top" format="horizontal" />
        </div>
        <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-serif text-4xl font-bold tracking-tight text-ink sm:text-5xl">
                    How to Use NewsFlow™
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600">
                    NewsFlow™ builds you a personal newspaper from only the topics you
                    choose. Here’s how to get the most out of it.
                </p>
            </div>

            <div class="mt-10">
                <AppMockup caption="Your dashboard: each topic shows the day’s 12 most popular articles." />
            </div>

            <!-- Quick start steps -->
            <h2 class="mt-16 font-serif text-2xl font-bold text-ink">Quick Start</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <div v-for="step in steps" :key="step.n" class="flex gap-4 rounded-xl border border-gray-200 bg-white p-5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                        {{ step.n }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-ink">{{ step.title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ step.body }}</p>
                    </div>
                </div>
            </div>

            <!-- Detailed sections -->
            <div class="mt-16 space-y-12">
                <section v-for="s in sections" :key="s.id" :id="s.id">
                    <h2 class="flex items-center gap-2 font-serif text-2xl font-bold text-ink">
                        {{ s.title }}
                        <span v-if="s.pro" class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">Pro</span>
                    </h2>
                    <ul class="mt-4 space-y-3">
                        <li v-for="(p, i) in s.points" :key="i" class="flex gap-3 text-gray-700">
                            <svg class="mt-1 h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <span>{{ p }}</span>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- Mobile apps -->
            <section class="mt-16 rounded-2xl border border-gray-200 bg-gray-50 p-8">
                <div class="flex items-center gap-3">
                    <h2 class="font-serif text-2xl font-bold text-ink">NewsFlow™ on Mobile</h2>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Coming Soon</span>
                </div>
                <p class="mt-3 text-gray-600">
                    Native NewsFlow™ apps for iOS and Android are on the way. Because
                    NewsFlow™ is built API-first, your account, topics, saved articles,
                    and preferences will sync automatically across the web and both apps —
                    sign in once and your newspaper follows you everywhere.
                </p>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li v-for="f in ['Your topics & feeds, synced live', 'Push notifications when your feed is ready', 'Read later, offline-friendly', 'The same account as the website']" :key="f" class="flex gap-2 text-sm text-gray-700">
                        <span class="text-brand-600">✓</span> {{ f }}
                    </li>
                </ul>
                <p class="mt-4 text-sm text-gray-500">
                    We’ll email you when the apps launch. In the meantime, NewsFlow™ works
                    great in any mobile browser.
                </p>
            </section>

            <!-- CTA -->
            <div class="mt-16 text-center">
                <Link :href="route('register')" class="inline-block rounded-lg bg-brand-600 px-6 py-3 text-base font-semibold text-white hover:bg-brand-700">
                    Start Free — {{ freeTopics }} Topics
                </Link>
                <p class="mt-3 text-sm text-gray-500">
                    Questions? See the <Link :href="route('faq')" class="text-brand-600">FAQ</Link>.
                </p>
            </div>
        </section>
    </PublicLayout>
</template>
