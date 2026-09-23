import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import {
    INITIAL_WINDOW_MONTHS,
    PAGED_STEP_MONTHS,
    useMonthlyChartPagination,
} from '@/composables/useMonthlyChartPagination'

function setup(offset = 0, maxOffset = 0, forecastLocked = false) {
    const offsetRef = ref(offset)
    const maxOffsetRef = ref(maxOffset)
    const lockedRef = ref(forecastLocked)

    const api = useMonthlyChartPagination({
        offset: offsetRef,
        maxOffset: maxOffsetRef,
        forecastLocked: lockedRef,
    })

    return { offsetRef, maxOffsetRef, lockedRef, ...api }
}

describe('useMonthlyChartPagination', () => {
    describe('defaults / undefined inputs', () => {
        it('treats an undefined offset and maxOffset as 0, not NaN', () => {
            const api = useMonthlyChartPagination({
                offset: () => undefined,
                maxOffset: () => undefined,
                forecastLocked: () => false,
            })

            expect(api.canGoNext.value).toBe(false)
            expect(api.canGoPrevious.value).toBe(false)
        })
    })

    describe('canGoNext', () => {
        it('is false at the default window (offset 0) — nothing more recent to page to', () => {
            const { canGoNext } = setup(0, 24)
            expect(canGoNext.value).toBe(false)
        })

        it('is true once paged into history (offset > 0)', () => {
            const { canGoNext } = setup(6, 24)
            expect(canGoNext.value).toBe(true)
        })
    })

    describe('canGoPrevious', () => {
        it('is true when there is more history and paging is unlocked', () => {
            const { canGoPrevious } = setup(0, 24, false)
            expect(canGoPrevious.value).toBe(true)
        })

        it('is false once offset has reached maxOffset — no more history', () => {
            const { canGoPrevious } = setup(24, 24, false)
            expect(canGoPrevious.value).toBe(false)
        })

        it('is false whenever forecastLocked is true, regardless of remaining history', () => {
            const { canGoPrevious } = setup(0, 24, true)
            expect(canGoPrevious.value).toBe(false)
        })
    })

    describe('previousOffset — the 6-then-12 step pattern', () => {
        it('steps by INITIAL_WINDOW_MONTHS (6) on the very first "previous" click', () => {
            const { previousOffset } = setup(0, 36)
            expect(previousOffset()).toBe(INITIAL_WINDOW_MONTHS)
        })

        it('steps by a full PAGED_STEP_MONTHS (12) on every click after the first', () => {
            const { previousOffset } = setup(6, 36)
            expect(previousOffset()).toBe(6 + PAGED_STEP_MONTHS)
        })

        it('clamps to maxOffset rather than overshooting past available history', () => {
            const { previousOffset } = setup(30, 36)
            expect(previousOffset()).toBe(36)
        })

        it('returns null instead of a bogus offset when already at maxOffset', () => {
            const { previousOffset } = setup(36, 36)
            expect(previousOffset()).toBeNull()
        })

        it('returns null when forecast/history paging is locked, even with history available', () => {
            const { previousOffset } = setup(0, 36, true)
            expect(previousOffset()).toBeNull()
        })
    })

    describe('nextOffset — stepping back toward the present', () => {
        it('returns null at offset 0 — already at the present, nothing to do', () => {
            const { nextOffset } = setup(0, 36)
            expect(nextOffset()).toBeNull()
        })

        it('steps back by a full PAGED_STEP_MONTHS when comfortably clear of 0', () => {
            const { nextOffset } = setup(24, 36)
            expect(nextOffset()).toBe(24 - PAGED_STEP_MONTHS)
        })

        it('floors at exactly 0 rather than going negative when the step would overshoot', () => {
            const { nextOffset } = setup(INITIAL_WINDOW_MONTHS, 36)
            expect(nextOffset()).toBe(0)
        })
    })

    describe('round trip — previous then next lands back where you started', () => {
        it('going previous once then next once returns to offset 0', () => {
            const api = setup(0, 36)
            const afterPrevious = api.previousOffset()
            expect(afterPrevious).not.toBeNull()

            api.offsetRef.value = afterPrevious!
            expect(api.nextOffset()).toBe(0)
        })
    })

    describe('reactivity — recomputes when refs change without re-invoking the composable', () => {
        it('canGoPrevious flips false→true when forecastLocked is cleared', () => {
            const { canGoPrevious, lockedRef } = setup(0, 24, true)
            expect(canGoPrevious.value).toBe(false)

            lockedRef.value = false
            expect(canGoPrevious.value).toBe(true)
        })

        it('canGoNext flips true→false as offset is paged back down to 0', () => {
            const { canGoNext, offsetRef } = setup(6, 24)
            expect(canGoNext.value).toBe(true)

            offsetRef.value = 0
            expect(canGoNext.value).toBe(false)
        })
    })
})
