<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    plan: String,
    tier: { type: String, default: null },
    subscription: { type: Object, default: null },
    invoices: { type: Array, default: () => [] },
    stripeConfigured: Boolean,
    pricesConfigured: Boolean,
    hasLifetime: Boolean,
    prices: { type: Object, default: () => ({}) },
});

const page = usePage();
const display = computed(() => page.props.pricing ?? {});
const flash = computed(() => page.props.flash ?? {});
const isPro = computed(() => props.plan === 'pro');

const tierLabel = computed(() => ({
    lifetime: 'Pro · Lifetime',
    yearly: 'Pro · Yearly',
    monthly: 'Pro · Monthly',
}[props.tier] ?? (isPro.value ? 'Pro' : 'Free')));

function subscribe(plan) {
    router.post(route('billing.checkout'), { plan });
}
function buyLifetime() {
    router.post(route('billing.lifetime'));
}
function manage() {
    router.post(route('billing.portal'));
}

function money(cents) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format((cents ?? 0) / 100);
}
</script>

<template>
    <Head title="Billing" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="font-serif text-2xl font-bold text-ink">Billing & Subscription</h1>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Flash -->
            <div v-if="flash.success" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ flash.success }}
            </div>
            <div v-if="$page.props.errors?.billing" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $page.props.errors.billing }}
            </div>

            <!-- Current plan -->
            <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500">Current Plan</p>
                        <p class="mt-1 font-serif text-2xl font-bold text-ink">{{ tierLabel }}</p>
                        <p v-if="isPro" class="mt-1 text-sm text-gray-500">Unlimited topics unlocked.</p>
                        <p v-else class="mt-1 text-sm text-gray-500">
                            Up to {{ display.free_topics ?? 2 }} topics. Upgrade for unlimited.
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold"
                        :class="isPro ? 'bg-brand-50 text-brand-700' : 'bg-gray-100 text-gray-600'"
                    >
                        {{ isPro ? 'Active' : 'Free' }}
                    </span>
                </div>

                <!-- Subscription details + manage -->
                <div v-if="subscription" class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-600">
                    <p v-if="subscription.canceled && subscription.ends_at">
                        Your subscription is cancelled and access ends on
                        <strong>{{ subscription.ends_at }}</strong>.
                    </p>
                    <p v-else-if="subscription.current_period_end">
                        Renews on <strong>{{ subscription.current_period_end }}</strong>.
                    </p>
                    <button
                        @click="manage"
                        class="mt-3 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-ink hover:bg-gray-50"
                    >
                        Manage Subscription
                    </button>
                </div>
                <div v-else-if="hasLifetime" class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-600">
                    You own <strong>Pro Lifetime</strong> — no recurring billing. Thank you!
                </div>
            </div>

            <!-- Stripe not configured notice -->
            <div
                v-if="!stripeConfigured || !pricesConfigured"
                class="mb-8 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                Payments aren’t fully configured yet. Add your Stripe keys and
                Price IDs to <code>.env</code> to enable checkout.
            </div>

            <!-- Upgrade options (hidden if already lifetime) -->
            <div v-if="!hasLifetime">
                <h2 class="mb-4 font-serif text-xl font-bold text-ink">
                    {{ isPro ? 'Change Your Plan' : 'Upgrade to Pro' }}
                </h2>
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Monthly -->
                    <div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-ink">Pro Monthly</h3>
                        <p class="mt-2 font-serif text-3xl font-bold text-ink">
                            ${{ display.monthly ?? '4.99' }}<span class="text-sm font-normal text-gray-500">/mo</span>
                        </p>
                        <p class="mt-2 flex-1 text-sm text-gray-600">Unlimited topics, billed monthly.</p>
                        <button
                            @click="subscribe('monthly')"
                            :disabled="!stripeConfigured || !pricesConfigured"
                            class="mt-4 rounded-lg border border-brand-600 px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50 disabled:opacity-50"
                        >
                            Choose Monthly
                        </button>
                    </div>

                    <!-- Yearly -->
                    <div class="relative flex flex-col rounded-2xl border-2 border-brand-600 bg-white p-6 shadow-md">
                        <span class="absolute -top-3 left-6 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">
                            Best Value
                        </span>
                        <h3 class="font-semibold text-ink">Pro Yearly</h3>
                        <p class="mt-2 font-serif text-3xl font-bold text-ink">
                            ${{ display.annual ?? '49.99' }}<span class="text-sm font-normal text-gray-500">/yr</span>
                        </p>
                        <p class="mt-2 flex-1 text-sm text-gray-600">Two months free vs monthly.</p>
                        <button
                            @click="subscribe('annual')"
                            :disabled="!stripeConfigured || !pricesConfigured"
                            class="mt-4 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50"
                        >
                            Choose Yearly
                        </button>
                    </div>

                    <!-- Lifetime -->
                    <div class="flex flex-col rounded-2xl border border-ink bg-ink p-6 text-white shadow-sm">
                        <h3 class="font-semibold">Pro Lifetime</h3>
                        <p class="mt-2 font-serif text-3xl font-bold">
                            ${{ display.lifetime ?? '149.99' }}<span class="text-sm font-normal text-gray-400"> once</span>
                        </p>
                        <p class="mt-2 flex-1 text-sm text-gray-300">Pay once. No recurring billing.</p>
                        <button
                            @click="buyLifetime"
                            :disabled="!stripeConfigured || !prices.lifetime"
                            class="mt-4 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-100 disabled:opacity-50"
                        >
                            Buy Lifetime
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invoices -->
            <div v-if="invoices.length" class="mt-10">
                <h2 class="mb-4 font-serif text-xl font-bold text-ink">Invoices</h2>
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="inv in invoices" :key="inv.id">
                                <td class="px-4 py-3 text-gray-700">{{ inv.date }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ inv.total }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                        {{ inv.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
