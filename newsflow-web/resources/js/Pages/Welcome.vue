<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const pricing = computed(() => page.props.pricing ?? {});

const steps = [
    {
        n: '1',
        title: 'Pick your topics',
        body: 'World News, your team, a hobby, a company you watch — anything. Type it in plain English.',
    },
    {
        n: '2',
        title: 'We scour the web at 6 AM',
        body: 'Every morning NewsFlow finds the most-read, most-popular stories on each topic from the day before.',
    },
    {
        n: '3',
        title: 'Read only what you care about',
        body: 'Up to 12 fresh headlines per topic with a short summary and a one-tap Read More to the full article.',
    },
];

const features = [
    ['Your topics, your order', 'Arrange your personal newspaper exactly how you like it.'],
    ['Always a full feed', 'Each topic keeps 12 articles — new stories push the oldest out.'],
    ['Popularity-ranked', 'We blend news coverage with engagement signals to surface what people are actually reading.'],
    ['No doomscrolling', 'No infinite feed, no topics you didn’t ask for. Just your headlines.'],
    ['Niche-friendly', 'Following something obscure? We keep searching until your feed is full.'],
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
    <Head title="Build your own newsroom" />

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
                            A more customizable Google News
                        </span>
                        <h1
                            class="mt-5 font-serif text-5xl font-bold leading-tight tracking-tight text-white sm:text-6xl"
                        >
                            Build Your Own Newsroom
                        </h1>
                        <p class="mt-4 font-serif text-2xl font-semibold text-brand-300">
                            Your news. Only the topics you choose.
                        </p>
                        <p class="mt-6 max-w-xl text-lg text-gray-200">
                            NewsFlow builds you a personal front page. Choose your
                            topics and every morning we deliver the day’s most
                            popular headlines on each one — nothing you didn’t ask for.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <Link
                                :href="route('register')"
                                class="rounded-lg bg-brand-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-brand-700"
                            >
                                Start free — 2 topics
                            </Link>
                            <Link
                                :href="route('pricing')"
                                class="rounded-lg px-6 py-3 text-base font-semibold text-white hover:text-brand-200"
                            >
                                See Pro plans →
                            </Link>
                        </div>
                        <p class="mt-4 text-sm text-gray-300">
                            Free forever for 2 topics. No credit card required.
                        </p>
                    </div>

                    <!-- Sample paper -->
                    <div class="relative">
                        <div
                            class="rounded-3xl border border-white/60 bg-white/95 p-5 shadow-2xl ring-1 ring-black/5 backdrop-blur"
                        >
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="font-serif text-xl font-bold text-ink">
                                    {{ sample.topic }}
                                </h3>
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-brand-700">
                                    Today · 12 stories
                                </span>
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="(a, i) in sample.articles"
                                    :key="i"
                                    class="group/card rounded-2xl border border-gray-100 bg-gradient-to-br from-white to-brand-50/70 p-4 transition duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md"
                                >
                                    <p class="font-serif text-base font-semibold leading-snug text-ink">
                                        {{ a[0] }}
                                    </p>
                                    <p class="mt-1.5 line-clamp-2 text-sm text-gray-500">
                                        {{ a[2] }}
                                    </p>
                                    <div class="mt-3 flex items-center justify-between">
                                        <span class="text-xs font-medium text-gray-400">{{ a[1] }}</span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-brand-600 to-brand-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition group-hover/card:from-brand-700 group-hover/card:to-brand-800">
                                            Read more
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="absolute -right-4 -top-4 -z-10 h-full w-full rounded-3xl bg-brand-500/30"
                        ></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="border-y border-gray-100 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <h2 class="text-center font-serif text-3xl font-bold text-ink">
                    How NewsFlow works
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
                Everything you need, nothing you don’t
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

        <!-- CTA -->
        <section class="bg-gradient-to-br from-brand-600 to-brand-800">
            <div class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8">
                <h2 class="font-serif text-3xl font-bold text-white sm:text-4xl">
                    Build your newsroom today
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
                        Get started free
                    </Link>
                    <Link
                        :href="route('pricing')"
                        class="rounded-lg bg-white/10 px-6 py-3 text-base font-semibold text-white ring-1 ring-inset ring-white/30 hover:bg-white/20"
                    >
                        Compare plans
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
