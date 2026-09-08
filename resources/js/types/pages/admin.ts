import type { MunicipalityOption, SupplyOption } from '../resources/marketplace'
import type { CategoryOption, VegetableSummary } from '../resources/product'
import type {
    AdminDealerSummary,
    AdminFarmerSummary,
    DealerResource,
    FarmerResource,
} from '../resources/profile'
import type { KpiStat, MapConfig, Paginated } from '../shared'

// ─── admin/Dashboard ──────────────────────────────────────────────────────────

export interface AdminDashboardKPIs {
    farmers: {
        total_farmers: KpiStat
    }
    dealers: {
        total_dealers: KpiStat
    }
    vegetables: {
        total_vegetables: KpiStat
    }
}

export interface RegistrationTrendPoint {
    month: string
    label: string
    farmers: number
    dealers: number
}

export interface AdminDashboardProps {
    kpis: AdminDashboardKPIs
    registrationTrends: RegistrationTrendPoint[]
}

// ─── admin/vegetables/Index ───────────────────────────────────────────────────

export interface AdminVegetablesFilters {
    search: string | null
    category_id: number | null
}

export interface AdminVegetablesProps {
    categories: CategoryOption[]
    summary: VegetableSummary
    filters: AdminVegetablesFilters
    vegetables: Paginated<App.Data.Vegetable.VegetableIndexData>
}

// ─── admin/farmers/Index ──────────────────────────────────────────────────────

export interface AdminFarmersFilters {
    search: string | null
    municipalities: MunicipalityOption[]
    supplies: Record<string, SupplyOption[]>
}

export interface AdminFarmersProps {
    view: 'list' | 'map'
    filters: AdminFarmersFilters
    mapConfig: MapConfig
    farmers: Paginated<FarmerResource> | null
    summary: AdminFarmerSummary
}

// ─── admin/dealers/Index ──────────────────────────────────────────────────────

export interface AdminDealersFilters {
    search: string | null
}

export interface AdminDealersProps {
    summary: AdminDealerSummary
    dealers: Paginated<DealerResource>
    filters: AdminDealersFilters
}
