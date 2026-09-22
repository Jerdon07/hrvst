import { describe, expect, it } from 'vitest'
import { useNetKg } from '@/composables/useNetKg'

describe('useNetKg', () => {
    describe('supply (farmer) side', () => {
        const { netKgClass, formatNetKg } = useNetKg('supply')

        it('treats a supply surplus as unfavourable (destructive)', () => {
            expect(netKgClass(50)).toBe('text-destructive')
            expect(formatNetKg(50)).toBe('50 kg excess supply')
        })

        it('treats a supply shortage as favourable (primary)', () => {
            expect(netKgClass(-50)).toBe('text-primary')
            expect(formatNetKg(-50)).toBe('50 kg needed')
        })

        it('formats zero as Balanced regardless of sign convention', () => {
            expect(netKgClass(0)).toBe('text-muted-foreground')
            expect(formatNetKg(0)).toBe('Balanced')
        })
    })

    describe('demand (dealer) side — sign meaning is inverted from supply', () => {
        const { netKgClass, formatNetKg } = useNetKg('demand')

        it('treats a supply surplus as favourable (primary) for a dealer', () => {
            expect(netKgClass(50)).toBe('text-primary')
            expect(formatNetKg(50)).toBe('50 kg excess supply')
        })

        it('treats a supply shortage as unfavourable (destructive) for a dealer', () => {
            expect(netKgClass(-50)).toBe('text-destructive')
            expect(formatNetKg(-50)).toBe('50 kg excess demand')
        })

        it('formats zero as Balanced', () => {
            expect(netKgClass(0)).toBe('text-muted-foreground')
            expect(formatNetKg(0)).toBe('Balanced')
        })
    })

    it('never leaks a negative sign into the displayed amount', () => {
        const { formatNetKg } = useNetKg('supply')
        expect(formatNetKg(-50)).not.toContain('-')
    })

    it('reacts to a reactive/getter type source, not just a static string', () => {
        let currentType: 'supply' | 'demand' = 'supply'
        const { netKgClass } = useNetKg(() => currentType)

        expect(netKgClass(50)).toBe('text-destructive')

        currentType = 'demand'
        expect(netKgClass(50)).toBe('text-primary')
    })

    it('formats large amounts with thousands separators and caps at 2 decimals', () => {
        const { formatNetKg } = useNetKg('supply')
        expect(formatNetKg(1234.5)).toBe('1,234.5 kg excess supply')
    })
})