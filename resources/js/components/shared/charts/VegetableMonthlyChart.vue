<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { AlertCircle, ChartColumn, ChartLine, ChevronLeft, ChevronRight, Info, Lock, Sparkles } from '@lucide/vue'
import { computed, ref } from 'vue'
import { Bar, Line } from 'vue-chartjs'
import EmptyState from '@/components/EmptyState.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { useMonthlyBarChart } from '@/composables/useMonthlyBarChart'
import { useMonthlyLineChart } from '@/composables/useMonthlyLineChart'
import { show as billingShow } from '@/routes/billing'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

const props = defineProps<{
    monthlyActivity: MonthlyActivity[]
    forecast?: ForecastPoint[]
    monthsOfHistory?: number
    forecastConfidence?: string
    activityOffset?: number
    maxActivityOffset?: number
    forecastLocked?: boolean
    upgradeFeatureLabel?: string | null
}>()

const emit = defineEmits<{
    navigate: [offset: number]
}>()

// The default window is 6 months (offset 0). Every historical page after
// that is 12 months. So the first "previous" click only steps the offset by
// 6 (landing on month-back index 6, immediately after the default window's
// last month) — every click after that steps by a full 12, since the
// window is already 12 months wide and contiguous with the last one. Going
// "next" mirrors this: stepping back below the first paged offset always
// lands exactly on 0, never a negative offset.
//
// This must match VegetableDetailService::resolveActivityWindowMonths() —
// the backend derives window size from offset using the same 6/12 split.
const INITIAL_WINDOW_MONTHS = 6
const PAGED_STEP_MONTHS = 12
const MIN_MONTHS_FOR_FORECAST = 12

const chartType = ref<'bar' | 'line'>('bar')

const {
    chartData: barChartData,
    chartOptions: barChartOptions,
    forecastDividerPlugin: barForecastDividerPlugin,
} = useMonthlyBarChart(() => props.monthlyActivity, () => props.forecast)

const {
    chartData: lineChartData,
    chartOptions: lineChartOptions,
    forecastDividerPlugin: lineForecastDividerPlugin,
} = useMonthlyLineChart(() => props.monthlyActivity, () => props.forecast)

const hasForecast = () => (props.forecast?.length ?? 0) > 0

const confidenceLabel: Record<string, string> = {
    developing: 'Developing forecast',
    established: 'Established forecast',
    strong: 'Strong forecast',
}

const confidenceTooltip: Record<string, string> = {
    developing: 'Based on 12–35 months of history — seasonal pattern is emerging but not yet fully confirmed.',
    established: 'Based on 36–59 months of history — seasonal pattern is well supported.',
    strong: 'Based on 60+ months of history — high-confidence seasonal projection.',
}

// ─── Pagination ───────────────────────────────────────────────────────────────
// Window size is always 6 months (server-controlled). Only the step between
// windows is 12 months. Forecast + history paging are both subscription-gated
// — see forecastLocked below, which the parent derives from the same
// Subscription::hasAccess() check that gates the forecast itself.

const offset = computed(() => props.activityOffset ?? 0)
const maxOffset = computed(() => props.maxActivityOffset ?? 0)

const canGoNext = computed(() => offset.value > 0)
const canGoPrevious = computed(() => !props.forecastLocked && offset.value < maxOffset.value)

const rangeLabel = computed(() => {
    if (!props.monthlyActivity.length) return ''
    const first = props.monthlyActivity[0]?.label
    const last = props.monthlyActivity[props.monthlyActivity.length - 1]?.label
    return first === last ? first : `${first} – ${last}`
})

function goPrevious(): void {
    if (!canGoPrevious.value) return

    const next = offset.value === 0
        ? INITIAL_WINDOW_MONTHS
        : Math.min(offset.value + PAGED_STEP_MONTHS, maxOffset.value)

    emit('navigate', next)
}

function goNext(): void {
    if (!canGoNext.value) return

    const next = offset.value - PAGED_STEP_MONTHS
    emit('navigate', next > 0 ? next : 0)
}
</script>

