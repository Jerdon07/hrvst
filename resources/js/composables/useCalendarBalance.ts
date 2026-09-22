import { computed, type MaybeRefOrGetter, toValue } from 'vue'

export type CalendarViewerRole = 'farmer' | 'dealer' | 'admin'
export type BalanceColor = 'red' | 'orange' | 'amber' | 'green'

export interface DayTotals {
    supplyKg: number
    demandKg: number
}

export interface DayBalance {
    color: BalanceColor
    label: string
}

const RATIO_HIGH = 0.6
const RATIO_LOW = 0.2

const ADMIN_HIGH_MULTIPLIER = 1.4
const ADMIN_LOW_MULTIPLIER = 0.6

function monthlyAverages(totals: DayTotals[]) {
    const withData = totals.filter((t) => t.supplyKg > 0 || t.demandKg > 0)
    if (withData.length === 0) return { avgTotal: 0 }

    const avgSupply =
        withData.reduce((s, t) => s + t.supplyKg, 0) / withData.length
    const avgDemand =
        withData.reduce((s, t) => s + t.demandKg, 0) / withData.length

    return { avgTotal: avgSupply + avgDemand }
}

function farmerBalance(ratio: number): DayBalance {
    if (ratio >= RATIO_HIGH) return { color: 'red', label: 'Very Surplus' }
    if (ratio >= RATIO_LOW) return { color: 'orange', label: 'Surplus' }
    if (ratio <= -RATIO_LOW) return { color: 'green', label: 'Unmet Demand' }
    return { color: 'amber', label: 'Balanced' }
}

function dealerBalance(ratio: number): DayBalance {
    if (ratio <= -RATIO_HIGH) return { color: 'red', label: 'Very Unmet' }
    if (ratio <= -RATIO_LOW) return { color: 'orange', label: 'Unmet' }
    if (ratio >= RATIO_LOW)
        return { color: 'green', label: 'Surplus Available' }
    return { color: 'amber', label: 'Balanced' }
}

function adminBalance(totalKg: number, avgTotal: number): DayBalance {
    if (avgTotal <= 0) return { color: 'amber', label: 'No Activity' }
    const ratio = totalKg / avgTotal

    if (ratio >= ADMIN_HIGH_MULTIPLIER)
        return { color: 'red', label: 'Very High Activity' }
    if (ratio <= ADMIN_LOW_MULTIPLIER)
        return { color: 'amber', label: 'Very Low Activity' }
    return { color: 'green', label: 'Average Activity' }
}

export function useCalendarBalance(
    dailyTotals: MaybeRefOrGetter<Record<string, DayTotals>>,
    role: MaybeRefOrGetter<CalendarViewerRole>,
) {
    function balanceFor(dateStr: string): DayBalance | null {
        const allTotals = toValue(dailyTotals)
        const totals = allTotals[dateStr]
        if (!totals) return null

        const otherDays = Object.entries(allTotals)
            .filter(([key]) => key !== dateStr)
            .map(([, value]) => value)
        const { avgTotal } = monthlyAverages(otherDays)

        const currentRole = toValue(role)

        if (currentRole === 'admin') {
            return adminBalance(totals.supplyKg + totals.demandKg, avgTotal)
        }

        const ratio =
            (totals.supplyKg - totals.demandKg) / Math.max(avgTotal, 1)
        return currentRole === 'farmer'
            ? farmerBalance(ratio)
            : dealerBalance(ratio)
    }

    function legend(): DayBalance[] {
        const currentRole = toValue(role)

        if (currentRole === 'farmer') {
            return [
                { color: 'red', label: 'Very Surplus' },
                { color: 'orange', label: 'Surplus' },
                { color: 'amber', label: 'Balanced' },
                { color: 'green', label: 'Unmet Demand' },
            ]
        }

        if (currentRole === 'dealer') {
            return [
                { color: 'red', label: 'Very Unmet' },
                { color: 'orange', label: 'Unmet' },
                { color: 'amber', label: 'Balanced' },
                { color: 'green', label: 'Surplus Available' },
            ]
        }

        return [
            { color: 'red', label: 'Very High Activity' },
            { color: 'green', label: 'Average Activity' },
            { color: 'amber', label: 'Very Low Activity' },
        ]
    }

    return {
        balanceFor,
        legend: computedLegend(legend),
    }
}

function computedLegend(fn: () => DayBalance[]) {
    return computed(fn)
}

export const BALANCE_DOT_CLASS: Record<BalanceColor, string> = {
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    amber: 'bg-amber-500',
    green: 'bg-green-500',
}