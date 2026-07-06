<script setup>
import AdSlot from '@/Components/AdSlot.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const pricing = computed(() => page.props.pricing ?? {});
const freeTopics = computed(() => pricing.value.free_topics ?? 2);

const open = ref(0);
function toggle(i) {
    open.value = open.value === i ? -1 : i;
}

const faqs = computed(() => [
    {
        q: 'What is NewsFlow™?',
        a: 'NewsFlow™ is a personalized news reader. You choose the topics you care about, and each morning we gather the day’s most popular articles on each one — like a more customizable Google News that only shows what you asked for.',
    },
    {
        q: 'How does NewsFlow™ decide what’s “most popular”?',
        a: 'True page-view counts are private to publishers, so we approximate popularity by blending news coverage with public engagement signals (like Hacker News). It’s a strong proxy for what people are actually reading, not literal readership numbers.',
    },
    {
        q: 'How many topics can I follow?',
        a: `Free accounts can follow up to ${freeTopics.value} topics. Any Pro plan gives you unlimited topics.`,
    },
    {
        q: 'When does my feed update?',
        a: 'Once a day at the hour you choose, in your timezone (default 6 AM). New stories are added to the top and the oldest drop off, so each topic always has a full set of articles. You can also refresh any topic on demand.',
    },
    {
        q: 'What if there aren’t 12 fresh articles on my niche topic?',
        a: 'We keep your existing articles so you always have a full feed, and add whatever genuinely new stories we find. Some niche topics won’t fully update every day — that’s expected.',
    },
    {
        q: 'Do you republish full articles?',
        a: 'No. We show the headline, a short summary, and a link. Read More always takes you to the original article on the publisher’s site.',
    },
    {
        q: 'What are the Pro plans?',
        a: `Pro Monthly ($${pricing.value.monthly ?? '4.99'}/mo), Pro Yearly ($${pricing.value.annual ?? '49.99'}/yr), and Pro Lifetime ($${pricing.value.lifetime ?? '149.99'} one-time). All unlock unlimited topics, saved articles, keyword mutes, a daily email digest, and more.`,
    },
    {
        q: 'What does Pro Lifetime include?',
        a: 'A one-time payment unlocks every Pro feature in the current major version of NewsFlow™ with no recurring billing. A future major version may be a separate purchase, with a loyalty discount for Lifetime owners.',
    },
    {
        q: 'Can I cancel anytime?',
        a: 'Yes. Manage or cancel your subscription anytime from the Billing page via Stripe. You keep Pro access through the end of your paid period.',
    },
    {
        q: 'Is there a mobile app?',
        a: 'Native iOS and Android apps are coming soon and will sync with your web account. For now, NewsFlow™ works great in any mobile browser.',
    },
    {
        q: 'How do you handle my data?',
        a: 'We never sell your data. We store your account, topics, and subscription status (via Stripe). See our Privacy Policy for details.',
    },
]);

// FAQPage structured data → eligible for Google's FAQ rich result.
const seoJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.value.map((f) => ({
        '@type': 'Question',
        name: f.q,
        acceptedAnswer: { '@type': 'Answer', text: f.a },
    })),
}));
</script>

<template>
    <SeoHead
        title="FAQ"
        description="Answers to common questions about NewsFlow — how we find stories, topic limits, refresh timing, Pro plans, billing, and how we handle your data."
        path="/faq"
        :json-ld="seoJsonLd"
    />

    <PublicLayout>
        <div class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <AdSlot slot="faq_top" format="horizontal" />
        </div>
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="font-serif text-4xl font-bold tracking-tight text-ink sm:text-5xl">
                    Frequently Asked Questions
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-600">
                    Everything you need to know about NewsFlow.
                </p>
            </div>

            <div class="mt-12 divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white">
                <div v-for="(f, i) in faqs" :key="i">
                    <button @click="toggle(i)" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left">
                        <span class="font-semibold text-ink">{{ f.q }}</span>
                        <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': open === i }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div v-show="open === i" class="px-6 pb-5 text-gray-600">
                        {{ f.a }}
                    </div>
                </div>
            </div>

            <div class="mt-12 text-center">
                <p class="text-gray-600">Still have a question?</p>
                <a href="mailto:vzwhaley4709@gmail.com" class="font-semibold text-brand-600">Email Us</a>
                <span class="text-gray-400"> · </span>
                <Link :href="route('how-to-use')" class="font-semibold text-brand-600">Read the Guide</Link>
            </div>
        </section>
    </PublicLayout>
</template>
