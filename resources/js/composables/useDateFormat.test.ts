import { describe, expect, it } from 'vitest'
import { toInputDate } from '@/composables/useDateFormat'

describe('toInputDate', () => {
    it('returns an empty string for null or undefined', () => {
        expect(toInputDate(null)).toBe('')
        expect(toInputDate(undefined)).toBe('')
    })

    it('returns an empty string for an empty string', () => {
        expect(toInputDate('')).toBe('')
    })

    it('returns an empty string for an unparseable date', () => {
        expect(toInputDate('not-a-date')).toBe('')
    })

    it('formats an ISO date string as YYYY-MM-DD', () => {
        expect(toInputDate('2026-01-05T00:00:00')).toBe('2026-01-05')
    })

    it('zero-pads single-digit months and days', () => {
        expect(toInputDate('2026-03-04T00:00:00')).toBe('2026-03-04')
    })
})
