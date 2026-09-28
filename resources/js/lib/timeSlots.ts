import type { PostTimeSlot } from '@/types'

export const TIME_SLOT_CONFIG: Record<PostTimeSlot, { label: string; hours: string }> = {
    morning: { label: 'Morning', hours: '6 AM – 12 PM' },
    afternoon: { label: 'Afternoon', hours: '12 PM – 6 PM' },
    evening: { label: 'Evening', hours: '6 PM – 10 PM' },
}

export const TIME_SLOT_ORDER = Object.keys(TIME_SLOT_CONFIG) as PostTimeSlot[]