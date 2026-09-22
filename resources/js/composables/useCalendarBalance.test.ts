import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import {
    BALANCE_DOT_CLASS,
    useCalendarBalance,
    type CalendarViewerRole,
    type DayTotals,
} from '@/composables/useCalendarBalance'

function baselineTotals(extra: Record<string, DayTotals> = {}) {
    return {
        '2026-06-01': { supplyKg: 100, demandKg: 100 },
        '2026-06-02': { supplyKg: 100, demandKg: 100 },
        '2026-06-03': { supplyKg: 100, demandKg: 100 },
        ...extra,
    }
}

function setup(
    totals: Record<string, DayTotals>,
    role: CalendarViewerRole = 'farmer',
) {
    const dailyTotals = ref(totals)
    const roleRef = ref(role)
    const { balanceFor, legend } = useCalendarBalance(dailyTotals, roleRef)

    return { dailyTotals, roleRef, balanceFor, legend }
}

describe('balanceFor: missing data', () => {
    it('returns null for a date not present in dailyTotals', () => {
        const { balanceFor } = setup(baselineTotals())
        expect(balanceFor('2099-01-01')).toBeNull()
    })
})

describe('balanceFor: farmer role', () => {
    it('is Balanced at ratio 0 (baseline day itself)', () => {
        const { balanceFor } = setup(baselineTotals())
        expect(balanceFor('2026-06-01')).toEqual({
            color: 'amber',
            label: 'Balanced',
        })
    })

    it('is Surplus exactly at the ratio 0.2 boundary (diff = 40)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 140, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'orange',
            label: 'Surplus',
        })
    })

    it('is Very Surplus exactly at the ratio 0.6 boundary (diff = 120)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 220, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'red',
            label: 'Very Surplus',
        })
    })

    it('stays Surplus just under the Very Surplus boundary (ratio 0.59)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 218, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)
        expect(balanceFor('2026-06-04')?.label).toBe('Surplus')
    })

    it('is Unmet Demand exactly at the ratio -0.2 boundary (diff = -40)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 60, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'green',
            label: 'Unmet Demand',
        })
    })

    it('stays Balanced just inside the Unmet Demand boundary (ratio -0.19)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 62, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)
        expect(balanceFor('2026-06-04')?.label).toBe('Balanced')
    })
})

describe('balanceFor: dealer role — sign convention is inverted from farmer', () => {
    it('is Surplus Available exactly at the ratio 0.2 boundary', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 140, demandKg: 100 },
        })
        const { balanceFor } = setup(totals, 'dealer')
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'green',
            label: 'Surplus Available',
        })
    })

    it('is Unmet exactly at the ratio -0.2 boundary', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 60, demandKg: 100 },
        })
        const { balanceFor } = setup(totals, 'dealer')
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'orange',
            label: 'Unmet',
        })
    })

    it('is Very Unmet exactly at the ratio -0.6 boundary (diff = -120)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 40, demandKg: 160 },
        })
        const { balanceFor } = setup(totals, 'dealer')
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'red',
            label: 'Very Unmet',
        })
    })

    it('is Balanced at ratio 0', () => {
        const { balanceFor } = setup(baselineTotals(), 'dealer')
        expect(balanceFor('2026-06-01')).toEqual({
            color: 'amber',
            label: 'Balanced',
        })
    })
})

describe('balanceFor: admin role', () => {
    it('is No Activity when avgTotal is 0 (no day in the set has any volume)', () => {
        const totals = {
            '2026-06-01': { supplyKg: 0, demandKg: 0 },
            '2026-06-02': { supplyKg: 0, demandKg: 0 },
        }
        const { balanceFor } = setup(totals, 'admin')
        expect(balanceFor('2026-06-01')).toEqual({
            color: 'amber',
            label: 'No Activity',
        })
    })

    it('is Very High Activity exactly at the ratio 1.4 boundary (totalKg = 280)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 200, demandKg: 80 },
        })
        const { balanceFor } = setup(totals, 'admin')
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'red',
            label: 'Very High Activity',
        })
    })

    it('is Very Low Activity exactly at the ratio 0.6 boundary (totalKg = 120)', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 60, demandKg: 60 },
        })
        const { balanceFor } = setup(totals, 'admin')
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'amber',
            label: 'Very Low Activity',
        })
    })

    it('is Average Activity strictly between the two thresholds', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 100, demandKg: 100 },
        })
        const { balanceFor } = setup(totals, 'admin')
        expect(balanceFor('2026-06-04')).toEqual({
            color: 'green',
            label: 'Average Activity',
        })
    })
})

