import { computed, type MaybeRefOrGetter, toValue } from 'vue'

export const INITIAL_WINDOW_MONTHS = 6
export const PAGED_STEP_MONTHS = 12

export interface UseMonthlyChartPaginationOptions {
    offset: MaybeRefOrGetter<number | undefined>
    maxOffset: MaybeRefOrGetter<number | undefined>
    forecastLocked: MaybeRefOrGetter<boolean | undefined>
}

export function useMonthlyChartPagination(options: UseMonthlyChartPaginationOptions) {
    const offset = computed(() => toValue(options.offset) ?? 0)
    const maxOffset = computed(() => toValue(options.maxOffset) ?? 0)

    const canGoNext = computed(() => offset.value > 0)
    const canGoPrevious = computed(
        () => !toValue(options.forecastLocked) && offset.value < maxOffset.value,
    )

    function previousOffset(): number | null {
        if (!canGoPrevious.value) return null

        return offset.value === 0
            ? INITIAL_WINDOW_MONTHS
            : Math.min(offset.value + PAGED_STEP_MONTHS, maxOffset.value)
    }

    function nextOffset(): number | null {
        if (!canGoNext.value) return null

        const next = offset.value - PAGED_STEP_MONTHS
        return next > 0 ? next : 0
    }

    return {
        canGoNext,
        canGoPrevious,
        previousOffset,
        nextOffset,
    }
}