declare namespace App {
namespace DTOs {
namespace Vegetable {
export type ExpectedBalanceComputation = {
readonly source_label: string,
readonly supply_kg: number,
readonly demand_kg: number,
readonly diff_pct: number | null,
};
export type ExpectedBalanceDTO = {
readonly band: string,
readonly explanation: string,
readonly computation: App.DTOs.Vegetable.ExpectedBalanceComputation | null,
};
export type VegetableAnalyticsDTO = {
supply_demand_ratio: number,
imbalance_band: App.Enums.Analytics.ImbalanceBand,
supply_fulfillment_rate: number | null,
demand_fulfillment_rate: number | null,
supply_volume_mom_pct: number | null,
demand_volume_mom_pct: number | null,
recommendations: App.DTOs.Vegetable.VegetableRecommendationDTO[],
expected_balance: App.DTOs.Vegetable.ExpectedBalanceDTO,
};
export type VegetableForecastDTO = {
readonly months_of_history: number,
readonly forecast_confidence: string,
readonly forecast: {
month: string,
label: string,
supply_fulfilled_kg: number,
supply_expired_kg: number,
demand_fulfilled_kg: number,
demand_expired_kg: number,
}[],
};
export type VegetableRecommendationDTO = {
readonly severity: App.Enums.Analytics.RecommendationSeverity,
readonly type: string,
readonly title: string,
readonly body: string,
};
}
}
namespace Data {
namespace Billing {
export type CurrentSubscriptionData = {
plan: string | null,
status: string | null,
is_active: boolean,
ends_at: string | null,
ends_at_human: string | null,
};
}
namespace Dealer {
export type DealerExpiringDemandData = {
id: number,
scheduled_date: string | null,
time_slot: App.Enums.PostTimeSlot | null,
time_slot_label: string | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: App.Data.PostItem.PostItemLightData[] | undefined,
};
}
namespace Farmer {
export type FarmerExpiringSupplyData = {
id: number,
scheduled_date: string | null,
time_slot: App.Enums.PostTimeSlot | null,
time_slot_label: string | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: App.Data.PostItem.PostItemLightData[] | undefined,
};
}
namespace Post {
export type DealerDemandData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
needs_action: boolean,
post_items: App.Data.PostItem.PostItemLightData[] | unknown,
};
export type FarmerSupplyData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
needs_action: boolean,
post_items: App.Data.PostItem.PostItemLightData[],
};
export type OverlapPosterData = {
poster_name: string,
poster_phone: string,
quantity_kg: number,
};
export type PostData = {
id: number,
type: App.Enums.PostType,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
days_until_transaction: number | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: App.Data.PostItem.PostItemData[] | undefined,
};
export type VegetableOverlapData = {
post_item_id: number,
vegetable_id: number,
total_kg: number,
posters: App.Data.Post.OverlapPosterData[],
};
}
namespace PostItem {
export type PostItemData = {
id: number,
image_url: string,
post_id: number,
status: App.Enums.PostItemStatus,
vegetable_id: number,
display_name: string,
quantity_kg: number,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
days_until_transaction: number | null,
created_at: string,
created_at_human: string,
};
export type PostItemLightData = {
id: number,
vegetable_id: number,
display_name: string | null,
vegetable_image_url: string | null,
quantity_kg: number,
status: App.Enums.PostItemStatus | null,
};
}
namespace Profile {
export type CoordinatesData = {
lat: number,
lng: number,
};
export type DealerData = {
id: number,
joined_at: string,
joined_at_human: string,
user: App.Data.Profile.UserData | undefined,
ongoing_demands_count: undefined | number,
demands: App.Data.PostItem.PostItemLightData[] | undefined,
demand_items: App.Data.PostItem.PostItemData[] | undefined,
insights: App.Data.Profile.UserInsightsData | undefined,
analytics_locked: boolean,
};
export type FarmerData = {
id: number,
joined_at: string,
joined_at_human: string,
user: App.Data.Profile.UserData | undefined,
full_address: string,
barangay: undefined | string | null,
municipality: undefined | string | null,
province: undefined | string | null,
coordinates: App.Data.Profile.CoordinatesData,
ongoing_supplies_count: undefined | number,
supplies: undefined | Array<any>,
supply_items: App.Data.PostItem.PostItemData[] | undefined,
insights: App.Data.Profile.UserInsightsData | undefined,
analytics_locked: boolean,
};
export type MonthlyVolumeData = {
month: string,
label: string,
value_kg: number,
};
export type TopVegetableData = {
id: number,
display_name: string,
image_url: string,
post_count: number,
value_kg: number,
};
export type UserData = {
id: number,
name: string,
email: string | null,
phone_number: string,
avatar_url: string,
};
export type UserInsightsData = {
fulfillment_rate: number | null,
total_posts: number,
posts_per_month: number,
last_active: string | null,
last_active_human: string | null,
top_varieties: App.Data.Profile.TopVegetableData[],
monthly_volume: App.Data.Profile.MonthlyVolumeData[],
};
}
namespace RegistrationRequest {
export type RegistrationRequestData = {
id: number,
name: string,
phone_number: string,
email: string | null,
role: string,
created_at: string,
municipality: string | null,
barangay: string | null,
id_type: string | null,
id_type_label: string | null,
id_number: string | null,
document_url: string | null,
};
}
namespace Vegetable {
export type CategoryData = {
id: number,
name: string,
slug: string,
};
export type VegetableCalendarItemData = {
post_id: number,
type: App.Enums.PostType,
variety_name: string | null,
quantity_kg: number,
status: App.Enums.PostItemStatus,
poster_name: string,
poster_phone: string,
};
export type VegetableDetailData = {
id: number,
vegetable_id: number,
vegetable_name: string,
variety_name: string | null,
display_name: string,
category_id: number,
supply_count: number,
demand_count: number,
supply_municipalities: Array<any>,
monthly_activity: Array<any>,
activity_offset: number,
activity_max_offset: number,
vegetable_calendar: unknown,
analytics: undefined,
forecast: undefined,
forecast_locked: boolean,
upgrade_feature: string | null,
upgrade_feature_label: string | null,
};
export type VegetableIndexData = {
id: number,
vegetable_name: string,
variety_name: string | null,
local_name: string | null,
display_name: string,
image_url: string,
category: App.Data.Vegetable.CategoryData | undefined,
supply_count: undefined | number,
demand_count: undefined | number,
};
export type VegetableLightData = {
id: number,
name: string,
category: string | null,
image_url: string | null,
};
export type VegetableStabilityData = {
id: number,
display_name: string,
image_url: string,
value_kg: number,
confidence: string,
months_observed: number,
};
export type VegetableWasteData = {
id: number,
display_name: string,
image_url: string,
value_kg: number,
};
}
}
namespace Enums {
export type PostItemStatus = 'ongoing' | 'fulfilled' | 'expired';
export type PostTimeSlot = 'morning' | 'afternoon' | 'evening';
export type PostType = 'supply' | 'demand';
export type RegistrationRequestStatus = 'pending' | 'approved' | 'rejected';
export type ValidIdType = 'drivers_license' | 'philippine_national_id' | 'philippine_passport' | 'voters_id';
namespace Analytics {
export type ImbalanceBand = 'oversupply' | 'balanced' | 'undersupply';
export type RecommendationSeverity = 'critical' | 'warning' | 'info';
export type VegetableViewerRole = 'admin' | 'farmer' | 'dealer';
}
namespace Billing {
export type SubscriptionFeature = 'admin_analytics' | 'farmer_forecasts' | 'dealer_market_intel';
export type SubscriptionPlan = 'monthly' | 'quarterly' | 'annual';
export type SubscriptionStatus = 'active' | 'cancelled' | 'expired';
}
}
}