describe('monthly average excludes zero-volume days from the baseline', () => {
    it('does not let padded zero days drag avgTotal toward 0', () => {
        const totals = baselineTotals({
            '2026-06-10': { supplyKg: 0, demandKg: 0 },
            '2026-06-11': { supplyKg: 0, demandKg: 0 },
            '2026-06-12': { supplyKg: 0, demandKg: 0 },
            '2026-06-13': { supplyKg: 0, demandKg: 0 },
            '2026-06-14': { supplyKg: 0, demandKg: 0 },
            '2026-06-15': { supplyKg: 140, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)
        expect(balanceFor('2026-06-15')).toEqual({
            color: 'orange',
            label: 'Surplus',
        })
    })
})

describe('legend', () => {
    it('returns the 4-entry farmer legend in the documented order', () => {
        const { legend } = setup(baselineTotals(), 'farmer')
        expect(legend.value).toEqual([
            { color: 'red', label: 'Very Surplus' },
            { color: 'orange', label: 'Surplus' },
            { color: 'amber', label: 'Balanced' },
            { color: 'green', label: 'Unmet Demand' },
        ])
    })

    it('returns the 4-entry dealer legend, inverted from farmer', () => {
        const { legend } = setup(baselineTotals(), 'dealer')
        expect(legend.value).toEqual([
            { color: 'red', label: 'Very Unmet' },
            { color: 'orange', label: 'Unmet' },
            { color: 'amber', label: 'Balanced' },
            { color: 'green', label: 'Surplus Available' },
        ])
    })

    it('returns the 3-entry admin legend', () => {
        const { legend } = setup(baselineTotals(), 'admin')
        expect(legend.value).toEqual([
            { color: 'red', label: 'Very High Activity' },
            { color: 'green', label: 'Average Activity' },
            { color: 'amber', label: 'Very Low Activity' },
        ])
    })
})

describe('reactivity: role changes without re-mounting the composable', () => {
    it('re-evaluates balanceFor and legend when the role ref changes', () => {
        const { roleRef, balanceFor, legend } = setup(
            baselineTotals({ '2026-06-04': { supplyKg: 140, demandKg: 100 } }),
            'farmer',
        )

        expect(balanceFor('2026-06-04')?.label).toBe('Surplus')
        expect(legend.value[0].label).toBe('Very Surplus')

        roleRef.value = 'dealer'

        expect(balanceFor('2026-06-04')?.label).toBe('Surplus Available')
        expect(legend.value[0].label).toBe('Very Unmet')
    })
})

describe('BALANCE_DOT_CLASS export', () => {
    it('maps every balance color to its Tailwind dot class', () => {
        expect(BALANCE_DOT_CLASS).toEqual({
            red: 'bg-red-500',
            orange: 'bg-orange-500',
            amber: 'bg-amber-500',
            green: 'bg-green-500',
        })
    })

    it('has an entry for every color balanceFor can actually return', () => {
        const totals = baselineTotals({
            '2026-06-04': { supplyKg: 220, demandKg: 100 },
            '2026-06-05': { supplyKg: 140, demandKg: 100 },
            '2026-06-06': { supplyKg: 60, demandKg: 100 },
        })
        const { balanceFor } = setup(totals)

        const colorsInUse = [
            balanceFor('2026-06-01')?.color,
            balanceFor('2026-06-04')?.color,
            balanceFor('2026-06-05')?.color,
            balanceFor('2026-06-06')?.color,
        ]

        colorsInUse.forEach((color) => {
            expect(color).toBeTruthy()
            expect(BALANCE_DOT_CLASS).toHaveProperty(color as string)
        })
    })
})
