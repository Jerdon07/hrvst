import { computed, type MaybeRefOrGetter, ref, toValue } from 'vue'

export function useExpandableList<T>(
    items: MaybeRefOrGetter<T[] | null | undefined>,
    initialVisible: MaybeRefOrGetter<number | null | undefined>,
) {
    const expanded = ref(false)

    const all = computed(() => toValue(items) ?? [])
    const limit = computed(() => toValue(initialVisible))

    const visible = computed(() => {
        if (!all.value.length) return []
        if (!limit.value || expanded.value) return all.value
        return all.value.slice(0, limit.value)
    })

    const hasMore = computed(
        () => !!limit.value && all.value.length > limit.value,
    )
    const hiddenCount = computed(() => all.value.length - (limit.value ?? 0))

    function toggle(): void {
        expanded.value = !expanded.value
    }

    return { expanded, visible, hasMore, hiddenCount, toggle }
}
