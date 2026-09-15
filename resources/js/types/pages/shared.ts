import type { CategoryOption } from '../resources/product'
import type { Paginated } from '../shared'

export interface SharedVegetablesFilters {
    search: string | null
    category_id: number | null
}

export interface SharedVegetablesProps {
    categories: CategoryOption[]
    filters: SharedVegetablesFilters
    vegetables?: Paginated<App.Data.Vegetable.VegetableIndexData>
}
