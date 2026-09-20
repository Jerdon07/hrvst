<script setup lang="ts">
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import AnalyticsUpsellCard from '@/components/shared/charts/AnalyticsUpsellCard.vue'
import UserVolumeChart from '@/components/shared/charts/UserVolumeChart.vue'
import WasteRankingCard from '@/components/shared/charts/WasteRankingCard.vue'
import type { UserInsights } from '@/types'

defineProps<{
    insights: UserInsights
    locked: boolean
    totalQuantity: number
    featureLabel: string
    wasteTitle: string
    wasteDescription: string
    wasteUnitLabel?: string
    wasteGuideQuestion: string
    volumeTitle: string
}>()
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <SmallCard
                title="Fulfillment Rate"
                :value="insights.fulfillment_rate !== null
                    ? `${Math.round(insights.fulfillment_rate * 100)}%`
                    : '—'"
                subtext="fulfilled schedules"
            />
            <SmallCard
                title="Posts / Month"
                :value="insights.posts_per_month"
            />
            <SmallCard
                title="Last Active"
                :value="insights.last_active_human"
                value-class="text-md"
            />
            <SmallCard
                title="Total Quantity"
                :value="totalQuantity.toLocaleString()"
                subtext="kg"
            />
        </div>

        <div class="relative">
            <div
                :class="locked ? 'pointer-events-none select-none blur-sm' : ''"
                :aria-hidden="locked"
                class="grid grid-cols-1 gap-4 lg:grid-cols-2"
            >
                <WasteRankingCard
                    :title="wasteTitle"
                    :description="wasteDescription"
                    :items="insights.top_varieties"
                    :initial-visible="5"
                    :unit-label="wasteUnitLabel"
                    :guide-question="wasteGuideQuestion"
                />
                <UserVolumeChart
                    :title="volumeTitle"
                    :monthly-volume="insights.monthly_volume"
                />
            </div>

            <div
                v-if="locked"
                class="absolute inset-0 flex items-center justify-center rounded-xl bg-background/75 p-4"
            >
                <AnalyticsUpsellCard
                    :feature-label="featureLabel"
                    class="max-w-sm shadow-lg"
                />
            </div>
        </div>
    </div>
</template>