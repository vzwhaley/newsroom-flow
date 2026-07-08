<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const updated = 'June 16, 2026';
const page = usePage();
const pricing = computed(() => page.props.pricing ?? {});
</script>

<template>
    <SeoHead
        title="Terms of Service"
        description="NewsroomFlow's terms of service — your account, subscriptions and billing, news content and third parties, acceptable use, and disclaimers."
        path="/terms"
    />

    <PublicLayout>
        <article class="prose prose-slate mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="font-serif text-4xl font-bold text-ink">Terms of Service</h1>
            <p class="text-sm text-gray-500">Last updated {{ updated }}</p>

            <p class="mt-6 text-gray-700">
                These terms govern your use of NewsroomFlow™, a Moon Whale Media
                product. By creating an account you agree to them.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">The Service</h2>
            <p class="mt-3 text-gray-700">
                NewsroomFlow™ lets you follow topics and receive a daily feed of
                popular headlines on each, with links to the original articles.
                Free accounts may follow up to {{ pricing.free_topics ?? 2 }}
                topics; Pro accounts are unlimited.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Subscriptions &amp; Billing</h2>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-gray-700">
                <li><strong>Pro Monthly</strong> (${{ pricing.monthly ?? '4.99' }}/month) and <strong>Pro Yearly</strong> (${{ pricing.annual ?? '49.99' }}/year) renew automatically until cancelled. You can cancel any time from the Billing page; access continues through the end of the paid period.</li>
                <li><strong>Pro Lifetime</strong> (${{ pricing.lifetime ?? '149.99' }}, one-time) unlocks every Pro feature in the <strong>current major version</strong> of NewsroomFlow™ with no recurring billing. A future major version may be offered as a separate purchase (existing Lifetime holders receive a loyalty discount).</li>
                <li>Payments are processed by Stripe. Taxes may apply based on your location.</li>
            </ul>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Refunds</h2>
            <p class="mt-3 text-gray-700">
                If a Lifetime purchase is refunded, the associated Pro access is
                removed. Subscription charges already incurred are generally
                non-refundable except where required by law.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">News Content &amp; Third Parties</h2>
            <p class="mt-3 text-gray-700">
                Headlines, summaries, and links are aggregated from third-party
                sources and belong to their respective publishers. NewsroomFlow™ does
                not author or guarantee the accuracy of third-party articles and
                is not responsible for external content. “Most popular” reflects
                a blend of public engagement signals, not literal readership
                figures.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Acceptable Use</h2>
            <p class="mt-3 text-gray-700">
                Don’t abuse, scrape, resell, or attempt to disrupt the service,
                and don’t use it unlawfully. We may suspend accounts that do.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Disclaimer &amp; Liability</h2>
            <p class="mt-3 text-gray-700">
                The service is provided “as is.” To the maximum extent permitted
                by law, Moon Whale Media is not liable for indirect or
                consequential damages arising from your use of NewsroomFlow.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Contact</h2>
            <p class="mt-3 text-gray-700">
                Questions about these terms? Email
                <a href="mailto:vzwhaley4709@gmail.com" class="text-brand-600">vzwhaley4709@gmail.com</a>.
            </p>
        </article>
    </PublicLayout>
</template>
