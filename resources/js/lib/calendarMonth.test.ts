import { describe, expect, it } from 'vitest'
import { stepMonth } from '@/lib/calendarMonth'

describe('stepMonth', () => {
    describe('mid-year, no rollover', () => {
        it('steps forward within the same year', () => {
            expect(stepMonth(2026, 6, 1)).toEqual({ year: 2026, month: 7 })
        })

        it('steps backward within the same year', () => {
            expect(stepMonth(2026, 6, -1)).toEqual({ year: 2026, month: 5 })
        })
    })

    describe('December → January rollover (the classic off-by-one bug)', () => {
        it('rolls December forward into January of the NEXT year', () => {
            expect(stepMonth(2026, 12, 1)).toEqual({ year: 2027, month: 1 })
        })

        it('does not just wrap the month and forget to increment the year', () => {
            const result = stepMonth(2026, 12, 1)
            expect(result.month).toBe(1)
            expect(result.year).toBe(2027)
        })
    })

    describe('January → December rollover', () => {
        it('rolls January backward into December of the PREVIOUS year', () => {
            expect(stepMonth(2026, 1, -1)).toEqual({ year: 2025, month: 12 })
        })

        it('does not leave month at 0 or the year unchanged', () => {
            const result = stepMonth(2026, 1, -1)
            expect(result.month).not.toBe(0)
            expect(result.year).toBe(2025)
        })
    })

    describe('round trip — forward then back lands exactly where you started', () => {
        it('holds across the December→January boundary', () => {
            const forward = stepMonth(2026, 12, 1)
            const back = stepMonth(forward.year, forward.month, -1)
            expect(back).toEqual({ year: 2026, month: 12 })
        })

        it('holds across the January→December boundary', () => {
            const backward = stepMonth(2026, 1, -1)
            const forward = stepMonth(backward.year, backward.month, 1)
            expect(forward).toEqual({ year: 2026, month: 1 })
        })

        it('holds for an ordinary mid-year month', () => {
            const forward = stepMonth(2026, 6, 1)
            const back = stepMonth(forward.year, forward.month, -1)
            expect(back).toEqual({ year: 2026, month: 6 })
        })
    })

    describe('chained navigation — repeated stepping never produces an invalid month', () => {
        it('12 consecutive forward steps return to the same month, one year later', () => {
            let state = { year: 2026, month: 1 }
            for (let i = 0; i < 12; i++) {
                state = stepMonth(state.year, state.month, 1)
            }
            expect(state).toEqual({ year: 2027, month: 1 })
        })

        it('every intermediate month value stays within 1–12', () => {
            let state = { year: 2026, month: 3 }
            for (let i = 0; i < 24; i++) {
                state = stepMonth(state.year, state.month, 1)
                expect(state.month).toBeGreaterThanOrEqual(1)
                expect(state.month).toBeLessThanOrEqual(12)
            }
        })
    })
})