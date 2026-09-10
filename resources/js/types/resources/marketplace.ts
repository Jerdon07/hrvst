import type { Coordinates } from '../shared'

// ─── Embedded shapes ──────────────────────────────────────────────────────────

export interface PostVegetableSnapshot {
    id: number
    name: string
    category: string | null
    image_url: string
}

export type PostItemSnapshot = App.Data.PostItem.PostItemData

// ─── FarmerSupplyDataFixed / DealerDemandDataFixed ───────────────────────────

export type PostDataFixed = Omit<
    App.Data.Post.PostScheduleData,
    'post_items'
> & {
    post_items: App.Data.PostItem.PostItemLightData[]
}

export type FarmerExpiringSupplyFixed = Omit<
    App.Data.Farmer.FarmerExpiringSupplyData,
    'image_url' | 'items'
> & {
    image_url: string
    items: App.Data.PostItem.PostItemLightData[]
}

export type DealerExpiringDemandFixed = Omit<
    App.Data.Dealer.DealerExpiringDemandData,
    'image_url' | 'items'
> & {
    image_url: string
    items: App.Data.PostItem.PostItemLightData[]
}

// ─── DealerPostItemResource ───────────────────────────────────────────────────

export type DealerPostItemResource = App.Data.PostItem.PostItemData
export type VegetableOverlapData = App.Data.Post.VegetableOverlapData

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOption = { id: number; name: string }
export type VegetableOptionsByCategory = Record<string, VegetableOption[]>
export type VarietyOption = { id: number; name: string }
export type VarietyOptionsByVegetable = Record<string, VarietyOption[]>

// ─── Map types ────────────────────────────────────────────────────────────────

export interface SupplyMarker {
    barangay_id: number
    barangay: string
    municipality_id: number
    municipality: string
    coordinates: Coordinates
    supply_count: number
    total_quantity_kg: number
}

export interface SupplyMapFilterOptions {
    categories: Array<{ id: number; name: string }>
    vegetables: Record<string, Array<{ id: number; name: string }>>
}

export interface SupplyMapFilters {
    category_id: number | null
    vegetable_id: number | null
}

export interface MunicipalityOption {
    id: number
    name: string
    province: string
    label: string
}

export interface SupplyOption {
    id: number
    name: string
    category: string
}

// ─── Farmer map marker ────────────────────────────────────────────────────────

export interface FarmerMarker {
    id: number
    coordinates: Coordinates
    farmer_name: string
    province_id: number
    province: string | null
    municipality_id: number
    municipality: string
    barangay_id: number
    barangay: string | null
    ongoing_supplies_count: number
    supplies_summary: Array<{
        vegetable: string
        count: number
        varieties: string[]
    }>
}