<template>
    <Card class="rounded-none border-0 shadow-none sm:rounded sm:border sm:shadow-sm">
        <CardHeader class="flex flex-row flex-wrap items-center justify-between gap-2 space-y-0 px-3 py-3 sm:px-6 sm:py-6">
            <div class="flex items-center gap-2">
                <CardTitle>
                    {{ hasForecast() ? '6-Month Forecast' : 'Market Volume' }}
                </CardTitle>

                <AppTooltip
                    v-if="hasForecast() && forecastConfidence"
                    :content="confidenceTooltip[forecastConfidence]"
                >
                    <Badge
                        variant="outline"
                        class="cursor-help gap-1 text-xs font-normal"
                    >
                        <Info class="size-3" />
                        {{ confidenceLabel[forecastConfidence] ?? 'Forecast' }}
                    </Badge>
                </AppTooltip>
            </div>

            <div class="flex items-center gap-2">
                <!-- Range navigation -->
                <div class="flex items-center gap-1">
                    <AppTooltip
                        :content="forecastLocked
                            ? 'Subscribe to browse older market history'
                            : (canGoPrevious ? 'View earlier history' : 'No earlier history available')"
                    >
                        <Button
                            v-if="forecastLocked"
                            as-child
                            variant="outline"
                            size="icon-sm"
                        >
                            <Link :href="billingShow().url">
                                <Lock class="size-3.5" />
                            </Link>
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="icon-sm"
                            :disabled="!canGoPrevious"
                            @click="goPrevious"
                        >
                            <ChevronLeft class="size-3.5" />
                        </Button>
                    </AppTooltip>

                    <span class="min-w-28 text-center text-xs font-medium text-muted-foreground">
                        {{ rangeLabel }}
                    </span>

                    <AppTooltip content="Back to current">
                        <Button
                            variant="outline"
                            size="icon-sm"
                            :disabled="!canGoNext"
                            @click="goNext"
                        >
                            <ChevronRight class="size-3.5" />
                        </Button>
                    </AppTooltip>
                </div>

                <ToggleGroup
                    v-if="barChartData || lineChartData"
                    v-model="chartType"
                    type="single"
                    variant="outline"
                    size="sm"
                >
                    <ToggleGroupItem
                        value="bar"
                        aria-label="Bar chart"
                    >
                        <ChartColumn class="size-3.5" />
                    </ToggleGroupItem>
                    <ToggleGroupItem
                        value="line"
                        aria-label="Line chart"
                    >
                        <ChartLine class="size-3.5" />
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>
        </CardHeader>
        <CardContent class="px-0 sm:px-6">
            <div
                v-if="barChartData || lineChartData"
                class="relative h-48 w-full sm:h-64"
            >
                <Bar
                    v-if="chartType === 'bar' && barChartData"
                    :data="barChartData"
                    :options="barChartOptions"
                    :plugins="[barForecastDividerPlugin]"
                />
                <Line
                    v-else-if="lineChartData"
                    :data="lineChartData"
                    :options="lineChartOptions"
                    :plugins="[lineForecastDividerPlugin]"
                />
            </div>

            <EmptyState
                v-else
                title="No completed activity record"
                :icon="AlertCircle"
            />

            <div
                v-if="forecastLocked"
                class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-dashed bg-muted/30 p-3 text-xs text-muted-foreground"
            >
                <span class="flex items-center gap-2">
                    <Lock class="size-3.5 shrink-0" />
                    Subscribe to unlock the 6-month forecast and up to 5 years of market history.
                </span>
                <Button
                    as-child
                    size="sm"
                    variant="outline"
                    class="shrink-0 gap-1.5"
                >
                    <Link :href="billingShow().url">
                        <Sparkles class="size-3.5" />
                        {{ upgradeFeatureLabel ?? 'Subscribe' }}
                    </Link>
                </Button>
            </div>

            <div
                v-else-if="(barChartData || lineChartData) && !hasForecast() && offset === 0 && (monthsOfHistory ?? 0) < MIN_MONTHS_FOR_FORECAST"
                class="mt-3 flex items-center gap-2 rounded-lg border border-dashed bg-muted/30 p-3 text-xs text-muted-foreground"
            >
                <Info class="size-3.5 shrink-0" />
                <span>
                    Forecast unavailable — {{ monthsOfHistory ?? 0 }}/{{ MIN_MONTHS_FOR_FORECAST }} months
                    of data collected. A full year of history is needed to detect seasonal patterns.
                </span>
            </div>
        </CardContent>
    </Card>
</template>