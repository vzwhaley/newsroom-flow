<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const pricing = computed(() => page.props.pricing ?? {});
const user = computed(() => page.props.auth?.user);
const freeTopics = computed(() => pricing.value.free_topics ?? 2);

// Where each CTA points: logged-in users go to billing, guests register.
const ctaHref = computed(() => (user.value ? route('billing') : route('register')));

const proFeatures = [
    'Unlimited topics',
    'Daily refresh at your chosen time',
    '12 popularity-ranked articles per topic',
    'Save articles to read later',
    'AI “TL;DR this” article summaries',
    'Keyword watchlist across all feeds',
    'Search your feeds & saved articles',
    'Article archive — never miss a day',
    'Mute keywords & block publishers',
    'Daily email digest (per-topic, new-only)',
    'Reorder your personal newspaper',
    'Web, Android & iOS (same account)',
];
</script>

<template>
    <SeoHead
        title="Pricing"
        description="NewsroomFlow is free forever for 2 topics. Go Pro for unlimited topics, AI TL;DR summaries, search, archive, and an ad-free experience from $4.99/mo."
        path="/pricing"
    />

    <PublicLayout>
        <div class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <AdSlot slot="pricing_top" format="horizontal" />
        </div>
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-serif text-4xl font-bold tracking-tight text-ink sm:text-5xl">
                    Simple Pricing for Your Newsroom
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600">
                    Start free with {{ freeTopics }} topics. Upgrade for unlimited
                    topics — pay monthly, save with yearly, or own it for life.
                </p>
            </div>

            <h2 class="sr-only">Plans</h2>
            <div class="mt-14 grid gap-6 lg:grid-cols-4">
                <!-- FREE -->
                <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-ink">Free</h3>
                    <p class="mt-2 flex items-baseline gap-1">
                        <span class="font-serif text-4xl font-bold text-ink">$0</span>
                        <span class="text-sm text-gray-500">forever</span>
                    </p>
                    <p class="mt-3 text-sm text-gray-600">
                        Perfect for keeping an eye on a couple of topics.
                    </p>
                    <ul class="mt-6 flex-1 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2">
                            <span class="text-brand-600" aria-hidden="true">✓</span>
                            Up to {{ freeTopics }} topics
                        </li>
                        <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">✓</span> Daily 6 AM refresh</li>
                        <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">✓</span> 12 articles per topic</li>
                        <li class="flex gap-2"><span class="text-brand-600" aria-hidden="true">✓</span> Web, Android & iOS</li>
                    </ul>
                    <Link
                        :href="user ? route('dashboard') : route('register')"
                        class="mt-6 rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-semibold text-ink hover:bg-gray-50"
                    >
                        {{ user ? 'Go to Dashboard' : 'Get Started Free' }}
                    </Link>
                </div>

                <!-- LIFETIME -->
                <div class="flex flex-col rounded-2xl border border-ink bg-ink p-6 text-white shadow-sm">
                    <h3 class="text-lg font-semibold">Pro Lifetime</h3>
                    <p class="mt-2 flex items-baseline gap-1">
                        <span class="font-serif text-4xl font-bold">${{ pricing.lifetime ?? '149.99' }}</span>
                        <span class="text-sm text-gray-400">once</span>
                    </p>
                    <p class="mt-3 text-sm text-gray-300">
                        Pay once, own Pro for the current version. No recurring billing.
                    </p>
                    <ul class="mt-6 flex-1 space-y-3 text-sm text-gray-200">
                        <li v-for="f in proFeatures" :key="f" class="flex gap-2">
                            <span class="text-brand-300" aria-hidden="true">✓</span> {{ f }}
                        </li>
                        <li class="flex gap-2"><span class="text-brand-300" aria-hidden="true">✓</span> One-time payment</li>
                    </ul>
                    <Link
                        :href="ctaHref"
                        class="mt-6 rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-ink hover:bg-gray-100"
                    >
                        Buy Lifetime
                    </Link>
                </div>

                <!-- YEARLY (highlighted) -->
                <div class="relative flex flex-col rounded-2xl border-2 border-brand-600 bg-white p-6 shadow-lg">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">
                        Best Value
                    </span>
                    <h3 class="text-lg font-semibold text-ink">Pro Yearly</h3>
                    <p class="mt-2 flex items-baseline gap-1">
                        <span class="font-serif text-4xl font-bold text-ink">${{ pricing.annual ?? '49.99' }}</span>
                        <span class="text-sm text-gray-500">/year</span>
                    </p>
                    <p class="mt-3 text-sm text-gray-600">
                        Two months free vs monthly. Unlimited topics.
                    </p>
                    <ul class="mt-6 flex-1 space-y-3 text-sm text-gray-700">
                        <li v-for="f in proFeatures" :key="f" class="flex gap-2">
                            <span class="text-brand-600" aria-hidden="true">✓</span> {{ f }}
                        </li>
                    </ul>
                    <Link
                        :href="ctaHref"
                        class="mt-6 rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-700"
                    >
                        Choose Yearly
                    </Link>
                </div>

                <!-- MONTHLY -->
                <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-ink">Pro Monthly</h3>
                    <p class="mt-2 flex items-baseline gap-1">
                        <span class="font-serif text-4xl font-bold text-ink">${{ pricing.monthly ?? '4.99' }}</span>
                        <span class="text-sm text-gray-500">/month</span>
                    </p>
                    <p class="mt-3 text-sm text-gray-600">Unlimited topics, billed monthly.</p>
                    <ul class="mt-6 flex-1 space-y-3 text-sm text-gray-700">
                        <li v-for="f in proFeatures" :key="f" class="flex gap-2">
                            <span class="text-brand-600" aria-hidden="true">✓</span> {{ f }}
                        </li>
                    </ul>
                    <Link
                        :href="ctaHref"
                        class="mt-6 rounded-lg border border-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-brand-700 hover:bg-brand-50"
                    >
                        Choose Monthly
                    </Link>
                </div>
            </div>

            <p class="mt-10 text-center text-sm text-gray-500">
                Prices in USD. Subscriptions renew automatically and can be
                cancelled any time. Lifetime covers the current major version.
            </p>
        </section>
    </PublicLayout>
</template>
