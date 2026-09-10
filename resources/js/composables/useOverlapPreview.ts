import axios from 'axios'
import { ref, watch } from 'vue'
import PostOverlapController from '@/actions/App/Http/Controllers/Api/PostOverlapController'
import type { PostTimeSlot, VegetableOverlapData } from '@/types'

interface UseOverlapPreviewOptions {
    type: 'supply' | 'demand'
    postId?: number
    scheduledDate: () => string
    timeSlot: () => PostTimeSlot | ''
    vegetableIds: () => string[]
}

export function useOverlapPreview(options: UseOverlapPreviewOptions) {
    const overlap = ref<Record<number, VegetableOverlapData>>({})
    const loading = ref(false)

    // Monotonic token guards against out-of-order responses: if request 2
    // fires after request 1 but resolves first, request 1's late response
    // must not clobber it. Only the response matching the *current* token
    // is allowed to write to `overlap`.
    let requestToken = 0

    async function fetchOverlap(): Promise<void> {
        const scheduledDate = options.scheduledDate()
        const timeSlot = options.timeSlot()
        const vegetableIds = options.vegetableIds().filter(Boolean)

        if (!scheduledDate || !timeSlot || vegetableIds.length === 0) {
            overlap.value = {}
            loading.value = false
            return
        }

        const token = ++requestToken
        loading.value = true

        try {
            const { data } = await axios.get<
                Record<number, VegetableOverlapData>
            >(PostOverlapController.url(), {
                params: {
                    type: options.type,
                    scheduled_date: scheduledDate,
                    time_slot: timeSlot,
                    vegetable_ids: vegetableIds,
                    post_id: options.postId,
                },
            })

            if (token === requestToken) {
                overlap.value = data
            }
        } catch {
            if (token === requestToken) {
                overlap.value = {}
            }
        } finally {
            if (token === requestToken) {
                loading.value = false
            }
        }
    }

    watch(
        () => [
            options.scheduledDate(),
            options.timeSlot(),
            ...options.vegetableIds(),
        ],
        fetchOverlap,
        { immediate: true },
    )

    return { overlap, loading }
}
