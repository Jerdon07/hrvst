<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import { Minus, TrendingDown, TrendingUp } from '@lucide/vue'
import RegistrationTrendChart from '@/components/features/admin/charts/RegistrationTrendChart.vue'
import Heading from '@/components/Heading.vue'
import LargeCard from '@/components/shared/cards/LargeCard.vue'
import AnalyticsUpsellCard from '@/components/shared/charts/AnalyticsUpsellCard.vue'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes/admin'
import type { AdminDashboardKPIs, BreadcrumbItem, KpiStat, RegistrationTrendPoint } from '@/types'

defineProps<{
    kpis?: AdminDashboardKPIs
    registrationTrends?: RegistrationTrendPoint[]
    analyticsLocked: boolean
    upgradeFeatureLabel: string
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: dashboard().url },
    { title: 'Dashboard', href: dashboard().url },
]

function getTrendIcon(trend?: KpiStat['trend']) {
    if (trend === 'up') return TrendingUp
    if (trend === 'down') return TrendingDown
    return Minus
}
function getTrendColor(trend?: KpiStat['trend']) {
    if (trend === 'up') return 'text-green-600 dark:text-green-500'
    if (trend === 'down') return 'text-red-600 dark:text-red-500'
    return 'text-muted-foreground'
}
function formatChange(change?: number): string {
    if (change === undefined) return ''
    const sign = change > 0 ? '+' : ''
    return `${sign}${change}%`
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Dashboard"
                description="Overview of the platform"
            />

            <Deferred data="kpis">
                <template #fallback>
                    <div class="grid gap-4 md:grid-cols-3">
                        <Skeleton
                            v-for="i in 3"
                            :key="i"
                            class="h-33"
                        />
                    </div>
                </template>

                <div class="grid gap-4 md:grid-cols-3">
                    <LargeCard
                        title="Total Vegetables"
                        subtext="recorded"
                        :value="kpis?.vegetables.total_vegetables.value"
                        card-class="bg-linear-to-br from-orange-500/10 via-amber-500/10 to-yellow-500/30"
                    />
                    <LargeCard
                        title="Total Farmers"
                        subtext="registered"
                        :value="kpis?.farmers.total_farmers.value.toLocaleString()"
                        :change="formatChange(kpis?.farmers.total_farmers.change)"
                        :badge="getTrendIcon(kpis?.farmers.total_farmers.trend)"
                        :trend-color="getTrendColor(kpis?.farmers.total_farmers.trend)"
                        card-class="bg-linear-to-br from-lime-500/10 via-emerald-500/10 to-cyan-500/30"
                    />
                    <LargeCard
                        title="Total Dealers"
                        subtext="registered"
                        :value="kpis?.dealers.total_dealers.value.toLocaleString()"
                        :change="formatChange(kpis?.dealers.total_dealers.change)"
                        :badge="getTrendIcon(kpis?.dealers.total_dealers.trend)"
                        :trend-color="getTrendColor(kpis?.dealers.total_dealers.trend)"
                        card-class="bg-linear-to-br from-indigo-500/10 via-fuchsia-500/10 to-rose-500/30"
                    />
                </div>
            </Deferred>

            <AnalyticsUpsellCard
                v-if="analyticsLocked"
                :feature-label="upgradeFeatureLabel"
            />
            <Deferred
                v-else
                data="registrationTrends"
            >
                <template #fallback>
                    <Skeleton class="h-72 w-full rounded-xl" />
                </template>
                <RegistrationTrendChart :trends="registrationTrends ?? []" />
            </Deferred>
        </div>
    </AppLayout>
</template>
