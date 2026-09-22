import { beforeEach, describe, expect, it, vi } from 'vitest'
import { updateTheme } from '@/composables/useAppearance'

function mockMatchMedia(matches: boolean) {
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
        matches,
        media: query,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
    })) as unknown as typeof window.matchMedia
}

describe('updateTheme', () => {
    beforeEach(() => {
        document.documentElement.classList.remove('dark')
    })

    it('adds the dark class for "dark"', () => {
        updateTheme('dark')
        expect(document.documentElement.classList.contains('dark')).toBe(true)
    })

    it('removes the dark class for "light"', () => {
        document.documentElement.classList.add('dark')
        updateTheme('light')
        expect(document.documentElement.classList.contains('dark')).toBe(false)
    })

    it('resolves "system" to dark when the OS prefers dark', () => {
        mockMatchMedia(true)
        updateTheme('system')
        expect(document.documentElement.classList.contains('dark')).toBe(true)
    })

    it('resolves "system" to light when the OS prefers light', () => {
        mockMatchMedia(false)
        updateTheme('system')
        expect(document.documentElement.classList.contains('dark')).toBe(false)
    })

    it('is idempotent — calling twice with the same value does not toggle', () => {
        updateTheme('dark')
        updateTheme('dark')
        expect(document.documentElement.classList.contains('dark')).toBe(true)
    })
})