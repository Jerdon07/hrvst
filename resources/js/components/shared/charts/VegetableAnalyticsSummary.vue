<script setup lang="ts">
import { computed } from 'vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import type { VarietyAnalytics } from '@/types/resources/product'

const props = defineProps<{
    analytics: VarietyAnalytics
}>()

const bandConfig = computed(() => {
    switch (props.analytics.expected_balance.band) {
        case 'oversupply':
            return { label: 'Oversupply', valueClass: 'text-red-600 dark:text-red-400' }
        case 'undersupply':
            return { label: 'Undersupply', valueClass: 'text-amber-600 dark:text-amber-400' }
        default:
            return { label: 'Balanced', valueClass: 'text-green-600 dark:text-green-400' }
    }
})

const expectedBalanceTooltip = computed(() => {
    const c = props.analytics.expected_balance.computation
    if (!c) return props.analytics.expected_balance.explanation

    const supply = c.supply_kg.toLocaleString()
    const demand = c.demand_kg.toLocaleString()

    if (c.diff_pct === null) {
        return `From ${c.source_label}: ${supply} kg supply vs ${demand} kg demand (demand was zero, no % comparison).`
    }

    const direction = c.diff_pct > 0 ? 'higher' : c.diff_pct < 0 ? 'lower' : 'equal to'
    const magnitude = Math.abs(c.diff_pct)

    return `From ${c.source_label}: supply (${supply} kg) is ${magnitude}% ${direction} than demand (${demand} kg).`
})

// Three-tier coloring: green ≥0.7, amber [0.5, 0.7), red <0.5.
// See VegetableAnalyticsSummary.test.ts for the exact boundary cases.
function fulfillmentConfig(rate: number | null) {
    if (rate === null) return { valueClass: 'text-muted-foreground' }
    if (rate >= 0.7) return { valueClass: 'text-green-600 dark:text-green-400' }
    if (rate >= 0.5) return { valueClass: 'text-amber-600 dark:text-amber-400' }
    return { valueClass: 'text-red-600 dark:text-red-400' }
}

function formatRate(rate: number | null): string {
    return rate !== null ? `${Math.round(rate * 100)}%` : '—'
}

const supplyConfig = computed(() => fulfillmentConfig(props.analytics.supply_fulfillment_rate))
const demandConfig = computed(() => fulfillmentConfig(props.analytics.demand_fulfillment_rate))
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <!-- Expected Market Balance -->
        <AppTooltip :content="expectedBalanceTooltip">
            <SmallCard
                title="Expected Market Balance"
                :value="bandConfig.label"
                :value-class="bandConfig.valueClass"
                :subtext="analytics.expected_balance.explanation"
                subtext-below
                class="col-span-2 cursor-help sm:col-span-1"
            />
        </AppTooltip>

        <!-- Supply Fulfillment -->
        <SmallCard
            title="Supply Fulfillment"
            :value="formatRate(analytics.supply_fulfillment_rate)"
            :value-class="supplyConfig.valueClass"
            subtext="3-month avg"
            subtext-below
        />

        <!-- Demand Fulfillment -->
        <SmallCard
            title="Demand Fulfillment"
            :value="formatRate(analytics.demand_fulfillment_rate)"
            :value-class="demandConfig.valueClass"
            subtext="3-month avg"
            subtext-below
        />
    </div>
</template>