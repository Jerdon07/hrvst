// ─── Analytics ───────────────────────────────────────────────────────────────

import { PostTimeSlot } from "../enums"

export type ImbalanceBand = App.Enums.Analytics.ImbalanceBand
export type RecommendationSeverity = App.Enums.Analytics.RecommendationSeverity
export type VarietyRecommendation =
    App.DTOs.Vegetable.VegetableRecommendationDTO
export type VarietyAnalytics = App.DTOs.Vegetable.VegetableAnalyticsDTO

// ─── Market calendar — mirrors VegetableCalendarService::mergeIntoSchedule() ──

export type CalendarTimeSlot = PostTimeSlot

export type CalendarScheduleItem = App.Data.Vegetable.VegetableCalendarItemData

export interface CalendarSlotData {
    supply_kg: number
    demand_kg: number
    net_kg: number
    supply_posts_count: number
    demand_posts_count: number
    items: CalendarScheduleItem[]
}

export type VegetableDaySchedule = Partial<
    Record<CalendarTimeSlot, CalendarSlotData>
>

export interface VegetableCalendarFilters {
    year: number
    month: number
}

// ─── VegetableResource ────────────────────────────────────────────────────────

export interface VegetableCategory {
    id: number
    name: string
    slug: string
}

export interface SupplyMunicipality {
    name: string
    total_kg: number
}

export interface MonthlyActivity {
    month: string
    label: string
    supply_expired_kg: number
    supply_fulfilled_kg: number
    demand_expired_kg: number
    demand_fulfilled_kg: number
}

export type VegetableDetailDataFixed = Omit<
    App.Data.Vegetable.VegetableDetailData,
    'analytics' | 'forecast' | 'vegetable_calendar'
> & {
    analytics: VarietyAnalytics | null
    forecast: App.DTOs.Vegetable.VegetableForecastDTO | null
    vegetable_calendar: Record<string, VegetableDaySchedule>
}

export type VegetableIndexData = App.Data.Vegetable.VegetableIndexData

export type VegetableWasteData = App.Data.Vegetable.VegetableWasteData
export type VegetableStabilityData = App.Data.Vegetable.VegetableStabilityData

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOptions = Record<string, Record<string, string>>

export interface CategoryOption {
    id: number
    name: string
    slug: string
}

export interface VegetableSummary {
    total_varieties: number
    total_vegetables: number
}

// ─── Flat admin table row ───────────────────────────────────────────────────

export interface VegetableTableRow {
    id: number
    vegetable_name: string
    variety_name: string | null
    local_name: string | null
    name: string
    category: { id: number; name: string } | null
    image_url: string | null
    supply_count?: number
    demand_count?: number
}

export interface ForecastPoint {
    month: string
    label: string
    supply_fulfilled_kg: number
    supply_expired_kg: number
    demand_fulfilled_kg: number
    demand_expired_kg: number
}

// --------------

export interface MonthlyVolumeData {
    month: string
    label: string
    value_kg: number
}

export type UserInsights = App.Data.Profile.UserInsightsData
export type TopVegetableData = App.Data.Profile.TopVegetableData
