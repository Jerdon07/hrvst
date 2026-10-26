<script setup lang="ts">
import { Deferred, Head, router, usePage } from '@inertiajs/vue3'
import { DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date'
import type { CalendarDate } from '@internationalized/date'
import { CalendarIcon, Store } from '@lucide/vue'
import { computed, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import MarketCard from '@/components/shared/cards/MarketCard.vue'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { useAuthRoles } from '@/composables/useAuthRoles'
import AppLayout from '@/layouts/AppLayout.vue'
import type { ScheduleType } from '@/lib/scheduleRegistry'
import { TIME_SLOT_CONFIG, TIME_SLOT_ORDER } from '@/lib/timeSlots'
import { useCapitalize } from '@/lib/utils'
import { dashboard } from '@/routes'
import { index } from '@/routes/market'
import type { BreadcrumbItem, SharedMarketProps } from '@/types'

const props = defineProps<SharedMarketProps>()

const { isFarmer, isDealer } = useAuthRoles()
const side = computed<ScheduleType | null>(() => (isFarmer.value ? 'supply' : isDealer.value ? 'demand' : null))

const breadcrumbs: BreadcrumbItem[] = [
    { title: useCapitalize(usePage().props.auth.user.roles[0]), href: dashboard().url },
    { title: 'Daily Market' },
]

const ALL = 'all'
const slot = ref<string>(props.filters.slot ?? ALL)

const df = new DateFormatter('en-US', { dateStyle: 'long' })
const minValue = computed(() => parseDate(props.filters.bounds.min))
const maxValue = computed(() => parseDate(props.filters.bounds.max))
const calendarDate = computed<CalendarDate>({
    get: () => parseDate(props.filters.date),
    set: (value) => visit({ date: value.toString() }),
})

function visit(overrides: { date?: string; slot?: string } = {}): void {
    const nextSlot = overrides.slot ?? slot.value

    router.visit(index().url, {
        data: {
            date: overrides.date ?? props.filters.date,
            slot: nextSlot === ALL ? undefined : nextSlot,
        },
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['rows', 'filters'],
    })
}

function handleSlotChange(value: unknown): void {
    slot.value = value == null ? ALL : String(value)
    visit({ slot: slot.value })
}
</script>

<template>
    <Head title="Daily Market" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Daily Market"
                description="Incoming supply and demand for a single day."
            />

            <div class="flex flex-wrap items-center gap-2">
                <Popover v-slot="{ close }">
                    <PopoverTrigger as-child>
                        <Button
                            variant="outline"
                            class="w-full justify-start font-normal sm:w-64"
                        >
                            <CalendarIcon class="mr-2 size-4" />
                            {{ df.format(calendarDate.toDate(getLocalTimeZone())) }}
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent
                        class="w-auto p-0"
                        align="start"
                    >
                        <Calendar
                            v-model="calendarDate"
                            layout="month-only"
                            :min-value="minValue"
                            :max-value="maxValue"
                            initial-focus
                            @update:model-value="close"
                        />
                    </PopoverContent>
                </Popover>

                <Select
                    :model-value="slot"
                    @update:model-value="handleSlotChange"
                >
                    <SelectTrigger class="w-full sm:w-64">
                        <SelectValue placeholder="All slots" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All slots</SelectItem>
                        <SelectItem
                            v-for="s in TIME_SLOT_ORDER"
                            :key="s"
                            :value="s"
                        >
                            {{ TIME_SLOT_CONFIG[s].label }} ({{ TIME_SLOT_CONFIG[s].hours }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Deferred data="rows">
                <template #fallback>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Skeleton
                            v-for="i in 6"
                            :key="i"
                            class="h-48 rounded-xl"
                        />
                    </div>
                </template>

                <EmptyState
                    v-if="!rows?.length"
                    title="Nothing scheduled"
                    description="No ongoing supply or demand for this day. Try another date or slot."
                    :icon="Store"
                />

                <div
                    v-else
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <MarketCard
                        v-for="row in rows"
                        :key="row.vegetable_id"
                        :row="row"
                        :side="side"
                    />
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>