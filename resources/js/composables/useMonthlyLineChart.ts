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
} from 'chart.js'
import { computed, type MaybeRefOrGetter } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'
import {
    BOTTOM_LEGEND,
    buildMonthlyTimeline,
    createForecastDividerPlugin,
    formatKgAxis,
    formatKgTooltipLabel,
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
        const timeline = buildMonthlyTimeline(activity, forecast)
        if (!timeline) return null

        const datasets = MONTHLY_VOLUME_SERIES.map((series) => ({
            label: series.label,
            data: timeline.valuesFor(series.key),
            borderColor: (ctx: ScriptableContext<'line'>) =>
                `rgba(${series.rgb}, ${timeline.isForecastIndex(ctx.dataIndex ?? 0) ? 0.5 : 1})`,
            backgroundColor: `rgba(${series.rgb}, 0.08)`,
            pointBackgroundColor: `rgb(${series.rgb})`,
            pointRadius: (ctx: ScriptableContext<'line'>) =>
                timeline.isForecastIndex(ctx.dataIndex ?? 0) ? 2 : 3,
            borderWidth: 2,
            borderDash: (ctx: ScriptableContext<'line'>) =>
                timeline.isForecastIndex(ctx.dataIndex ?? 0) ? [4, 3] : [],
            tension: 0.3,
            fill: false,
        }))

        return { labels: timeline.labels, datasets }
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
            legend: BOTTOM_LEGEND,
            tooltip: { callbacks: { label: formatKgTooltipLabel } },
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