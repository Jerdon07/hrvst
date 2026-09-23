import type { FarmerMarker } from '@/types/resources/marketplace'

export type FarmerGroupLevel = 'province' | 'municipality' | 'barangay'

export interface FarmerGroup {
    key: number
    name: string
    lat: number
    lng: number
    farmers: FarmerMarker[]
    totalSupplies: number
    level: FarmerGroupLevel
}

export const ZOOM_MUNICIPALITY = 10
export const ZOOM_BARANGAY = 13

export function getLevel(zoom: number): FarmerGroupLevel {
    if (zoom < ZOOM_MUNICIPALITY) return 'province'
    if (zoom < ZOOM_BARANGAY) return 'municipality'
    return 'barangay'
}

export function centroid(farmers: FarmerMarker[]): [number, number] {
    const lat =
        farmers.reduce((s, f) => s + f.coordinates.lat, 0) / farmers.length
    const lng =
        farmers.reduce((s, f) => s + f.coordinates.lng, 0) / farmers.length
    return [lat, lng]
}

export function buildGroups(
    markers: FarmerMarker[],
    level: FarmerGroupLevel,
): FarmerGroup[] {
    const buckets = new Map<number, FarmerMarker[]>()

    for (const f of markers) {
        const key =
            level === 'province'
                ? f.province_id
                : level === 'municipality'
                  ? f.municipality_id
                  : f.barangay_id

        if (!buckets.has(key)) buckets.set(key, [])
        buckets.get(key)!.push(f)
    }

    return Array.from(buckets.entries()).map(([key, farmers]) => {
        const [lat, lng] = centroid(farmers)
        const name =
            level === 'province'
                ? (farmers[0].province ?? `Province ${key}`)
                : level === 'municipality'
                  ? farmers[0].municipality
                  : (farmers[0].barangay ?? `Barangay ${key}`)

        return {
            key,
            name,
            lat,
            lng,
            farmers,
            totalSupplies: farmers.reduce(
                (s, f) => s + f.ongoing_supplies_count,
                0,
            ),
            level,
        }
    })
}
