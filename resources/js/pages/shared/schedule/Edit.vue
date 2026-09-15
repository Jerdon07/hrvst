<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { CalendarDate, DateFormatter, getLocalTimeZone, today } from '@internationalized/date'
import { CalendarIcon } from '@lucide/vue'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import ScheduleItemsEditor from '@/components/shared/schedule/ScheduleItemsEditor.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Label } from '@/components/ui/label'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Spinner } from '@/components/ui/spinner'
import { toInputDate } from '@/composables/useDateFormat'
import { useOverlapPreview } from '@/composables/useOverlapPreview'
import { formatNetKgDealer, formatNetKgFarmer, netKgClassDealer, netKgClassFarmer } from '@/composables/useVegetableAvailability'
import AppLayout from '@/layouts/AppLayout.vue'
import { nextItemKey, type ScheduleFormItem } from '@/lib/scheduleForm'
import { scheduleRegistry, type ScheduleType } from '@/lib/scheduleRegistry'
import type { BreadcrumbItem, PostDataFixed, PostTimeSlot, VarietyOptionsByVegetable } from '@/types'

const props = defineProps<{
    type: ScheduleType
    schedule: PostDataFixed
    varietyOptions?: VarietyOptionsByVegetable
}>()

const config = computed(() => scheduleRegistry[props.type])
const netKgClass = computed(() => (props.type === 'supply' ? netKgClassFarmer : netKgClassDealer))
const formatNetKg = computed(() => (props.type === 'supply' ? formatNetKgFarmer : formatNetKgDealer))

const form = useForm({
    scheduled_date: toInputDate(props.schedule.scheduled_date),
    time_slot: props.schedule.time_slot as PostTimeSlot | '',
    items: (props.schedule.post_items ?? []).map((item): ScheduleFormItem => ({
        _key: nextItemKey(),
        id: item.id,
        vegetable_id: String(item.vegetable_id ?? ''),
        quantity_kg: item.quantity_kg ?? 0,
    })),
})

const { overlap, loading: overlapLoading } = useOverlapPreview({
    type: props.type,
    postId: props.schedule.id,
    scheduledDate: () => form.scheduled_date,
    timeSlot: () => form.time_slot,
    vegetableIds: () => form.items.map((i) => i.vegetable_id),
})

function addItem(): void {
    form.items.push({ _key: nextItemKey(), id: null, vegetable_id: '', quantity_kg: null })
}
function removeItem(index: number): void {
    form.items.splice(index, 1)
}
function submit(): void {
    form.put(config.value.routes.update(props.schedule.id).url)
}

const df = new DateFormatter('en-US', { dateStyle: 'long' })
const minDateValue = computed(() => today(getLocalTimeZone()).add({ days: 1 }))
const maxDateValue = computed(() => today(getLocalTimeZone()).add({ months: 3 }))

const calendarDate = computed({
    get(): CalendarDate | undefined {
        if (!form.scheduled_date) return undefined
        const [y, m, d] = form.scheduled_date.split('-').map(Number)
        return new CalendarDate(y, m, d)
    },
    set(val: CalendarDate | undefined): void {
        form.scheduled_date = val ? val.toString() : ''
    },
})

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: config.value.roleLabel, href: config.value.dashboard().url },
    { title: config.value.noun.plural, href: config.value.routes.index().url },
    { title: props.schedule.scheduled_date, href: config.value.routes.show(props.schedule.id).url },
    { title: 'Edit' },
])
</script>

<template>
    <Head :title="config.copy.editTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                :title="config.copy.editTitle"
                :description="config.copy.editDescription(schedule.scheduled_date)"
            />

            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-4 rounded bg-muted p-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label class="flex items-center gap-1.5">
                                Delivery Day
                                <Badge
                                    variant="destructive"
                                    class="text-xs font-normal"
                                >Required</Badge>
                            </Label>

                            <Popover v-slot="{ close }">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="[
                                            'w-full justify-start text-left font-normal',
                                            !form.scheduled_date && 'text-muted-foreground',
                                            form.errors.scheduled_date && 'border-destructive text-destructive',
                                        ]"
                                    >
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{ form.scheduled_date ? df.format(calendarDate!.toDate(getLocalTimeZone())) : 'Pick a date' }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent
                                    class="w-auto p-0"
                                    align="start"
                                >
                                    <Calendar
                                        v-model="calendarDate"
                                        layout="month-only"
                                        :min-value="minDateValue"
                                        :max-value="maxDateValue"
                                        initial-focus
                                        @update:model-value="close"
                                    />
                                </PopoverContent>
                            </Popover>
                            <p
                                v-if="form.errors.scheduled_date"
                                class="text-xs text-destructive"
                            >{{ form.errors.scheduled_date }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label class="flex items-center gap-1.5">
                                Time Slot
                                <Badge
                                    variant="destructive"
                                    class="text-xs font-normal"
                                >Required</Badge>
                            </Label>

                            <Select v-model="form.time_slot">
                                <SelectTrigger
                                    :class="{ 'border-destructive': form.errors.time_slot }"
                                    class="w-full bg-background"
                                >
                                    <SelectValue placeholder="Select time..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="morning">Morning (6 AM – 12 PM)</SelectItem>
                                    <SelectItem value="afternoon">Afternoon (12 PM – 6 PM)</SelectItem>
                                    <SelectItem value="evening">Evening (6 PM – 10 PM)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.time_slot"
                                class="text-xs text-destructive"
                            >{{ form.errors.time_slot }}</p>
                        </div>
                    </div>
                </div>

                <ScheduleItemsEditor
                    :items="form.items"
                    :variety-options="varietyOptions"
                    :errors="form.errors"
                    :scheduled-date="form.scheduled_date"
                    :time-slot="form.time_slot"
                    :overlap="overlap"
                    :overlap-loading="overlapLoading"
                    :net-kg-class="netKgClass"
                    :format-net-kg="formatNetKg"
                    @add="addItem"
                    @remove="removeItem"
                />

                <div class="flex justify-end gap-3">
                    <Button
                        variant="outline"
                        as-child
                    >
                        <a :href="config.routes.index().url">Cancel</a>
                    </Button>
                    <Button
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <Spinner v-if="form.processing" />
                        Update Schedule
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>