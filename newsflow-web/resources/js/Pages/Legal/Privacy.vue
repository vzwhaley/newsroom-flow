<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';

const updated = 'June 16, 2026';
</script>

<template>
    <SeoHead
        title="Privacy Policy"
        description="NewsFlow's privacy policy — what we collect, how news content and advertising work, the service providers we use, and your choices."
        path="/privacy"
    />

    <PublicLayout>
        <article class="prose prose-slate mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="font-serif text-4xl font-bold text-ink">Privacy Policy</h1>
            <p class="text-sm text-gray-500">Last updated {{ updated }}</p>

            <p class="mt-6 text-gray-700">
                NewsFlow™ (“we”, “us”), a Moon Whale Media product, respects your
                privacy. This policy explains what we collect, why, and your
                choices. We do not sell your personal information.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">What We Collect</h2>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-gray-700">
                <li><strong>Account information</strong> — your name, email, and password (stored hashed). If you sign in with Google, Apple, or Discord, we receive a provider ID and your email.</li>
                <li><strong>Your topics</strong> — the subjects you choose to follow, so we can build your feed.</li>
                <li><strong>Billing</strong> — handled by Stripe. We store a Stripe customer ID and subscription status; we never see or store your full card number.</li>
                <li><strong>Technical data</strong> — basic logs needed to operate and secure the service.</li>
            </ul>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">How News Content Works</h2>
            <p class="mt-3 text-gray-700">
                NewsFlow™ aggregates publicly available headlines, short
                descriptions, and links from third-party news APIs and public
                engagement sources. We link out to the original publisher for the
                full article — we do not republish full article text. Article
                content belongs to its respective publishers.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">How We Use Your Data</h2>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-gray-700">
                <li>To build and deliver your personalized daily feed.</li>
                <li>To manage your account, subscription, and security.</li>
                <li>To send transactional email (verification, password resets, billing, and — if enabled — your daily digest).</li>
            </ul>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Service Providers</h2>
            <p class="mt-3 text-gray-700">
                We share limited data only with providers that help us operate:
                Stripe (payments), our email provider (transactional mail), and
                the third-party news/data APIs used to assemble feeds. Each is
                bound by their own terms and privacy obligations.
            </p>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Your Choices</h2>
            <ul class="mt-3 list-disc space-y-2 pl-6 text-gray-700">
                <li>Access, update, or delete your account at any time from your profile.</li>
                <li>Deleting your account removes your topics and feed data.</li>
                <li>Manage or cancel your subscription from the Billing page.</li>
            </ul>

            <h2 class="mt-8 font-serif text-2xl font-semibold text-ink">Contact</h2>
            <p class="mt-3 text-gray-700">
                Questions? Email
                <a href="mailto:vzwhaley4709@gmail.com" class="text-brand-600">vzwhaley4709@gmail.com</a>.
            </p>
        </article>
    </PublicLayout>
</template>
