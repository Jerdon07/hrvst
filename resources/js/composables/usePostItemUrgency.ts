/**
 * Parses a 'YYYY-MM-DD' (or any Date-constructible) string as a LOCAL
 * calendar date rather than letting the JS Date constructor apply its
 * date-only-string-parses-as-UTC quirk (ECMA-262 §21.4.1.15). Mixing that
 * UTC-parsed value against a locally-computed "today" midnight silently
 * skews every diff by the runtime's UTC offset — in UTC+8 (Asia/Manila,
 * where this app runs) that understates daysOverdue by up to a full day.
 */
function toLocalMidnight(dateStr: string): number {
    const [year, month, day] = dateStr.split('-').map(Number)

    if (!year || !month || !day) {
        // Not a plain YYYY-MM-DD string — fall back to native parsing
        // rather than producing NaN.
        const d = new Date(dateStr)
        return new Date(
            d.getFullYear(),
            d.getMonth(),
            d.getDate(),
        ).getTime()
    }

    return new Date(year, month - 1, day).getTime()
}

export function daysOverdue(scheduledDate: string | null): number {
    if (!scheduledDate) return 0

    const todayMidnight = new Date().setHours(0, 0, 0, 0)
    const scheduledMidnight = toLocalMidnight(scheduledDate)

    const diff = todayMidnight - scheduledMidnight

    return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
}

export function isDueToday(scheduledDate: string | null): boolean {
    // Preserves the existing null → true contract (see the composable's
    // test suite) rather than silently changing behavior for callers that
    // may rely on it. If no caller actually wants "no date = due today",
    // raise that separately — don't fold a second behavior change in here.
    if (!scheduledDate) return true

    // daysOverdue() clamps negative results to 0, so it cannot distinguish
    // "scheduled for today" from "scheduled for any day in the future" —
    // both come out as 0. Comparing daysOverdue() === 0 here was the bug:
    // it made every future-dated item register as due today. Compare the
    // calendar dates directly instead.
    const todayMidnight = new Date().setHours(0, 0, 0, 0)
    const scheduledMidnight = toLocalMidnight(scheduledDate)

    return scheduledMidnight === todayMidnight
}

export function urgencyClass(days: number): string {
    if (days >= 3) return 'text-red-600 dark:text-red-400'
    if (days >= 1) return 'text-amber-600 dark:text-amber-400'
    return 'text-yellow-600 dark:text-yellow-400'
}

export function urgencyLabel(days: number): string {
    if (days <= 0) return 'Due today'
    if (days === 1) return 'Overdue 1 day'
    return `Overdue ${days} days`
}

export function usePostItemUrgency() {
    return { daysOverdue, isDueToday, urgencyClass, urgencyLabel }
}