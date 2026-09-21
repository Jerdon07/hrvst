import { type MaybeRefOrGetter, toValue } from 'vue'
import type { ScheduleType } from '@/lib/scheduleRegistry'

function formatAmount(kg: number): string {
    return Math.abs(kg).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}

export function useNetKg(type: MaybeRefOrGetter<ScheduleType>) {
    const isSupply = () => toValue(type) === 'supply'

    function netKgClass(net: number): string {
        if (net === 0) return 'text-muted-foreground'

        const favourable = isSupply() ? net < 0 : net > 0

        return favourable ? 'text-primary' : 'text-destructive'
    }

    function formatNetKg(net: number): string {
        if (net === 0) return 'Balanced'

        const amount = formatAmount(net)

        if (net > 0) return `${amount} kg excess supply`

        return isSupply() ? `${amount} kg needed` : `${amount} kg excess demand`
    }

    return { netKgClass, formatNetKg }
}
