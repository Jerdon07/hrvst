import { flushPromises } from '@vue/test-utils'
import axios from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { effectScope, nextTick, ref } from 'vue'
import { useOverlapPreview } from '@/composables/useOverlapPreview'

vi.mock('axios')

vi.mock('@/actions/App/Http/Controllers/Api/PostOverlapController', () => ({
    default: { url: () => '/api/posts/overlap' },
}))

const mockedAxios = vi.mocked(axios)

function makeOptions(overrides: Partial<Parameters<typeof useOverlapPreview>[0]> = {}) {
    const scheduledDate = ref('2026-06-01')
    const timeSlot = ref<'morning' | 'afternoon' | 'evening' | ''>('morning')
    const vegetableIds = ref<string[]>(['1'])

    return {
        state: { scheduledDate, timeSlot, vegetableIds },
        options: {
            type: 'supply' as const,
            scheduledDate: () => scheduledDate.value,
            timeSlot: () => timeSlot.value,
            vegetableIds: () => vegetableIds.value,
            debounceMs: 250,
            ...overrides,
        },
    }
}

describe('useOverlapPreview', () => {
    beforeEach(() => {
        vi.useFakeTimers()
        mockedAxios.get.mockResolvedValue({ data: {} })
    })

    afterEach(() => {
        vi.useRealTimers()
        vi.clearAllMocks()
    })

    it('fires the first request immediately, without waiting for the debounce', async () => {
        const { options } = makeOptions()

        effectScope().run(() => {
            useOverlapPreview(options)
        })
        await nextTick()

        // Immediate watcher run: previousKey is undefined, so delay is 0 —
        // no need to advance fake timers at all for the initial fetch.
        await flushPromises()
        expect(mockedAxios.get).toHaveBeenCalledTimes(1)
    })

    it('collapses rapid successive changes into a single debounced request', async () => {
        const { state, options } = makeOptions()

        effectScope().run(() => {
            useOverlapPreview(options)
        })
        await nextTick()
        await flushPromises()
        mockedAxios.get.mockClear() // drop the immediate first-run call

        // Simulate the user changing vegetables three times in quick
        // succession, faster than the 250ms debounce window.
        state.vegetableIds.value = ['1', '2']
        await nextTick()
        vi.advanceTimersByTime(100)

        state.vegetableIds.value = ['1', '2', '3']
        await nextTick()
        vi.advanceTimersByTime(100)

        state.vegetableIds.value = ['1', '2', '3', '4']
        await nextTick()
        vi.advanceTimersByTime(250)
        await flushPromises()

        expect(mockedAxios.get).toHaveBeenCalledTimes(1)
        const params = mockedAxios.get.mock.calls[0][1]?.params
        expect(params.vegetable_ids).toEqual(['1', '2', '3', '4'])
    })

    it('does not request when scheduled_date is missing', async () => {
        const { options } = makeOptions({ scheduledDate: () => '' })

        effectScope().run(() => {
            useOverlapPreview(options)
        })
        await nextTick()
        await flushPromises()

        expect(mockedAxios.get).not.toHaveBeenCalled()
    })

    it('does not request when time_slot is missing', async () => {
        const { options } = makeOptions({ timeSlot: () => '' })

        effectScope().run(() => {
            useOverlapPreview(options)
        })
        await nextTick()
        await flushPromises()

        expect(mockedAxios.get).not.toHaveBeenCalled()
    })

    it('does not request when there are no vegetable ids', async () => {
        const { options } = makeOptions({ vegetableIds: () => [] })

        effectScope().run(() => {
            useOverlapPreview(options)
        })
        await nextTick()
        await flushPromises()

        expect(mockedAxios.get).not.toHaveBeenCalled()
    })

    it('deduplicates and sorts vegetable ids so order/repeats do not change the request', async () => {
        const { options } = makeOptions({ vegetableIds: () => ['3', '1', '3', '2'] })

        effectScope().run(() => {
            useOverlapPreview(options)
        })
        await nextTick()
        await flushPromises()

        const params = mockedAxios.get.mock.calls[0][1]?.params
        expect(params.vegetable_ids).toEqual(['1', '2', '3'])
    })

    it('sets loading synchronously when inputs change, before the debounced request fires', async () => {
        const { state, options } = makeOptions()

        const { loading } = effectScope().run(() => useOverlapPreview(options))!
        await nextTick()
        await flushPromises()
        expect(loading.value).toBe(false)

        state.vegetableIds.value = ['1', '2']
        await nextTick()

        expect(loading.value).toBe(true)
        vi.advanceTimersByTime(250)
        await flushPromises()
        expect(loading.value).toBe(false)
    })

    it('discards a stale response that resolves after a newer request has been sent', async () => {
        const { state, options } = makeOptions()

        let resolveFirst!: (v: { data: Record<string, unknown> }) => void
        mockedAxios.get.mockImplementationOnce(
            () => new Promise((resolve) => { resolveFirst = resolve }),
        )

        const { overlap } = effectScope().run(() => useOverlapPreview(options))!
        await nextTick()

        mockedAxios.get.mockResolvedValueOnce({ data: { 2: { total_supplies_kg: 999 } } })
        state.vegetableIds.value = ['1', '2']
        await nextTick()
        vi.advanceTimersByTime(250)
        await flushPromises()

        expect(overlap.value).toEqual({ 2: { total_supplies_kg: 999 } })

        resolveFirst({ data: { 1: { total_supplies_kg: 1 } } })
        await flushPromises()

        expect(overlap.value).toEqual({ 2: { total_supplies_kg: 999 } })
    })

    it('cancels a pending debounced request when the owning scope is disposed', async () => {
        const { state, options } = makeOptions()
        const scope = effectScope()

        scope.run(() => {
            useOverlapPreview(options)
        })
        await nextTick()
        await flushPromises()
        mockedAxios.get.mockClear()

        state.vegetableIds.value = ['1', '2']
        await nextTick()

        scope.stop()
        vi.advanceTimersByTime(500)
        await flushPromises()

        expect(mockedAxios.get).not.toHaveBeenCalled()
    })

    it('falls back to an empty object when the request fails', async () => {
        mockedAxios.get.mockReset()
        mockedAxios.get.mockRejectedValueOnce(new Error('network error'))

        const { options } = makeOptions()
        const { overlap, loading } = effectScope().run(() => useOverlapPreview(options))!
        await nextTick()
        await flushPromises()

        expect(overlap.value).toEqual({})
        expect(loading.value).toBe(false)
    })
})