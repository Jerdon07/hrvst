import type { CalendarScheduleItem } from '@/types'

export interface PosterGroup {
    post_id: number
    poster_name: string
    poster_phone: string
    total_kg: number
    status: string
}

const STATUS_PRIORITY: Record<string, number> = {
    ongoing: 0,
    expired: 1,
    fulfilled: 2,
}

export function mostRelevantStatus(a: string, b: string): string {
    return (STATUS_PRIORITY[a] ?? 99) <= (STATUS_PRIORITY[b] ?? 99) ? a : b
}

export function groupPostersByType(
    items: CalendarScheduleItem[],
    type: 'supply' | 'demand',
): PosterGroup[] {
    const groups = new Map<number, PosterGroup>()

    for (const item of items) {
        if (item.type !== type) continue

        const existing = groups.get(item.post_id)
        if (existing) {
            existing.total_kg += item.quantity_kg
            existing.status = mostRelevantStatus(existing.status, item.status)
            continue
        }

        groups.set(item.post_id, {
            post_id: item.post_id,
            poster_name: item.poster_name,
            poster_phone: item.poster_phone,
            total_kg: item.quantity_kg,
            status: item.status,
        })
    }

    return Array.from(groups.values()).sort((a, b) => b.total_kg - a.total_kg)
}
