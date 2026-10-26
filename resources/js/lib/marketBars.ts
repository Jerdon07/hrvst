export function barPercent(kg: number, max: number): number {
    return max > 0 ? Math.round((kg / max) * 100) : 0
}