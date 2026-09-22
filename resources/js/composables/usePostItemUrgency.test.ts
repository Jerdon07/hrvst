// NOTE: these tests pin a fixed "now" and assert calendar-day math in the
// LOCAL timezone the test runner executes in. They currently fail against
// the source in resources/js/composables/usePostItemUrgency.ts because that
// implementation mixes a local-midnight "now" with a UTC-parsed
// `scheduledDate`. See the fix below the imports — apply it to the
// composable, not to these expectations.
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import {
    daysOverdue,
    isDueToday,
    urgencyClass,
    urgencyLabel,
} from '@/composables/usePostItemUrgency'

// Fixed "now" for deterministic day-boundary math: Jun 15 2026, 14:30 local.
// Anything after noon exercises the "same day, later time" case that a naive
// (now - scheduled) / 86400000 without midnight-normalization gets wrong.
const NOW = new Date(2026, 5, 15, 14, 30, 0)

beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(NOW)
})

afterEach(() => {
    vi.useRealTimers()
})

describe('daysOverdue', () => {
    it('returns 0 for null', () => {
        expect(daysOverdue(null)).toBe(0)
    })

    it('returns 0 for a date scheduled today, regardless of time-of-day', () => {
        // scheduledDate passed as ISO date-only string — no time component,
        // so its Date parse lands at local midnight while "now" is 14:30.
        // A correct implementation compares midnight-to-midnight.
        expect(daysOverdue('2026-06-15')).toBe(0)
    })

    it('returns 0 for a date in the future', () => {
        expect(daysOverdue('2026-06-20')).toBe(0)
    })

    it('never goes negative for a future date', () => {
        expect(daysOverdue('2099-01-01')).toBe(0)
    })

    it('returns 1 for yesterday', () => {
        expect(daysOverdue('2026-06-14')).toBe(1)
    })

    it('returns the exact day count for a date further in the past', () => {
        expect(daysOverdue('2026-06-08')).toBe(7)
    })

    it('floors partial days rather than rounding', () => {
        // "now" is mid-afternoon; a date 1 day + several hours back must
        // still report exactly 1, not 2.
        expect(daysOverdue('2026-06-14')).toBe(1)
    })
})

describe('isDueToday', () => {
    it('is true when scheduled for today', () => {
        expect(isDueToday('2026-06-15')).toBe(true)
    })

    it('is false once overdue by at least a day', () => {
        expect(isDueToday('2026-06-14')).toBe(false)
    })

    it('is false for a future date', () => {
        expect(isDueToday('2026-06-16')).toBe(false)
    })

    it('is true for null (daysOverdue(null) === 0)', () => {
        // Documents current behavior: a schedule with no date reads as
        // "due today" rather than "not due." Flag this if it's not intended —
        // it's an easy footgun for a caller rendering an urgency badge.
        expect(isDueToday(null)).toBe(true)
    })
})

describe('urgencyClass', () => {
    it('uses the red tier at 3+ days overdue', () => {
        expect(urgencyClass(3)).toBe('text-red-600 dark:text-red-400')
        expect(urgencyClass(10)).toBe('text-red-600 dark:text-red-400')
    })

    it('uses the amber tier between 1 and 2 days overdue', () => {
        expect(urgencyClass(1)).toBe('text-amber-600 dark:text-amber-400')
        expect(urgencyClass(2)).toBe('text-amber-600 dark:text-amber-400')
    })

    it('uses the yellow tier at 0 days overdue', () => {
        expect(urgencyClass(0)).toBe('text-yellow-600 dark:text-yellow-400')
    })

    it('is correct exactly at the 1-day and 3-day boundaries', () => {
        // Regression guard against off-by-one flips (>= vs >).
        expect(urgencyClass(0)).not.toBe(urgencyClass(1))
        expect(urgencyClass(2)).not.toBe(urgencyClass(3))
    })
})

describe('urgencyLabel', () => {
    it('reads "Due today" for 0 days', () => {
        expect(urgencyLabel(0)).toBe('Due today')
    })

    it('reads "Due today" for negative input too (defensive floor)', () => {
        expect(urgencyLabel(-1)).toBe('Due today')
    })

    it('uses singular phrasing for exactly 1 day', () => {
        expect(urgencyLabel(1)).toBe('Overdue 1 day')
    })

    it('uses plural phrasing for 2+ days', () => {
        expect(urgencyLabel(2)).toBe('Overdue 2 days')
        expect(urgencyLabel(30)).toBe('Overdue 30 days')
    })
})

describe('integration: daysOverdue feeding urgencyClass/urgencyLabel', () => {
    it('produces a consistent red badge + correct day count for a stale item', () => {
        const days = daysOverdue('2026-06-10') // 5 days back
        expect(days).toBe(5)
        expect(urgencyClass(days)).toBe('text-red-600 dark:text-red-400')
        expect(urgencyLabel(days)).toBe('Overdue 5 days')
    })
})