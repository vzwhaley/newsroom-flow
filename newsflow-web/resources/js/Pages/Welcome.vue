<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const pricing = computed(() => page.props.pricing ?? {});

const seoJsonLd = computed(() => {
    const site = 'https://newsflow.app';
    return [
        {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'NewsFlow',
            url: site,
            logo: `${site}/favicon.svg`,
            parentOrganization: {
                '@type': 'Organization',
                name: 'Moon Whale Media, LLC',
                url: 'https://moonwhale.media',
            },
        },
        {
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            name: 'NewsFlow',
            url: site,
        },
        {
            '@context': 'https://schema.org',
            '@type': 'SoftwareApplication',
            name: 'NewsFlow',
            applicationCategory: 'NewsApplication',
            operatingSystem: 'Web, iOS, Android',
            offers: [
                { '@type': 'Offer', price: '0', priceCurrency: 'USD', name: 'Free' },
                { '@type': 'Offer', price: String(pricing.value.monthly ?? '4.99'), priceCurrency: 'USD', name: 'Pro Monthly' },
                { '@type': 'Offer', price: String(pricing.value.annual ?? '49.99'), priceCurrency: 'USD', name: 'Pro Yearly' },
                { '@type': 'Offer', price: String(pricing.value.lifetime ?? '149.99'), priceCurrency: 'USD', name: 'Pro Lifetime' },
            ],
        },
    ];
});

const steps = [
    {
        n: '1',
        title: 'Pick Your Topics',
        body: 'World News, your team, a hobby, a company you watch — anything. Type it in plain English.',
    },
    {
        n: '2',
        title: 'We Scour the Web at 6 AM',
        body: 'Every morning NewsFlow™ finds the most-read, most-popular stories on each topic from the day before.',
    },
    {
        n: '3',
        title: 'Read Only What You Care About',
        body: 'Up to 12 fresh headlines per topic with a short summary and a one-tap Read More to the full article.',
    },
];

const features = [
    ['Your Topics, Your Order', 'Arrange your personal newspaper exactly how you like it.'],
    ['Always a Full Feed', 'Each topic keeps 12 articles — new stories push the oldest out.'],
    ['Popularity-Ranked', 'We blend news coverage with engagement signals to surface what people are actually reading.'],
    ['No Doomscrolling', 'No infinite feed, no topics you didn’t ask for. Just your headlines.'],
    ['Niche-Friendly', 'Following something obscure? We keep searching until your feed is full.'],
    ['Web, Android & iOS', 'Built API-first so your topics follow you across every device.'],
];

const sample = {
    topic: 'World News',
    articles: [
        ['Markets steady as central banks signal a pause', 'Global Wire', 'Stocks held firm after policymakers hinted that rate hikes may be over for now.'],
        ['Historic climate accord reached after marathon talks', 'The Beacon', 'Nearly 200 nations agreed to a phased shift away from fossil fuels.'],
        ['What the new trade deal means for everyday prices', 'The Daily Dispatch', 'Tariff cuts could ease costs on electronics, cars, and groceries within months.'],
    ],
};
</script>

