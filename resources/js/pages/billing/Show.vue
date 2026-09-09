<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { CalendarClock, CircleCheck, Lock, Sparkles } from '@lucide/vue'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import AppLayout from '@/layouts/AppLayout.vue'
import { cancel, subscribe } from '@/routes/billing'
import type { BreadcrumbItem } from '@/types'

interface PlanOption {
    value: 'monthly' | 'quarterly' | 'annual'
    label: string
    price_cents: number
}

interface Props {
    feature: string
    featureLabel: string
    subscription?: {
        plan: string | null
        status: string | null
        is_active: boolean
        ends_at: string | null
        ends_at_human: string | null
    }
    plans: PlanOption[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Billing' }]

const subscribeForm = useForm({
    feature: props.feature,
    plan: '' as PlanOption['value'] | '',
})

const cancelForm = useForm({})

const hasActiveSubscription = computed(() => props.subscription?.is_active ?? false)
const isCancelled = computed(() => props.subscription?.status === 'cancelled')

// Matches App\Enums\Billing\SubscriptionPlan::duration() — kept in sync manually,
// there's no shared source of truth between backend and frontend for this.
const MONTHS_BY_PLAN: Record<PlanOption['value'], number> = {
    monthly: 1,
    quarterly: 3,
    annual: 12,
}

// Quarterly is recommended because it's the only tier that matches this app's
// own 3-month scheduling ceiling (see StoreSupplyRequest/StoreDemandRequest),
// not because "middle tier" is a pricing-page convention.
const RECOMMENDED_PLAN: PlanOption['value'] = 'quarterly'

// Reuses the exact vocabulary already shown on the farmer/dealer dashboards
// (WasteRankingCard titles, VegetableMonthlyChart heading) so the pitch here
// doesn't introduce a second name for the same feature.
const FEATURE_BULLETS: Record<string, string[]> = {
    admin_analytics: [
        'Platform-wide registration trends, month over month',
        'Farmer and dealer growth trajectories',
        'Full historical data for reporting',
    ],
    farmer_forecasts: [
        '6-Month Forecast for every vegetable you grow',
        'Year-Round Shortages — steady gaps worth planting for',
        '5 years of seasonal history behind every projection',
    ],
    dealer_market_intel: [
        '6-Month Forecast for every vegetable you source',
        'Year-Round Surpluses — reliable bulk-buying windows',
        '5 years of seasonal history behind every projection',
    ],
}

const bullets = computed(() => FEATURE_BULLETS[props.feature] ?? [])

// Onboarding phase: billing isn't wired up yet, so every plan is free.
// Grep for FREE_ONBOARDING_PHASE when it's time to rip this out and switch
// the template back to using `plans` (the prop) directly.
const FREE_ONBOARDING_PHASE = true

const displayPlans = computed<PlanOption[]>(() =>
    FREE_ONBOARDING_PHASE ? props.plans.map((p) => ({ ...p, price_cents: 0 })) : props.plans,
)

const monthlyBaseline = computed(() => displayPlans.value.find((p) => p.value === 'monthly'))

function perMonthCents(plan: PlanOption): number {
    return plan.price_cents / MONTHS_BY_PLAN[plan.value]
}

function savingsPct(plan: PlanOption): number | null {
    const monthly = monthlyBaseline.value
    if (!monthly || plan.value === 'monthly') return null

    const fullPrice = monthly.price_cents * MONTHS_BY_PLAN[plan.value]
    if (fullPrice <= 0) return null

    return Math.round((1 - plan.price_cents / fullPrice) * 100)
}

function formatPrice(cents: number): string {
    if (cents <= 0) return 'Free'

    return (cents / 100).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    })
}

function selectPlan(plan: PlanOption['value']): void {
    subscribeForm.plan = plan
    subscribeForm.post(subscribe().url, {
        preserveScroll: true,
        onSuccess: () => subscribeForm.reset('plan'),
    })
}

function handleCancel(): void {
    cancelForm.post(cancel().url, { preserveScroll: true })
}
</script>

