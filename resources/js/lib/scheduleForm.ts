let _keyCounter = 0

export function nextItemKey(): number {
    return ++_keyCounter
}

export interface ScheduleFormItem {
    _key: number
    id: number | null
    vegetable_id: string
    quantity_kg: number | null
}

export function blankScheduleItem(): ScheduleFormItem {
    return {
        _key: nextItemKey(),
        id: null,
        vegetable_id: '',
        quantity_kg: null,
    }
}
