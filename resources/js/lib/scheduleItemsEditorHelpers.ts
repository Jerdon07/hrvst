import type { VarietyOptionsByVegetable, VegetableOverlapData } from '@/types'

// ─── Overlap derivation ─────────────────────────────────────────────────────

export function overlapFor(
    overlap: Record<number, VegetableOverlapData> | undefined,
    vegetableId: string,
): VegetableOverlapData | undefined {
    if (!vegetableId) return undefined
    return overlap?.[Number(vegetableId)]
}

export function isOverlapReady(
    vegetableId: string,
    scheduledDate: string,
    timeSlot: string,
): boolean {
    return !!vegetableId && !!scheduledDate && !!timeSlot
}

export function othersCount(
    overlap: Record<number, VegetableOverlapData> | undefined,
    vegetableId: string,
): number {
    return overlapFor(overlap, vegetableId)?.posters.length ?? 0
}

export function netKgFor(
    overlap: Record<number, VegetableOverlapData> | undefined,
    vegetableId: string,
): number | null {
    const data = overlapFor(overlap, vegetableId)
    return data ? data.total_supplies_kg - data.total_demands_kg : null
}

// ─── Variety label lookup + Combobox filter ────────────────────────────────

export function buildVarietyLabelMap(
    varietyOptions: VarietyOptionsByVegetable | undefined,
): Map<string, string> {
    const map = new Map<string, string>()
    for (const varieties of Object.values(varietyOptions ?? {})) {
        for (const variety of varieties)
            map.set(String(variety.id), variety.name)
    }
    return map
}

export function filterByLabel<T extends { value: unknown }>(
    items: T[],
    term: string,
    labelById: Map<string, string>,
): T[] {
    const needle = term.toLowerCase()
    return items.filter((item) =>
        (labelById.get(String(item.value)) ?? '')
            .toLowerCase()
            .includes(needle),
    )
}
