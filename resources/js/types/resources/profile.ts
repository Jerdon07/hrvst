// ─── FarmerResource ───────────────────────────────────────────────────────────

export type FarmerResource = Omit<
    App.Data.Profile.FarmerData,
    'supplies' | 'supply_items'
> & {
    supplies?: App.Data.PostItem.PostItemLightData[]
    supply_items?: App.Data.PostItem.PostItemData[]
}

// ─── DealerResource ───────────────────────────────────────────────────────────

export type DealerResource = Omit<
    App.Data.Profile.DealerData,
    'demands' | 'demand_items'
> & {
    demands?: App.Data.PostItem.PostItemLightData[]
    demand_items?: App.Data.PostItem.PostItemData[]
}

// ─── Summary shapes ───────────────────────────────────────────────────────────

export interface ScheduleStatusSummary {
    total_ongoing: number
    total_fulfilled: number
    total_expired: number
}

export type FarmerSupplySummary = ScheduleStatusSummary
export type DealerDemandSummary = ScheduleStatusSummary

export interface AdminFarmerSummary {
    total_farmers: number
    new_farmers_this_month: number
    total_supplies: number
    new_supplies_this_month: number
}

export interface AdminDealerSummary {
    total_dealers: number
    new_dealers_this_month: number
    total_demands: number
    new_demands_this_month: number
}