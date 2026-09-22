import { describe, expect, it } from 'vitest'
import { formatKgAxis } from '@/composables/chartSeries'

describe('formatKgAxis', () => {
    it('renders values under 1000 as plain kg', () => {
        expect(formatKgAxis(500)).toBe('500 kg')
        expect(formatKgAxis(0)).toBe('0 kg')
    })

    it('renders values at or above 1000 in k-kg notation', () => {
        expect(formatKgAxis(1000)).toBe('1k kg')
        expect(formatKgAxis(2500)).toBe('2.5k kg')
    })

    it('drops the decimal when the scaled value is whole', () => {
        expect(formatKgAxis(3000)).toBe('3k kg')
    })

    it('handles negative magnitudes using absolute-value threshold', () => {
        expect(formatKgAxis(-1500)).toBe('-1.5k kg')
    })
})
