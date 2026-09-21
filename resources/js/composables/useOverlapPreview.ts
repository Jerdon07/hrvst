import axios from 'axios'
import { onScopeDispose, ref, watch } from 'vue'
import PostOverlapController from '@/actions/App/Http/Controllers/Api/PostOverlapController'
import type { PostTimeSlot, VegetableOverlapData } from '@/types'

const DEFAULT_DEBOUNCE_MS = 250

interface UseOverlapPreviewOptions {
    type: 'supply' | 'demand'
    postId?: number
    scheduledDate: () => string
    timeSlot: () => PostTimeSlot | ''
    vegetableIds: () => string[]
    debounceMs?: number
}

export function useOverlapPreview(options: UseOverlapPreviewOptions) {
    const overlap = ref<Record<number, VegetableOverlapData>>({})
    const loading = ref(false)
    const debounceMs = options.debounceMs ?? DEFAULT_DEBOUNCE_MS

    let requestToken = 0
    let timer: ReturnType<typeof setTimeout> | null = null

    function cancelPending(): void {
        if (timer) {
            clearTimeout(timer)
            timer = null
        }
        requestToken++
    }

    function currentInputs() {
        return {
            scheduledDate: options.scheduledDate(),
            timeSlot: options.timeSlot(),
            // Deduped + sorted: adding a blank row or reordering rows must not
            // change the request, and must not trigger one.
            vegetableIds: [
                ...new Set(options.vegetableIds().filter(Boolean)),
            ].sort(),
        }
    }

    async function fetchOverlap(
        inputs: ReturnType<typeof currentInputs>,
    ): Promise<void> {
        const token = ++requestToken

        try {
            const { data } = await axios.get<
                Record<number, VegetableOverlapData>
            >(PostOverlapController.url(), {
                params: {
                    type: options.type,
                    scheduled_date: inputs.scheduledDate,
                    time_slot: inputs.timeSlot,
                    vegetable_ids: inputs.vegetableIds,
                    post_id: options.postId,
                },
            })

            if (token === requestToken) overlap.value = data
        } catch {
            if (token === requestToken) overlap.value = {}
        } finally {
            if (token === requestToken) loading.value = false
        }
    }

    watch(
        () => {
            const { scheduledDate, timeSlot, vegetableIds } = currentInputs()
            return `${scheduledDate}|${timeSlot}|${vegetableIds.join(',')}`
        },
        (_key, previousKey) => {
            cancelPending()

            const inputs = currentInputs()

            if (
                !inputs.scheduledDate ||
                !inputs.timeSlot ||
                inputs.vegetableIds.length === 0
            ) {
                overlap.value = {}
                loading.value = false
                return
            }

            loading.value = true

            const delay = previousKey === undefined ? 0 : debounceMs

            timer = setTimeout(() => {
                timer = null
                void fetchOverlap(inputs)
            }, delay)
        },
        { immediate: true },
    )

    onScopeDispose(cancelPending)

    return { overlap, loading }
}
