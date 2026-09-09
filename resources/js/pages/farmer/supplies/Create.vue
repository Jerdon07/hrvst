<script setup lang="ts">
import { Deferred, Head, router, useForm } from '@inertiajs/vue3'
import { CalendarDate, today, getLocalTimeZone, DateFormatter } from '@internationalized/date'
import { CalendarIcon, Check, ChevronsUpDown, Plus, Search, Trash2 } from '@lucide/vue'
import { computed, watch } from 'vue'
import { store } from '@/actions/App/Http/Controllers/Farmer/Schedule/SupplyController'
import Heading from '@/components/Heading.vue'
import PosterRow from '@/components/shared/PosterRow.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger, ComboboxViewport } from '@/components/ui/combobox'
import { Label } from '@/components/ui/label'
import { NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput } from '@/components/ui/number-field'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { useVegetableAvailability, netKgClassFarmer, formatNetKgFarmer } from '@/composables/useVegetableAvailability'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { create, index } from '@/routes/farmer/supplies'
import type { BreadcrumbItem, PostTimeSlot, VarietyOptionsByVegetable, VegetableOverlapData } from '@/types'

const props = defineProps<{
    varietyOptions?: VarietyOptionsByVegetable
    overlap?: Record<number, VegetableOverlapData>
}>()

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

const form = useForm({
    scheduled_date: '',
    time_slot: '' as PostTimeSlot | '',
    items: [blankItem()] as Array<{
        _key: number
        vegetable_id: string
        quantity_kg: number | null
    }>,
})

function blankItem() {
    return { _key: nextKey(), vegetable_id: '', quantity_kg: null }
}

const { getState, getData } = useVegetableAvailability(
    () => form.scheduled_date,
    () => form.time_slot,
    () => form.items.map((i) => i.vegetable_id),
)

const varietyLabelById = computed(() => {
    const map = new Map<string, string>()
    const varietyGroups = (props.varietyOptions ?? {}) as Record<string, Array<{ id: number | string; name: string }>>

    for (const varieties of Object.values(varietyGroups)) {
        for (const variety of varieties) {
            map.set(String(variety.id), variety.name)
        }
    }

    return map
})

function varietyFilterFunction<T extends { value: unknown }>(items: T[], term: string): T[] {
    const needle = term.toLowerCase()
    return items.filter((item) => (varietyLabelById.value.get(String(item.value)) ?? '').toLowerCase().includes(needle))
}

function addItem(): void {
    form.items.push(blankItem())
}

function removeItem(index: number): void {
    form.items.splice(index, 1)
}

function submit(): void {
    form.post(store().url)
}

