import { ref, watch } from 'vue'
import { slotSummary } from '@/actions/App/Http/Controllers/Api/VegetableAvailabilityController'

export interface AvailabilityData {
    supply_kg: number
    demand_kg: number
    net_kg: number
}

type AvailabilityState =
    | { status: 'idle' }
    | { status: 'loading' }
    | { status: 'loaded'; data: AvailabilityData }
    | { status: 'error' }

export function netKgClassFarmer(netKg: number): string {
    if (netKg > 0) return 'text-destructive'
    if (netKg < 0) return 'text-primary'
    return 'text-muted-foreground'
}

export function netKgClassDealer(netKg: number): string {
    if (netKg > 0) return 'text-primary'
    if (netKg < 0) return 'text-destructive'
    return 'text-muted-foreground'
}

export function formatNetKgFarmer(net: number): string {
    const abs = Math.abs(net)
    const formatted = abs.toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
    if (net > 0) return `${formatted} kg excess supply`
    if (net < 0) return `${formatted} kg needed`
    return 'Balanced'
}

export function formatNetKgDealer(net: number): string {
    const abs = Math.abs(net)
    const formatted = abs.toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
    if (net > 0) return `${formatted} kg supply`
    if (net < 0) return `${formatted} kg excess demand`
    return 'Balanced'
}

export function useVegetableAvailability(
    getDate: () => string,
    getTimeSlot: () => string,
    getVegetableIds: () => string[],
) {
    const cache = ref<Record<string, AvailabilityState>>({})

    function isComplete(vegetableId: string): boolean {
        return !!vegetableId && !!getDate() && !!getTimeSlot()
    }

    function cacheKey(vegetableId: string): string {
        return `${vegetableId}:${getDate()}:${getTimeSlot()}`
    }

    function getState(vegetableId: string): AvailabilityState {
        if (!isComplete(vegetableId)) return { status: 'idle' }
        return cache.value[cacheKey(vegetableId)] ?? { status: 'idle' }
    }

    function getData(vegetableId: string): AvailabilityData | null {
        const state = getState(vegetableId)
        return state.status === 'loaded' ? state.data : null
    }

    async function fetchOne(vegetableId: string): Promise<void> {
        if (!isComplete(vegetableId)) return

        const key = cacheKey(vegetableId)
        const existing = cache.value[key]
        if (existing?.status === 'loaded' || existing?.status === 'loading')
            return

        cache.value[key] = { status: 'loading' }

        try {
            const params: Record<string, string> = {
                date: getDate(),
                time_slot: getTimeSlot(),
            }

            const url = slotSummary(Number(vegetableId), { query: params }).url
            const res = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            if (!res.ok) throw new Error(`HTTP ${res.status}`)

            cache.value[key] = { status: 'loaded', data: await res.json() }
        } catch {
            cache.value[key] = { status: 'error' }
        }
    }

    // `immediate: true` on both watchers: Edit pages mount with vegetable_id /
    // scheduled_date / time_slot already populated from the loaded record, so
    // without an immediate first run nothing fetches until the user changes a
    // field. On an edit form that's often never — the user just hits Save.
    watch(
        getVegetableIds,
        (newIds, oldIds = []) => {
            newIds.forEach((id, index) => {
                if (id && id !== oldIds[index]) void fetchOne(id)
            })
        },
        { immediate: true },
    )

    watch(
        [getDate, getTimeSlot],
        () => {
            getVegetableIds()
                .filter(Boolean)
                .forEach((id) => void fetchOne(id))
        },
        { immediate: true },
    )

    return { getState, getData }
}