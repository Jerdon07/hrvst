import { describe, expect, it } from 'vitest'
import {
    buildGroups,
    centroid,
    getLevel,
    ZOOM_BARANGAY,
    ZOOM_MUNICIPALITY,
} from '@/lib/farmerMapGrouping'
import type { FarmerMarker } from '@/types/resources/marketplace'

function marker(overrides: Partial<FarmerMarker> = {}): FarmerMarker {
    return {
        id: 1,
        coordinates: { lat: 16.4, lng: 120.6 },
        farmer_name: 'Farmer A',
        province_id: 1,
        province: 'Benguet',
        municipality_id: 1,
        municipality: 'La Trinidad',
        barangay_id: 1,
        barangay: 'Poblacion',
        ongoing_supplies_count: 1,
        supplies_summary: [],
        ...overrides,
    }
}

describe('getLevel', () => {
    it('is "province" below the municipality threshold', () => {
        expect(getLevel(ZOOM_MUNICIPALITY - 1)).toBe('province')
    })

    it('is "municipality" exactly at the municipality threshold', () => {
        expect(getLevel(ZOOM_MUNICIPALITY)).toBe('municipality')
    })

    it('is "municipality" just below the barangay threshold', () => {
        expect(getLevel(ZOOM_BARANGAY - 1)).toBe('municipality')
    })

    it('is "barangay" exactly at and above the barangay threshold', () => {
        expect(getLevel(ZOOM_BARANGAY)).toBe('barangay')
        expect(getLevel(ZOOM_BARANGAY + 5)).toBe('barangay')
    })
})

describe('centroid', () => {
    it('returns the single farmer\'s own coordinates for a group of one', () => {
        const [lat, lng] = centroid([marker({ coordinates: { lat: 10, lng: 20 } })])
        expect(lat).toBe(10)
        expect(lng).toBe(20)
    })

    it('averages coordinates across multiple farmers', () => {
        const [lat, lng] = centroid([
            marker({ coordinates: { lat: 10, lng: 20 } }),
            marker({ coordinates: { lat: 20, lng: 40 } }),
        ])
        expect(lat).toBe(15)
        expect(lng).toBe(30)
    })
})

describe('buildGroups', () => {
    it('buckets by province_id at the "province" level', () => {
        const markers = [
            marker({ id: 1, province_id: 1 }),
            marker({ id: 2, province_id: 1 }),
            marker({ id: 3, province_id: 2 }),
        ]
        const groups = buildGroups(markers, 'province')

        expect(groups).toHaveLength(2)
        expect(groups.find((g) => g.key === 1)?.farmers).toHaveLength(2)
        expect(groups.find((g) => g.key === 2)?.farmers).toHaveLength(1)
    })

    it('buckets by municipality_id at the "municipality" level, ignoring province grouping', () => {
        const markers = [
            marker({ id: 1, province_id: 1, municipality_id: 10 }),
            marker({ id: 2, province_id: 1, municipality_id: 20 }),
        ]
        const groups = buildGroups(markers, 'municipality')

        expect(groups).toHaveLength(2)
    })

    it('buckets by barangay_id at the "barangay" level', () => {
        const markers = [
            marker({ id: 1, barangay_id: 100 }),
            marker({ id: 2, barangay_id: 100 }),
            marker({ id: 3, barangay_id: 200 }),
        ]
        const groups = buildGroups(markers, 'barangay')

        expect(groups).toHaveLength(2)
    })

    it('sums ongoing_supplies_count within each group', () => {
        const markers = [
            marker({ id: 1, province_id: 1, ongoing_supplies_count: 3 }),
            marker({ id: 2, province_id: 1, ongoing_supplies_count: 5 }),
        ]
        const groups = buildGroups(markers, 'province')

        expect(groups[0].totalSupplies).toBe(8)
    })

    it('names the group after the first farmer\'s label for that level', () => {
        const markers = [marker({ province_id: 1, province: 'Benguet' })]
        const groups = buildGroups(markers, 'province')
        expect(groups[0].name).toBe('Benguet')
    })

    it('falls back to a "Province N" / "Barangay N" label when the name is null', () => {
        const provinceMarkers = [marker({ province_id: 7, province: null })]
        const barangayMarkers = [marker({ barangay_id: 9, barangay: null })]

        expect(buildGroups(provinceMarkers, 'province')[0].name).toBe('Province 7')
        expect(buildGroups(barangayMarkers, 'barangay')[0].name).toBe('Barangay 9')
    })

    it('does not apply the null-fallback to municipality — that field is never null on the type', () => {
        const markers = [marker({ municipality_id: 3, municipality: 'Itogon' })]
        expect(buildGroups(markers, 'municipality')[0].name).toBe('Itogon')
    })

    it('returns an empty array for no markers, without throwing', () => {
        expect(buildGroups([], 'province')).toEqual([])
    })

    it('centroid of a group matches the averaged coordinates of its members', () => {
        const markers = [
            marker({ id: 1, province_id: 1, coordinates: { lat: 10, lng: 100 } }),
            marker({ id: 2, province_id: 1, coordinates: { lat: 20, lng: 120 } }),
        ]
        const [group] = buildGroups(markers, 'province')

        expect(group.lat).toBe(15)
        expect(group.lng).toBe(110)
    })
})