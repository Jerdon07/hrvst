import {
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    type ChartData,
    type ChartOptions,
    Legend,
    LinearScale,
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
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Title,
    Tooltip,
    Legend,
)

export function useMonthlyBarChart(
    activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast?: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
) {
    const chartData = computed<ChartData<'bar'> | null>(() => {
        const timeline = buildMonthlyTimeline(activity, forecast)
        if (!timeline) return null

        const datasets = MONTHLY_VOLUME_SERIES.map((series) => ({
            label: series.label,
            data: timeline.valuesFor(series.key),
            backgroundColor: (ctx: ScriptableContext<'bar'>) =>
                `rgba(${series.rgb}, ${timeline.isForecastIndex(ctx.dataIndex) ? series.forecastAlpha : series.historicalAlpha})`,
            borderColor: (ctx: ScriptableContext<'bar'>) =>
                `rgba(${series.rgb}, ${timeline.isForecastIndex(ctx.dataIndex) ? 0.7 : 1})`,
            borderWidth: 1,
            borderDash: (ctx: ScriptableContext<'bar'>) =>
                timeline.isForecastIndex(ctx.dataIndex) ? [4, 3] : [],
            borderRadius: series.radius,
            stack: series.stack,
        }))

        return { labels: timeline.labels, datasets }
    })

    const forecastDividerPlugin = createForecastDividerPlugin(
        activity,
        forecast,
    )

    const chartOptions: ChartOptions<'bar'> = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: BOTTOM_LEGEND,
            tooltip: { callbacks: { label: formatKgTooltipLabel } },
        },
        scales: {
            x: {
                stacked: true,
                grid: { display: false },
                ticks: {
                    font: { size: 11 },
                    maxRotation: 45,
                    maxTicksLimit: 12,
                },
            },
            y: {
                stacked: true,
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
