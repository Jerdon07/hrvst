import {
    CategoryScale,
    Chart as ChartJS,
    type ChartOptions,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import type { RegistrationTrendPoint } from '@/types'
import { BOTTOM_LEGEND } from './chartSeries'

ChartJS.register(
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
)

export function useRegistrationTrendChart(
    points: MaybeRefOrGetter<RegistrationTrendPoint[] | null | undefined>,
) {
    const chartData = computed(() => {
        const rows = toValue(points)
        if (!rows?.length) return null

        return {
            labels: rows.map((r) => r.label),
            datasets: [
                {
                    label: 'Farmers',
                    data: rows.map((r) => r.farmers),
                    borderColor: 'rgba(34, 197, 94, 1)',
                    backgroundColor: 'rgba(34, 197, 94, 0.15)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                },
                {
                    label: 'Dealers',
                    data: rows.map((r) => r.dealers),
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                },
            ],
        }
    })

    const chartOptions: ChartOptions<'line'> = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: BOTTOM_LEGEND,
            tooltip: {
                callbacks: {
                    label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw}`,
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, maxRotation: 45 },
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { precision: 0, font: { size: 11 } },
            },
        },
    }

    return { chartData, chartOptions }
}
