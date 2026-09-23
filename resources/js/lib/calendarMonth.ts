export function stepMonth(
    year: number,
    month: number,
    direction: 1 | -1,
): { year: number; month: number } {
    let nextMonth = month + direction
    let nextYear = year

    if (nextMonth > 12) {
        nextMonth = 1
        nextYear++
    }
    if (nextMonth < 1) {
        nextMonth = 12
        nextYear--
    }

    return { year: nextYear, month: nextMonth }
}
