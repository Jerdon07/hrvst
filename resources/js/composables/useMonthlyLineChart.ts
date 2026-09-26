import {
    CategoryScale,
    Chart as ChartJS,
    type ChartData,
    type ChartOptions,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    type ScriptableContext,
    Title,
    Tooltip,
    type TooltipItem,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'
import {
    createForecastDividerPlugin,
    formatKgAxis,
    MONTHLY_VOLUME_SERIES,
} from './chartSeries'

ChartJS.register(
    LineController,
    LineElement,
    PointElement,
    Filler,
    CategoryScale,
    LinearScale,
    Title,
    Tooltip,
    Legend,
)

export function useMonthlyLineChart(
    activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast?: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
) {
    const chartData = computed<ChartData<'line'> | null>(() => {
        const allMonths = toValue(activity)
        const fc = forecast ? (toValue(forecast) ?? []) : []
        if (!allMonths?.length) return null

        // Do not slice this — the backend already sends exactly the window
        // it wants displayed (6 months by default, 12 when paged).
        const historical = allMonths
        const histLen = historical.length
        const allLabels = [
            ...historical.map((m) => m.label),
            ...fc.map((m) => m.label),
        ]
        const isForecastIndex = (dataIndex: number) => dataIndex >= histLen

        const datasets = MONTHLY_VOLUME_SERIES.map((series) => {
            const historicalValues = historical.map(
                (m) => (m as unknown as Record<string, number>)[series.key],
            )
            const forecastValues = fc.map((m) => m[series.key])
            const data = [...historicalValues, ...forecastValues]

            return {
                label: series.label,
                data,
                borderColor: (ctx: ScriptableContext<'line'>) =>
                    `rgba(${series.rgb}, ${isForecastIndex(ctx.dataIndex ?? 0) ? 0.5 : 1})`,
                backgroundColor: `rgba(${series.rgb}, 0.08)`,
                pointBackgroundColor: `rgb(${series.rgb})`,
                pointRadius: (ctx: ScriptableContext<'line'>) =>
                    isForecastIndex(ctx.dataIndex ?? 0) ? 2 : 3,
                borderWidth: 2,
                borderDash: (ctx: ScriptableContext<'line'>) =>
                    isForecastIndex(ctx.dataIndex ?? 0) ? [4, 3] : [],
                tension: 0.3,
                fill: false,
            }
        })

        return { labels: allLabels, datasets }
    })

    const forecastDividerPlugin = createForecastDividerPlugin(
        activity,
        forecast,
    )

    const chartOptions: ChartOptions<'line'> = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 8,
                    boxHeight: 8,
                    padding: 8,
                    font: { size: 10 },
                },
            },
            tooltip: {
                callbacks: {
                    label: (ctx: TooltipItem<'line'>) => {
                        const raw = ctx.raw as number | null
                        if (raw === null || raw === undefined) return ''
                        return ` ${ctx.dataset.label}: ${raw.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} kg`
                    },
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 11 },
                    maxRotation: 45,
                    maxTicksLimit: 12,
                },
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: {
                    font: { size: 11 },
                    callback: (value) => formatKgAxis(Number(value)),
                },
            },
        },
    }

    return { chartData, chartOptions, forecastDividerPlugin }
}
