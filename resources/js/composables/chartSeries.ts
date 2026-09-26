import type { Chart as ChartJS } from 'chart.js'
import { type MaybeRefOrGetter, toValue } from 'vue'
import type { ForecastPoint, MonthlyActivity } from '@/types/resources/product'

export type MetricKey =
    | 'supply_fulfilled_kg'
    | 'supply_expired_kg'
    | 'demand_fulfilled_kg'
    | 'demand_expired_kg'

export interface SeriesConfig {
    key: MetricKey
    label: string
    rgb: string
    stack: 'supply' | 'demand'
    historicalAlpha: number
    forecastAlpha: number
    radius: number
}

export const MONTHLY_VOLUME_SERIES: SeriesConfig[] = [
    {
        key: 'supply_fulfilled_kg',
        label: 'Fulfilled Suply',
        rgb: '34, 197, 94',
        stack: 'supply',
        historicalAlpha: 0.9,
        forecastAlpha: 0.35,
        radius: 4,
    },
    {
        key: 'supply_expired_kg',
        label: 'Expired Supply',
        rgb: '166, 166, 166',
        stack: 'supply',
        historicalAlpha: 0.3,
        forecastAlpha: 0.15,
        radius: 0,
    },
    {
        key: 'demand_fulfilled_kg',
        label: 'Fulfilled Demand',
        rgb: '249, 115, 22',
        stack: 'demand',
        historicalAlpha: 0.9,
        forecastAlpha: 0.35,
        radius: 4,
    },
    {
        key: 'demand_expired_kg',
        label: 'Expired Demand',
        rgb: '166, 166, 166',
        stack: 'demand',
        historicalAlpha: 0.3,
        forecastAlpha: 0.15,
        radius: 0,
    },
]

export function formatKgAxis(value: number): string {
    if (Math.abs(value) >= 1000) {
        const scaled = value / 1000
        return `${scaled.toLocaleString('en-PH', {
            minimumFractionDigits: 0,
            maximumFractionDigits: scaled % 1 === 0 ? 0 : 1,
        })}k kg`
    }
    return `${value} kg`
}

export function createForecastDividerPlugin(
    activity: MaybeRefOrGetter<MonthlyActivity[] | null | undefined>,
    forecast?: MaybeRefOrGetter<ForecastPoint[] | null | undefined>,
) {
    return {
        id: 'forecastDivider',
        afterDraw(chart: ChartJS) {
            const fc = forecast ? (toValue(forecast) ?? []) : []
            if (!fc.length) return

            const histLen = toValue(activity)?.length ?? 0
            if (histLen <= 0) return

            const { ctx, chartArea, scales } = chart as unknown as {
                ctx: CanvasRenderingContext2D
                chartArea: { top: number; bottom: number }
                scales: { x: { getPixelForValue: (v: number) => number } }
            }

            const xLeft = scales.x.getPixelForValue(histLen - 1)
            const xRight = scales.x.getPixelForValue(histLen)
            const xPos = (xLeft + xRight) / 2

            ctx.save()
            ctx.strokeStyle = 'rgba(100,116,139,0.3)'
            ctx.lineWidth = 1
            ctx.setLineDash([4, 3])
            ctx.beginPath()
            ctx.moveTo(xPos, chartArea.top)
            ctx.lineTo(xPos, chartArea.bottom)
            ctx.stroke()

            ctx.setLineDash([])
            ctx.font = '10px system-ui, sans-serif'
            ctx.fillStyle = 'rgba(100,116,139,0.65)'
            ctx.textAlign = 'left'
            ctx.fillText('▸ forecast', xPos + 5, chartArea.top + 12)
            ctx.restore()
        },
    }
}
