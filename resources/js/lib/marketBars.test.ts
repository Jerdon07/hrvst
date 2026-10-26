import { describe, expect, it } from 'vitest'
import { barPercent } from '@/lib/marketBars'

describe('barPercent', () => {
    it('makes the larger side 100% and scales the smaller', () => {
        expect(barPercent(200, 200)).toBe(100)
        expect(barPercent(50, 200)).toBe(25)
    })

    it('returns 0 instead of dividing by zero', () => {
        expect(barPercent(0, 0)).toBe(0)
    })
})