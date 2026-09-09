<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import type { DateValue} from '@internationalized/date';
import { CalendarDate, toCalendarDate } from '@internationalized/date'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import { CalendarRoot } from 'reka-ui'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
	CalendarCell,
	CalendarCellTrigger,
	CalendarGrid,
	CalendarGridBody,
	CalendarGridHead,
	CalendarGridRow,
	CalendarHeadCell,
} from '@/components/ui/calendar'
import { Card } from '@/components/ui/card'
import {
	BALANCE_DOT_CLASS,
	useCalendarBalance,
	type CalendarViewerRole,
} from '@/composables/useCalendarBalance'
import adminRoutes from '@/routes/admin'
import vegetables from '@/routes/vegetables'
import type { CalendarSlotData, VegetableCalendarFilters, VegetableDaySchedule } from '@/types'

interface Props {
	calendar?: Record<string, VegetableDaySchedule>
	calendarFilters: VegetableCalendarFilters
	vegetableId: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
	'day-select': [dateStr: string]
}>()

// ─── Role + routing ───────────────────────────────────────────────────────────

const isAdmin = computed(() => usePage().props.auth.user.roles.includes('admin'))

function showRoute(): { url: string } {
	return isAdmin.value
		? adminRoutes.vegetables.show(props.vegetableId)
		: vegetables.show(props.vegetableId)
}

// ─── Month navigation (server-driven — reka-ui is display-only here) ─────────

const calendarYear = computed(() => props.calendarFilters.year)
const calendarMonth = computed(() => props.calendarFilters.month)

const placeholder = computed<DateValue>({
    get: () => new CalendarDate(calendarYear.value, calendarMonth.value, 1),
    set: (_value: DateValue) => {},
})

const monthLabel = computed(() =>
	new Date(calendarYear.value, calendarMonth.value - 1, 1).toLocaleString('en-PH', {
		month: 'long',
		year: 'numeric',
	}),
)

function navigateMonth(direction: 1 | -1): void {
	let month = calendarMonth.value + direction
	let year = calendarYear.value

	if (month > 12) {
		month = 1
		year++
	}
	if (month < 1) {
		month = 12
		year--
	}

	router.visit(showRoute().url, {
		data: { year, month },
		preserveState: true,
		preserveScroll: true,
		only: ['vegetable', 'calendarFilters'],
	})
}

// ─── Daily totals + balance classification ───────────────────────────────────

const dailyTotals = computed(() => {
	const map: Record<string, { supplyKg: number; demandKg: number }> = {}
	if (!props.calendar) return map

	for (const [dateStr, daySchedule] of Object.entries(props.calendar)) {
		let supplyKg = 0
		let demandKg = 0
		for (const slotData of Object.values(daySchedule) as CalendarSlotData[]) {
			supplyKg += slotData.supply_kg
			demandKg += slotData.demand_kg
		}
		if (supplyKg > 0 || demandKg > 0) map[dateStr] = { supplyKg, demandKg }
	}
	return map
})

const viewerRole = computed<CalendarViewerRole>(() => {
	const roles = usePage().props.auth.user.roles
	if (roles.includes('admin')) return 'admin'
	if (roles.includes('dealer')) return 'dealer'
	return 'farmer'
})

const { balanceFor, legend } = useCalendarBalance(dailyTotals, viewerRole)

// ─── Cell helpers ─────────────────────────────────────────────────────────────

function toDateStr(date: DateValue): string {
	return toCalendarDate(date).toString()
}

function hasData(date: DateValue): boolean {
	return !!dailyTotals.value[toDateStr(date)]
}

function dotClassFor(date: DateValue): string {
	const balance = balanceFor(toDateStr(date))
	return balance ? BALANCE_DOT_CLASS[balance.color] : ''
}

function dotTitleFor(date: DateValue): string {
	return balanceFor(toDateStr(date))?.label ?? ''
}

function handleDayClick(date: DateValue): void {
	const dateStr = toDateStr(date)
	if (!props.calendar?.[dateStr]) return
	emit('day-select', dateStr)
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[220px_1fr]">
        <!-- Sidebar -->
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-0.5">
                <h2 class="font-semibold">Market Calendar</h2>
                <p class="hidden text-sm text-muted-foreground sm:inline-block">
                    All scheduled supply and demand.
                </p>
            </div>

            <div class="flex items-center justify-center gap-2">
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Previous month"
                    @click="navigateMonth(-1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <span class="min-w-[5rem] text-center text-sm font-semibold tabular-nums">
                    {{ monthLabel }}
                </span>
                <Button
                    variant="outline"
                    size="icon"
                    aria-label="Next month"
                    @click="navigateMonth(1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>

            <div class="flex justify-center gap-3 text-xs text-muted-foreground sm:flex-col">
                <div
                    v-for="item in legend"
                    :key="item.label"
                    class="flex items-center gap-0.5"
                >
                    <span
                        class="h-2 w-2 rounded-full"
                        :class="BALANCE_DOT_CLASS[item.color]"
                    />
                    {{ item.label }}
                </div>
            </div>
        </div>

        <!-- Calendar grid -->
        <Card class="rounded-none border-0 px-4 shadow-none sm:rounded sm:border sm:shadow">
            <CalendarRoot
                v-slot="{ weekDays, grid }"
                v-model:placeholder="placeholder"
                fixed-weeks
                :week-starts-on="0"
                class="w-full"
            >
                <CalendarGrid
                    v-for="month in grid"
                    :key="month.value.toString()"
                    class="w-full"
                >
                    <CalendarGridHead>
                        <CalendarGridRow>
                            <CalendarHeadCell
                                v-for="day in weekDays"
                                :key="day"
                            >
                                {{ day }}
                            </CalendarHeadCell>
                        </CalendarGridRow>
                    </CalendarGridHead>

                    <CalendarGridBody>
                        <CalendarGridRow
                            v-for="(weekDates, i) in month.rows"
                            :key="`week-${i}`"
                        >
                            <CalendarCell
                                v-for="weekDate in weekDates"
                                :key="weekDate.toString()"
                                :date="weekDate"
                                class="relative h-16 p-0"
                            >
                                <CalendarCellTrigger
                                    :day="weekDate"
                                    :month="month.value"
                                    class="flex h-full w-full flex-col items-center justify-center gap-1 rounded-md border border-border/40 bg-muted/20 text-xs font-semibold transition-colors hover:bg-muted/50 data-[outside-view]:opacity-30"
                                    @click="handleDayClick(weekDate)"
                                />
                                <span
                                    v-if="hasData(weekDate)"
                                    class="pointer-events-none absolute bottom-1.5 left-1/2 size-2.5 -translate-x-1/2 rounded-full"
                                    :class="dotClassFor(weekDate)"
                                    :title="dotTitleFor(weekDate)"
                                />
                            </CalendarCell>
                        </CalendarGridRow>
                    </CalendarGridBody>
                </CalendarGrid>
            </CalendarRoot>
        </Card>
    </div>
</template>