<template>
    <Head :title="`${featureLabel} — Billing`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                :title="featureLabel"
                description="Manage your subscription to access this feature."
            />

            <!-- Current status -->
            <Card v-if="hasActiveSubscription">
                <CardContent class="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CircleCheck class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold capitalize">
                                {{ subscription?.plan }} plan
                                <Badge
                                    v-if="isCancelled"
                                    variant="outline"
                                    class="ml-1 text-xs font-normal"
                                >Not renewing</Badge>
                            </p>
                            <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <CalendarClock class="size-3.5" />
                                {{ isCancelled ? 'Access ends' : 'Renews' }} {{ subscription?.ends_at_human }}
                                ({{ subscription?.ends_at }})
                            </p>
                        </div>
                    </div>

                    <Button
                        v-if="!isCancelled"
                        variant="outline"
                        size="sm"
                        :disabled="cancelForm.processing"
                        @click="handleCancel"
                    >
                        <Spinner
                            v-if="cancelForm.processing"
                            class="size-3.5"
                        />
                        Cancel
                    </Button>
                </CardContent>
            </Card>

            <!-- Locked preview: the actual chart, not a generic padlock -->
            <Card
                v-else
                class="overflow-hidden border-dashed"
            >
                <CardContent class="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center">
                    <div class="relative h-28 w-full max-w-xs shrink-0 overflow-hidden rounded-lg bg-muted/30 p-3">
                        <div class="flex h-full items-end gap-2 blur-[2px]">
                            <div
                                v-for="(h, i) in [55, 70, 45, 80, 60, 90]"
                                :key="i"
                                class="flex flex-1 flex-col justify-end gap-0.5"
                            >
                                <div
                                    class="rounded-sm"
                                    :class="i < 3 ? 'bg-emerald-500/70' : 'bg-emerald-500/40'"
                                    :style="{ height: `${h * 0.55}%` }"
                                />
                                <div
                                    class="rounded-sm bg-neutral-400/50"
                                    :style="{ height: `${h * 0.2}%` }"
                                />
                            </div>
                        </div>
                        <div class="absolute inset-y-3 left-1/2 border-l border-dashed border-neutral-400/60" />
                        <span class="absolute left-[calc(50%+6px)] top-3 text-[10px] font-medium text-muted-foreground/80">
                            ▸ forecast
                        </span>
                        <div class="absolute inset-0 flex items-center justify-center bg-background/40">
                            <div class="flex items-center gap-1.5 rounded-full border bg-card px-3 py-1 text-xs font-medium shadow-sm">
                                <Lock class="size-3.5" />
                                Locked
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold">6-Month Forecast, unlocked</p>
                        <p class="max-w-sm text-sm text-muted-foreground">
                            This is the actual forecast chart on every vegetable's detail page. Subscribe below to
                            see six months ahead instead of just this week.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Plans -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Card
                    v-for="plan in displayPlans"
                    :key="plan.value"
                    :class="[
                        'relative flex flex-col transition-transform motion-reduce:transition-none',
                        plan.value === RECOMMENDED_PLAN
                            ? 'border-amber-500/60 ring-1 ring-amber-500/60'
                            : 'hover:-translate-y-0.5',
                        subscribeForm.plan === plan.value && subscribeForm.processing && 'opacity-60',
                    ]"
                >
                    <Badge
                        v-if="plan.value === RECOMMENDED_PLAN"
                        class="absolute -top-2.5 left-1/2 -translate-x-1/2 gap-1 bg-amber-500 text-white hover:bg-amber-500"
                    >
                        <Sparkles class="size-3" />
                        Matches your 3-month scheduling window
                    </Badge>

                    <CardHeader class="pt-6">
                        <CardTitle class="text-base">{{ plan.label }}</CardTitle>
                        <CardDescription class="flex items-baseline gap-1.5">
                            <span class="text-3xl font-bold tabular-nums text-foreground">
                                {{ formatPrice(plan.price_cents) }}
                            </span>
                        </CardDescription>
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span
                                v-if="plan.value !== 'monthly' && plan.price_cents > 0"
                                class="text-xs text-muted-foreground"
                            >
                                ≈ {{ formatPrice(perMonthCents(plan)) }} / month
                            </span>
                            <Badge
                                v-if="savingsPct(plan)"
                                variant="secondary"
                                class="text-xs font-medium text-emerald-700 dark:text-emerald-400"
                            >
                                Save {{ savingsPct(plan) }}%
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col gap-4">
                        <ul class="flex flex-1 flex-col gap-2 text-sm">
                            <li
                                v-for="bullet in bullets"
                                :key="bullet"
                                class="flex items-start gap-2"
                            >
                                <CircleCheck class="mt-0.5 size-4 shrink-0 text-primary" />
                                <span class="text-muted-foreground">{{ bullet }}</span>
                            </li>
                        </ul>

                        <Button
                            class="w-full"
                            :variant="hasActiveSubscription && subscription?.plan === plan.value ? 'outline' : 'default'"
                            :disabled="subscribeForm.processing || (hasActiveSubscription && subscription?.plan === plan.value && !isCancelled)"
                            @click="selectPlan(plan.value)"
                        >
                            <Spinner
                                v-if="subscribeForm.processing && subscribeForm.plan === plan.value"
                                class="size-3.5"
                            />
                            <template v-else>
                                {{ hasActiveSubscription && subscription?.plan === plan.value && !isCancelled
                                    ? 'Current plan'
                                    : FREE_ONBOARDING_PHASE
                                        ? `Get free access — ${plan.label}`
                                        : `Subscribe — ${plan.label}` }}
                            </template>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <p
                v-if="subscribeForm.errors.plan"
                class="text-center text-xs text-destructive"
            >
                {{ subscribeForm.errors.plan }}
            </p>
        </div>
    </AppLayout>
</template>