import type { PostItemStatus } from '../enums'
import type {
    DealerPostItemResource,
    PostDataFixed,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '../resources/marketplace'
import type {
    CategoryOption,
    VegetableStabilityData,
    VegetableWasteData,
} from '../resources/product'
import type { FarmerSupplySummary } from '../resources/profile'
import type { Paginated } from '../shared'

// ─── farmer/Dashboard ─────────────────────────────────────────────────────────

export interface FarmerDashboardProps {
    topWastedDemand?: VegetableWasteData[]
    mostStableWastedDemand?: VegetableStabilityData[]
    topOversupplied?: VegetableWasteData[]
    analyticsLocked: boolean
    upgradeFeatureLabel: string
}

// ─── farmer/supplies/Index ────────────────────────────────────────────────────

export interface FarmerSuppliesFilters {
    status: PostItemStatus
}

export interface FarmerSuppliesProps {
    filters: FarmerSuppliesFilters
    summary: FarmerSupplySummary
    vegetableOptions: VegetableOptionsByCategory
    varietyOptions: VarietyOptionsByVegetable
    needsAction?: PostDataFixed[]
    supplies: Paginated<PostDataFixed> | null
}

// ─── farmer/marketplace/Index ─────────────────────────────────────────────────

export interface FarmerMarketplaceFilters {
    search: string | null
    category_id: number | null
    vegetable_id: number | null
    date_from: string | null
    date_to: string | null
}

export interface FarmerMarketplaceProps {
    filters: FarmerMarketplaceFilters
    categoryOptions: CategoryOption[]
    demands: Paginated<DealerPostItemResource>
}
