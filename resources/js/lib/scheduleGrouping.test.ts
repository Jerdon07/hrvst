import { describe, expect, it } from 'vitest'
import { groupPostersByType, mostRelevantStatus } from '@/lib/scheduleGrouping'
import type { CalendarScheduleItem } from '@/types'

function item(overrides: Partial<CalendarScheduleItem> = {}): CalendarScheduleItem {
    return {
        post_id: 1,
        type: 'supply',
        variety_name: null,
        quantity_kg: 10,
        status: 'ongoing',
        poster_name: 'Farmer A',
        poster_phone: '09171234567',
        ...overrides,
    } as CalendarScheduleItem
}

describe('mostRelevantStatus', () => {
    it('prefers ongoing over expired', () => {
        expect(mostRelevantStatus('ongoing', 'expired')).toBe('ongoing')
        expect(mostRelevantStatus('expired', 'ongoing')).toBe('ongoing')
    })

    it('prefers ongoing over fulfilled', () => {
        expect(mostRelevantStatus('ongoing', 'fulfilled')).toBe('ongoing')
    })

    it('prefers expired over fulfilled', () => {
        expect(mostRelevantStatus('expired', 'fulfilled')).toBe('expired')
        expect(mostRelevantStatus('fulfilled', 'expired')).toBe('expired')
    })

    it('returns the same status when both sides match', () => {
        expect(mostRelevantStatus('fulfilled', 'fulfilled')).toBe('fulfilled')
    })

    it('treats an unrecognized status as lowest priority (falls back to the known one)', () => {
        expect(mostRelevantStatus('ongoing', 'something-new')).toBe('ongoing')
        expect(mostRelevantStatus('something-new', 'ongoing')).toBe('ongoing')
    })

    it('does not throw when both statuses are unrecognized — ties go to the first argument', () => {
        expect(mostRelevantStatus('weird-a', 'weird-b')).toBe('weird-a')
    })
})

describe('groupPostersByType', () => {
    it('filters strictly to the requested type, excluding the other side entirely', () => {
        const items = [
            item({ post_id: 1, type: 'supply' }),
            item({ post_id: 2, type: 'demand' }),
        ]
        const supply = groupPostersByType(items, 'supply')
        const demand = groupPostersByType(items, 'demand')

        expect(supply).toHaveLength(1)
        expect(supply[0].post_id).toBe(1)
        expect(demand).toHaveLength(1)
        expect(demand[0].post_id).toBe(2)
    })

    it('sums quantity_kg across multiple line items from the same post_id', () => {
        const items = [
            item({ post_id: 1, quantity_kg: 40 }),
            item({ post_id: 1, quantity_kg: 60 }),
        ]
        const groups = groupPostersByType(items, 'supply')

        expect(groups).toHaveLength(1)
        expect(groups[0].total_kg).toBe(100)
    })

    it('keeps distinct posters (different post_id) as separate groups', () => {
        const items = [
            item({ post_id: 1, quantity_kg: 40 }),
            item({ post_id: 2, quantity_kg: 60 }),
        ]
        const groups = groupPostersByType(items, 'supply')
        expect(groups).toHaveLength(2)
    })

    it('folds status by priority when merging multiple items for one poster', () => {
        const items = [
            item({ post_id: 1, status: 'fulfilled', quantity_kg: 10 }),
            item({ post_id: 1, status: 'ongoing', quantity_kg: 10 }),
        ]
        const groups = groupPostersByType(items, 'supply')

        expect(groups[0].status).toBe('ongoing')
    })

    it('sorts groups largest total_kg first', () => {
        const items = [
            item({ post_id: 1, quantity_kg: 30 }),
            item({ post_id: 2, quantity_kg: 90 }),
            item({ post_id: 3, quantity_kg: 60 }),
        ]
        const groups = groupPostersByType(items, 'supply')

        expect(groups.map((g) => g.post_id)).toEqual([2, 3, 1])
    })

    it('returns an empty array for an empty input, without throwing', () => {
        expect(groupPostersByType([], 'supply')).toEqual([])
    })

    it('takes the first-seen poster_name/poster_phone rather than overwriting on merge', () => {
        const items = [
            item({ post_id: 1, poster_name: 'Farmer A', poster_phone: '0917', quantity_kg: 10 }),
            item({ post_id: 1, poster_name: 'Farmer A (typo)', poster_phone: '0918', quantity_kg: 10 }),
        ]
        const groups = groupPostersByType(items, 'supply')

        expect(groups[0].poster_name).toBe('Farmer A')
        expect(groups[0].poster_phone).toBe('0917')
    })
})