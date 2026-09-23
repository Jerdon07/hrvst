import { describe, expect, it } from 'vitest'
import {
    buildVarietyLabelMap,
    filterByLabel,
    isOverlapReady,
    netKgFor,
    othersCount,
    overlapFor,
} from '@/lib/scheduleItemsEditorHelpers'
import type { VegetableOverlapData } from '@/types'

function overlapEntry(
    overrides: Partial<VegetableOverlapData> = {},
): VegetableOverlapData {
    return {
        post_item_id: 1,
        vegetable_id: 42,
        total_supplies_kg: 100,
        total_demands_kg: 40,
        posters: [],
        supply_posters: [],
        demand_posters: [],
        ...overrides,
    }
}

describe('overlapFor', () => {
    it('returns undefined when vegetableId is an empty string, without indexing overlap[0]', () => {
        const overlap = { 0: overlapEntry() }
        expect(overlapFor(overlap, '')).toBeUndefined()
    })

    it('looks up by numeric key even though vegetableId is a string (combobox values are strings)', () => {
        const overlap = { 42: overlapEntry() }
        expect(overlapFor(overlap, '42')).toEqual(overlapEntry())
    })

    it('returns undefined for a vegetableId with no matching overlap entry', () => {
        const overlap = { 42: overlapEntry() }
        expect(overlapFor(overlap, '99')).toBeUndefined()
    })

    it('returns undefined when overlap itself is undefined (not yet loaded)', () => {
        expect(overlapFor(undefined, '42')).toBeUndefined()
    })
})

describe('isOverlapReady', () => {
    it('requires all three of vegetableId, scheduledDate, and timeSlot', () => {
        expect(isOverlapReady('42', '2026-06-01', 'morning')).toBe(true)
        expect(isOverlapReady('', '2026-06-01', 'morning')).toBe(false)
        expect(isOverlapReady('42', '', 'morning')).toBe(false)
        expect(isOverlapReady('42', '2026-06-01', '')).toBe(false)
    })
})

describe('othersCount', () => {
    it('counts posters on the matched overlap entry', () => {
        const overlap = { 42: overlapEntry({ posters: [{}, {}] as never }) }
        expect(othersCount(overlap, '42')).toBe(2)
    })

    it('is 0, not undefined/NaN, when there is no overlap entry yet', () => {
        expect(othersCount(undefined, '42')).toBe(0)
        expect(othersCount({}, '42')).toBe(0)
    })
})

describe('netKgFor', () => {
    it('computes supplies minus demands', () => {
        const overlap = {
            42: overlapEntry({ total_supplies_kg: 100, total_demands_kg: 40 }),
        }
        expect(netKgFor(overlap, '42')).toBe(60)
    })

    it('returns null — not 0 — when there is no overlap data yet, distinguishing "unknown" from "balanced"', () => {
        expect(netKgFor(undefined, '42')).toBeNull()
    })

    it('returns 0 (not null) for a genuinely balanced entry', () => {
        const overlap = {
            42: overlapEntry({ total_supplies_kg: 50, total_demands_kg: 50 }),
        }
        expect(netKgFor(overlap, '42')).toBe(0)
    })
})

describe('buildVarietyLabelMap', () => {
    it('flattens a category-grouped options object into a single id → name map', () => {
        const options = {
            'Leafy Greens': [
                { id: 1, name: 'Pechay' },
                { id: 2, name: 'Kangkong' },
            ],
            Root: [{ id: 3, name: 'Carrot' }],
        }
        const map = buildVarietyLabelMap(options)

        expect(map.get('1')).toBe('Pechay')
        expect(map.get('2')).toBe('Kangkong')
        expect(map.get('3')).toBe('Carrot')
    })

    it('returns an empty map for undefined input, without throwing', () => {
        expect(buildVarietyLabelMap(undefined).size).toBe(0)
    })

    it('keys are strings even when source ids are numbers (Combobox values are always strings)', () => {
        const map = buildVarietyLabelMap({ Cat: [{ id: 7, name: 'X' }] })
        expect(map.has('7')).toBe(true)
        expect(map.has(7 as unknown as string)).toBe(false)
    })
})

describe('filterByLabel', () => {
    const labelMap = new Map([
        ['1', 'Pechay'],
        ['2', 'Kangkong'],
        ['3', 'Carrot'],
    ])
    const items = [{ value: '1' }, { value: '2' }, { value: '3' }]

    it('matches by resolved label, not by the raw value', () => {
        const result = filterByLabel(items, 'pech', labelMap)
        expect(result).toEqual([{ value: '1' }])
    })

    it('is case-insensitive', () => {
        expect(filterByLabel(items, 'CARROT', labelMap)).toEqual([
            { value: '3' },
        ])
    })

    it('matches a substring anywhere in the label, not only a prefix', () => {
        expect(filterByLabel(items, 'ngko', labelMap)).toEqual([{ value: '2' }])
    })

    it('returns everything for an empty search term', () => {
        expect(filterByLabel(items, '', labelMap)).toHaveLength(3)
    })

    it('excludes an item whose value has no entry in the label map when the term is non-empty', () => {
        const withUnknown = [...items, { value: '999' }]
        expect(filterByLabel(withUnknown, 'pech', labelMap)).not.toContainEqual(
            { value: '999' },
        )
    })

    it('returns an empty array when nothing matches', () => {
        expect(filterByLabel(items, 'zzz', labelMap)).toEqual([])
    })
})
