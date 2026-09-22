import { describe, expect, it } from 'vitest'
import { getInitials } from '@/composables/useInitials'

describe('getInitials', () => {
    it('returns an empty string for undefined', () => {
        expect(getInitials(undefined)).toBe('')
    })

    it('returns an empty string for an empty string', () => {
        expect(getInitials('')).toBe('')
    })

    it('returns a single uppercase letter for a one-word name', () => {
        expect(getInitials('juan')).toBe('J')
    })

    it('returns first + last initials for a full name', () => {
        expect(getInitials('Juan Dela Cruz')).toBe('JC')
    })

    it('uppercases initials regardless of input case', () => {
        expect(getInitials('juan cruz')).toBe('JC')
    })

    it('trims leading/trailing whitespace before splitting', () => {
        expect(getInitials('  Juan Cruz  ')).toBe('JC')
    })
})