<template>
    <SeoHead
        title="Build Your Own Newsroom"
        description="Build your own newsroom. Follow only the topics you care about and get the day's most popular headlines on each — every morning. Free for 2 topics."
        path="/"
        :json-ld="seoJsonLd"
    />

    <PublicLayout>
        <!-- Hero -->
        <section class="relative isolate overflow-hidden">
            <!-- Background hero image + dark overlay for legibility -->
            <div class="absolute inset-0 -z-10">
                <img
                    src="/images/hero-newspaper.jpg"
                    alt=""
                    class="h-full w-full object-cover object-center"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-ink/90 via-ink/75 to-ink/50"></div>
            </div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8 lg:py-32">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <span
                            class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-white ring-1 ring-inset ring-white/20"
                        >
                            Your News — Only Topics You Care About
                        </span>
                        <h1
                            class="mt-5 font-serif text-5xl font-bold leading-tight tracking-tight text-white sm:text-6xl"
                        >
                            Build Your Own Newsroom
                        </h1>
                        <p class="mt-4 font-serif text-2xl font-semibold text-brand-300">
                            Your own customized news topics, every day.
                        </p>
                        <p class="mt-6 max-w-xl text-lg text-gray-200">
                            NewsFlow™ builds you a personal front page. Choose your
                            topics and every morning we deliver the day’s most
                            popular headlines on each one — nothing you didn’t ask for.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <Link
                                :href="route('register')"
                                class="rounded-lg bg-brand-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-brand-700"
                            >
                                Start Free — 2 Topics
                            </Link>
                            <Link
                                :href="route('pricing')"
                                class="rounded-lg bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-green-700"
                            >
                                See Pro Plans →
                            </Link>
                        </div>
                        <p class="mt-4 text-sm text-gray-300">
                            Free forever for 2 topics. No credit card required.
                        </p>
                    </div>

                    <!-- Sample paper — links to the live World News demo feed -->
                    <div class="relative">
                        <Link
                            :href="route('world-news')"
                            class="group/paper block overflow-hidden rounded-3xl bg-gradient-to-br from-white via-brand-50 to-indigo-100 p-5 shadow-2xl ring-1 ring-white/60 transition duration-200 hover:-translate-y-0.5 hover:shadow-brand-500/30 hover:ring-brand-200"
                        >
                            <!-- Header -->
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-indigo-600 text-white shadow-md shadow-brand-500/30">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                                    </span>
                                    <h3 class="font-serif text-xl font-bold text-ink">
                                        {{ sample.topic }}
                                    </h3>
                                </div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-brand-700 ring-1 ring-brand-100 backdrop-blur">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span class="absolute inline-flex h-full w-full motion-safe:animate-ping rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                    </span>
                                    Today · 12 Stories
                                </span>
                            </div>

                            <!-- Article list -->
                            <div class="space-y-3">
                                <div
                                    v-for="(a, i) in sample.articles"
                                    :key="i"
                                    class="group/card relative overflow-hidden rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-100 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-300/40 hover:ring-brand-200"
                                >
                                    <!-- Accent bar -->
                                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-brand-500 via-indigo-500 to-violet-500"></div>
                                    <div class="flex gap-3 pl-2">
                                        <!-- Rank badge -->
                                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-600 to-indigo-600 text-sm font-bold text-white shadow-sm">
                                            {{ i + 1 }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-serif text-base font-semibold leading-snug text-ink transition group-hover/card:text-brand-700">
                                                {{ a[0] }}
                                            </p>
                                            <p class="mt-1 line-clamp-2 text-sm text-gray-500">
                                                {{ a[2] }}
                                            </p>
                                            <div class="mt-3 flex items-center justify-between gap-2">
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-100">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-brand-500 to-indigo-500"></span>
                                                    {{ a[1] }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm shadow-brand-500/20 transition group-hover/card:from-brand-700 group-hover/card:to-indigo-700">
                                                    Read More
                                                    <svg class="h-3.5 w-3.5 transition-transform group-hover/card:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Click-through caption -->
                            <div class="mt-4 flex items-center justify-center gap-1.5 text-sm font-semibold text-brand-700">
                                See the Live World News Feed
                                <svg class="h-4 w-4 transition-transform group-hover/paper:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </div>
                        </Link>
                        <!-- Decorative gradient halo -->
                        <div
                            class="absolute -right-5 -top-5 -z-10 h-full w-full rounded-3xl bg-gradient-to-br from-brand-400/40 via-indigo-400/30 to-violet-400/30 blur-sm"
                        ></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ad (non-Pro visitors) -->
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <AdSlot slot="home_top" format="horizontal" />
        </div>

        <!-- How it works -->
        <section class="border-y border-gray-100 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <h2 class="text-center font-serif text-3xl font-bold text-ink">
                    How NewsFlow™ Works
                </h2>
                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    <div v-for="step in steps" :key="step.n" class="text-center">
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white"
                        >
                            {{ step.n }}
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-ink">{{ step.title }}</h3>
                        <p class="mt-2 text-gray-600">{{ step.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <h2 class="text-center font-serif text-3xl font-bold text-ink">
                Everything You Need, Nothing You Don’t
            </h2>
            <div class="mt-12 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(f, i) in features" :key="i" class="flex gap-3">
                    <svg class="mt-1 h-6 w-6 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <h3 class="font-semibold text-ink">{{ f[0] }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ f[1] }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ad (non-Pro visitors) -->
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <AdSlot slot="home_mid" format="horizontal" />
        </div>

        <!-- CTA -->
        <section class="bg-gradient-to-br from-brand-600 to-brand-800">
            <div class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8">
                <h2 class="font-serif text-3xl font-bold text-white sm:text-4xl">
                    Build Your Newsroom Today
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-brand-100">
                    Start free with 2 topics. Go Pro for unlimited topics from
                    {{ '$' + (pricing.monthly ?? '4.99') }}/mo — or own it for life at
                    {{ '$' + (pricing.lifetime ?? '149.99') }}.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <Link
                        :href="route('register')"
                        class="rounded-lg bg-white px-6 py-3 text-base font-semibold text-brand-700 shadow-sm hover:bg-brand-50"
                    >
                        Get Started Free
                    </Link>
                    <Link
                        :href="route('pricing')"
                        class="rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-inset ring-white/30 hover:bg-white/20"
                    >
                        Compare Plans
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
