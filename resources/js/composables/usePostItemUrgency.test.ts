import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import {
    daysOverdue,
    isDueToday,
    urgencyClass,
    urgencyLabel,
} from '@/composables/usePostItemUrgency'

describe('usePostItemUrgency', () => {
    beforeEach(() => {
        vi.useFakeTimers()
        vi.setSystemTime(new Date('2026-06-15T10:00:00'))
    })

    afterEach(() => {
        vi.useRealTimers()
    })

    describe('daysOverdue', () => {
        it('returns 0 for null', () => {
            expect(daysOverdue(null)).toBe(0)
        })

        it('returns 0 for a date scheduled today', () => {
            expect(daysOverdue('2026-06-15')).toBe(0)
        })

        it('returns 0 (clamped) for a future date, never negative', () => {
            expect(daysOverdue('2026-06-20')).toBe(0)
        })

        it('returns 1 for yesterday', () => {
            expect(daysOverdue('2026-06-14')).toBe(1)
        })

        it('returns the correct count for a date several days past', () => {
            expect(daysOverdue('2026-06-10')).toBe(5)
        })
    })

    describe('isDueToday', () => {
        it('is true only when daysOverdue is exactly 0', () => {
            expect(isDueToday('2026-06-15')).toBe(true)
            expect(isDueToday('2026-06-14')).toBe(false)
        })
    })

    describe('urgencyClass', () => {
        it('uses the mildest (yellow) class for 0 days', () => {
            expect(urgencyClass(0)).toContain('yellow')
        })

        it('escalates to amber for 1-2 days', () => {
            expect(urgencyClass(1)).toContain('amber')
            expect(urgencyClass(2)).toContain('amber')
        })

        it('escalates to red at 3+ days', () => {
            expect(urgencyClass(3)).toContain('red')
            expect(urgencyClass(10)).toContain('red')
        })
    })

    describe('urgencyLabel', () => {
        it('labels 0 or negative days as "Due today"', () => {
            expect(urgencyLabel(0)).toBe('Due today')
            expect(urgencyLabel(-1)).toBe('Due today')
        })

        it('uses singular phrasing for exactly 1 day', () => {
            expect(urgencyLabel(1)).toBe('Overdue 1 day')
        })

        it('uses plural phrasing for more than 1 day', () => {
            expect(urgencyLabel(5)).toBe('Overdue 5 days')
        })
    })
})
