<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const values = [
    ['You choose the news', 'No infinite feed, no algorithmic rabbit holes. You pick the topics; we bring the headlines.'],
    ['Signal over noise', 'We surface what people are actually reading, then get out of your way.'],
    ['Respect for your time', 'A finite, finishable feed — read what matters and get on with your day.'],
    ['Privacy first', 'We never sell your data. Your topics are yours.'],
];
</script>

<template>
    <Head title="About NewsFlow" />

    <PublicLayout>
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="font-serif text-4xl font-bold tracking-tight text-ink sm:text-5xl">
                About NewsFlow
            </h1>

            <p class="mt-6 text-lg text-gray-700">
                NewsFlow exists to give you back control of your news. Modern feeds are
                built to maximize time spent, not time well spent — endless scrolling
                through stories you never asked for. We think there’s a better way.
            </p>

            <p class="mt-4 text-gray-700">
                With NewsFlow, you build your own newsroom. Choose the topics you
                actually care about — world news, your team, an industry, a hobby —
                and every morning we gather the day’s most popular articles on each
                one. Twelve headlines per topic, a short summary, and a link to the
                full story. That’s it. No clutter, no doomscrolling, no topics you
                didn’t pick.
            </p>

            <h2 class="mt-12 font-serif text-2xl font-bold text-ink">What we believe</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <div v-for="(v, i) in values" :key="i" class="rounded-xl border border-gray-200 bg-white p-5">
                    <h3 class="font-semibold text-ink">{{ v[0] }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ v[1] }}</p>
                </div>
            </div>

            <h2 class="mt-12 font-serif text-2xl font-bold text-ink">Built by Moon Whale Media</h2>
            <p class="mt-4 text-gray-700">
                NewsFlow is a Moon Whale Media product, built with the same care as our
                other apps. It runs on the web today, with native iOS and Android apps
                on the way — all sharing one account so your newspaper follows you
                everywhere.
            </p>

            <div class="mt-12 rounded-2xl bg-ink p-8 text-center">
                <h2 class="font-serif text-2xl font-bold text-white">Build your newsroom today</h2>
                <p class="mx-auto mt-2 max-w-lg text-gray-300">Start free with two topics. Go Pro any time for unlimited.</p>
                <Link :href="route('register')" class="mt-5 inline-block rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700">
                    Get started free
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