watch(
    () => [form.scheduled_date, form.time_slot, ...form.items.map((item) => item.vegetable_id)],
    ([scheduledDate, timeSlot, ...vegetableIds]) => {
        router.visit(create({
            query: {
                scheduled_date: scheduledDate,
                time_slot: timeSlot,
                vegetable_ids: vegetableIds.filter(Boolean),
            },
        }).url, {
            only: ['overlap'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    },
)

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: index().url },
    { title: 'New Schedule' },
]
</script>

<template>
    <Head title="New Supply Schedule" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="New Supply Schedule"
                description="Post the vegetables you plan to bring, and when."
            />

            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="col-span-1 space-y-2">
                        <Label class="flex items-center gap-1.5">
                            Delivery Day
                            <Badge variant="destructive" class="text-xs font-normal">Required</Badge>
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
                            <PopoverContent class="w-auto p-0" align="start">
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
                        <p v-if="form.errors.scheduled_date" class="text-xs text-destructive">{{ form.errors.scheduled_date }}</p>
                    </div>

                    <div class="col-span-1 space-y-2">
                        <Label class="flex items-center gap-1.5">
                            Time Slot
                            <Badge variant="destructive" class="text-xs font-normal">Required</Badge>
                        </Label>
                        <Select v-model="form.time_slot">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.time_slot }">
                                <SelectValue placeholder="Select time..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="morning">Morning (6 AM – 12 PM)</SelectItem>
                                <SelectItem value="afternoon">Afternoon (12 PM – 6 PM)</SelectItem>
                                <SelectItem value="evening">Evening (6 PM – 10 PM)</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.time_slot" class="text-xs text-destructive">{{ form.errors.time_slot }}</p>
                    </div>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Vegetable Supplies <span class="text-destructive">*</span></TableHead>
                            <TableHead class="text-center">Kilogram <span class="text-destructive">*</span></TableHead>
                            <TableHead class="text-end">
                                <Button type="button" variant="outline" size="icon-sm" class="h-7 gap-1.5 text-xs" @click="addItem">
                                    <Plus class="size-3" />
                                </Button>
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableEmpty v-if="form.items.length === 0" :colspan="3">
                            <span :class="form.errors.items ? 'text-destructive' : ''">No supplies yet. Add at least one supply.</span>
                        </TableEmpty>

                        <TableRow v-for="(item, index) in form.items" :key="item._key">
                            <TableCell class="relative px-0 pb-6 align-top">
                                <Combobox
                                    :model-value="item.vegetable_id"
                                    :filter-function="varietyFilterFunction"
                                    @update:model-value="(value) => (item.vegetable_id = value == null ? '' : String(value))"
                                >
                                    <ComboboxAnchor as-child class="max-w-35 sm:max-w-full">
                                        <ComboboxTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                role="combobox"
                                                :class="[
                                                    'justify-between font-normal',
                                                    !item.vegetable_id && 'text-muted-foreground',
                                                    form.errors[`items.${index}.vegetable_id`] && 'border-destructive text-destructive',
                                                ]"
                                            >
                                                <span class="truncate">{{ varietyLabelById.get(item.vegetable_id) ?? 'Select supply...' }}</span>
                                                <ChevronsUpDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
                                            </Button>
                                        </ComboboxTrigger>
                                    </ComboboxAnchor>

                                    <ComboboxList class="w-(--reka-combobox-anchor-width)">
                                        <div class="relative">
                                            <ComboboxInput class="pl-9" placeholder="Search vegetable or variety..." />
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <Search class="size-4 text-muted-foreground" />
                                            </span>
                                        </div>
                                        <ComboboxEmpty>No vegetable found.</ComboboxEmpty>
                                        <ComboboxViewport>
                                            <ComboboxGroup v-for="(varieties, categoryName) in props.varietyOptions" :key="categoryName" :heading="categoryName">
                                                <ComboboxItem v-for="v in varieties" :key="v.id" :value="String(v.id)">
                                                    {{ v.name }}
                                                    <ComboboxItemIndicator><Check class="size-4" /></ComboboxItemIndicator>
                                                </ComboboxItem>
                                            </ComboboxGroup>
                                        </ComboboxViewport>
                                    </ComboboxList>
                                </Combobox>

                                <div v-if="item.vegetable_id && form.scheduled_date" class="absolute bottom-1 text-xs">
                                    <Skeleton v-if="getState(item.vegetable_id).status === 'loading'" class="h-3.5 w-20 rounded" />
                                    <template v-else-if="getData(item.vegetable_id)">
                                        <span :class="netKgClassFarmer(getData(item.vegetable_id)!.net_kg)" class="text-xs font-medium tabular-nums">
                                            {{ formatNetKgFarmer(getData(item.vegetable_id)!.net_kg) }}
                                        </span>
                                    </template>
                                </div>

                                <p v-if="form.errors[`items.${index}.vegetable_id`]" class="absolute bottom-1 text-xs text-destructive">
                                    {{ form.errors[`items.${index}.vegetable_id`] }}
                                </p>
                            </TableCell>

                            <TableCell class="relative px-0 pb-5 align-top">
                                <NumberField
                                    v-model="item.quantity_kg"
                                    :min="0"
                                    :max="99999.99"
                                    :step="0.1"
                                    :format-options="{ style: 'unit', unit: 'kilogram', unitDisplay: 'short', minimumFractionDigits: 0, maximumFractionDigits: 1 }"
                                >
                                    <NumberFieldContent class="min-w-30 sm:max-w-fit">
                                        <NumberFieldDecrement />
                                        <NumberFieldInput :class="{ 'border-destructive': form.errors[`items.${index}.quantity_kg`] }" />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                                <p v-if="form.errors[`items.${index}.quantity_kg`]" class="absolute bottom-1 text-xs text-destructive">
                                    {{ form.errors[`items.${index}.quantity_kg`] }}
                                </p>
                            </TableCell>

                            <TableCell class="text-end align-top">
                                <Button type="button" variant="ghost" size="icon" class="size-9 text-muted-foreground hover:text-destructive" @click="removeItem(index)">
                                    <Trash2 class="size-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="item in form.items" :key="`overlap-${item._key}`">
                            <TableCell colspan="3" class="border-b-0 pt-0">
                                <Deferred data="overlap">
                                    <template #fallback>
                                        <Skeleton v-if="item.vegetable_id" class="h-7 w-full rounded" />
                                    </template>
                                    <div v-if="item.vegetable_id && overlap?.[Number(item.vegetable_id)]?.posters.length" class="space-y-2 rounded-md bg-muted/30 p-2">
                                        <p class="text-xs font-medium text-muted-foreground">Other activity this slot</p>
                                        <div v-if="overlap[Number(item.vegetable_id)].supply_posters.length" class="space-y-1.5">
                                            <p class="text-xs text-muted-foreground">Farmers supplying</p>
                                            <PosterRow
                                                v-for="(poster, i) in overlap[Number(item.vegetable_id)].supply_posters"
                                                :key="`supply-${i}`"
                                                :poster-name="poster.poster_name"
                                                :poster-phone="poster.poster_phone"
                                                :total-kg="poster.quantity_kg"
                                                status="ongoing"
                                                accent-class="text-primary"
                                                bg-class="bg-primary/5"
                                            />
                                        </div>
                                        <div v-if="overlap[Number(item.vegetable_id)].demand_posters.length" class="space-y-1.5">
                                            <p class="text-xs text-muted-foreground">Dealers requesting</p>
                                            <PosterRow
                                                v-for="(poster, i) in overlap[Number(item.vegetable_id)].demand_posters"
                                                :key="`demand-${i}`"
                                                :poster-name="poster.poster_name"
                                                :poster-phone="poster.poster_phone"
                                                :total-kg="poster.quantity_kg"
                                                status="ongoing"
                                                accent-class="text-orange-600"
                                                bg-class="bg-orange-500/5"
                                            />
                                        </div>
                                    </div>
                                </Deferred>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" as-child>
                        <a :href="index().url">Cancel</a>
                    </Button>
                    <Button :disabled="form.processing" @click="submit">
                        <Spinner v-if="form.processing" />
                        Create Schedule
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>