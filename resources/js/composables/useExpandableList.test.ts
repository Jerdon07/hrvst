import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import { useExpandableList } from '@/composables/useExpandableList'

describe('useExpandableList', () => {
    describe('empty / undefined inputs', () => {
        it('returns an empty visible list for undefined items, without throwing', () => {
            const { visible, hasMore } = useExpandableList(() => undefined, () => 3)
            expect(visible.value).toEqual([])
            expect(hasMore.value).toBe(false)
        })

        it('returns an empty visible list for null items', () => {
            const { visible } = useExpandableList(() => null, () => 3)
            expect(visible.value).toEqual([])
        })
    })

    describe('no limit (falsy initialVisible) — show everything, no toggle', () => {
        it('shows all items when initialVisible is undefined', () => {
            const { visible, hasMore } = useExpandableList(() => [1, 2, 3, 4], () => undefined)
            expect(visible.value).toEqual([1, 2, 3, 4])
            expect(hasMore.value).toBe(false)
        })

        it('shows all items when initialVisible is 0 (falsy, not "show none")', () => {
            const { visible, hasMore } = useExpandableList(() => [1, 2, 3], () => 0)
            expect(visible.value).toEqual([1, 2, 3])
            expect(hasMore.value).toBe(false)
        })
    })

    describe('truncation when collapsed', () => {
        it('slices to the first N items when collapsed', () => {
            const { visible } = useExpandableList(() => [1, 2, 3, 4, 5], () => 3)
            expect(visible.value).toEqual([1, 2, 3])
        })

        it('hasMore is true only when the list actually exceeds the limit', () => {
            const exceeds = useExpandableList(() => [1, 2, 3, 4], () => 3)
            const exact = useExpandableList(() => [1, 2, 3], () => 3)
            const under = useExpandableList(() => [1, 2], () => 3)

            expect(exceeds.hasMore.value).toBe(true)
            expect(exact.hasMore.value).toBe(false)
            expect(under.hasMore.value).toBe(false)
        })

        it('hiddenCount is the exact remainder, not clamped or rounded', () => {
            const { hiddenCount } = useExpandableList(() => Array.from({ length: 10 }), () => 3)
            expect(hiddenCount.value).toBe(7)
        })
    })

    describe('toggle', () => {
        it('reveals every item once toggled on', () => {
            const { visible, toggle } = useExpandableList(() => [1, 2, 3, 4, 5], () => 2)
            expect(visible.value).toHaveLength(2)

            toggle()
            expect(visible.value).toEqual([1, 2, 3, 4, 5])
        })

        it('toggling twice returns to the truncated view', () => {
            const { visible, toggle } = useExpandableList(() => [1, 2, 3, 4], () => 2)
            toggle()
            toggle()
            expect(visible.value).toEqual([1, 2])
        })

        it('hasMore stays true even while expanded — the button switches to "Show less", it does not disappear', () => {
            const { hasMore, toggle } = useExpandableList(() => [1, 2, 3, 4], () => 2)
            toggle()
            expect(hasMore.value).toBe(true)
        })
    })

    describe('reactivity — updates as the source list changes without re-invoking the composable', () => {
        it('recomputes hasMore/hiddenCount when the underlying ref grows past the limit', () => {
            const items = ref([1, 2])
            const { hasMore, hiddenCount } = useExpandableList(items, () => 3)

            expect(hasMore.value).toBe(false)

            items.value = [1, 2, 3, 4, 5]
            expect(hasMore.value).toBe(true)
            expect(hiddenCount.value).toBe(2)
        })
    })
})