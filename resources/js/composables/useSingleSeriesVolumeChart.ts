import {
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    type ChartOptions,
    LinearScale,
    Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { MonthlyVolumeData } from '@/types/resources/product'
import { formatKgAxis } from './chartSeries'

ChartJS.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip)

export function useSingleSeriesVolumeChart(
    volume: MaybeRefOrGetter<MonthlyVolumeData[] | null | undefined>,
) {
    const chartData = computed(() => {
        const rows = toValue(volume)
        if (!rows?.length) return null

        return {
            labels: rows.map((r) => r.label),
            datasets: [
                {
                    type: 'bar' as const,
                    label: 'Volume',
                    data: rows.map((r) => r.value_kg),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderRadius: 4,
                },
            ],
        }
    })

    const chartOptions: ChartOptions<'bar'> = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) =>
                        ` ${(ctx.raw as number).toLocaleString('en-PH')} kg`,
                },
            },
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { callback: (v) => formatKgAxis(Number(v)) },
            },
        },
    }

    return { chartData, chartOptions }
}