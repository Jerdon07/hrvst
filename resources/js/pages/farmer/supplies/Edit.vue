<script setup lang="ts">
import { Deferred, Head, useForm } from '@inertiajs/vue3'
import { CalendarDate, today, getLocalTimeZone, DateFormatter } from '@internationalized/date'
import { CalendarIcon, Check, ChevronsUpDown, Plus, Search, Trash2, Users } from 'lucide-vue-next'
import { computed } from 'vue'
import { update } from '@/actions/App/Http/Controllers/Farmer/Schedule/SupplyController'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import PosterRow from '@/components/shared/PosterRow.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger, ComboboxViewport } from '@/components/ui/combobox'
import { Label } from '@/components/ui/label'
import { NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput } from '@/components/ui/number-field'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { toInputDate } from '@/composables/useDateFormat'
import { useVegetableAvailability, netKgClassFarmer, formatNetKgFarmer } from '@/composables/useVegetableAvailability'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { index, show } from '@/routes/farmer/supplies'
import type { BreadcrumbItem, FarmerSupplyDataFixed, PostTimeSlot, VarietyOptionsByVegetable } from '@/types'

interface OverlapPoster {
    post_id: number
    poster_name: string
    poster_phone: string
    total_kg: number
    items: { id: number; display_name: string | null; quantity_kg: number }[]
}

const props = defineProps<{
    supply: FarmerSupplyDataFixed
    varietyOptions?: VarietyOptionsByVegetable
    overlap?: OverlapPoster[]
}>()

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

const form = useForm({
    scheduled_date: toInputDate(props.supply.scheduled_date),
    time_slot: props.supply.time_slot as PostTimeSlot | '',
    items: (props.supply.post_items ?? []).map((item) => ({
        _key: nextKey(),
        id: item.id,
        vegetable_id: String(item.vegetable_id ?? ''),
        quantity_kg: item.quantity_kg ?? 0,
    })),
})

function blankItem() {
    return { _key: nextKey(), id: null as number | null, vegetable_id: '', quantity_kg: null as number | null }
}

const { getState, getData } = useVegetableAvailability(
    () => form.scheduled_date,
    () => form.time_slot,
    () => form.items.map((i) => i.vegetable_id),
)

const varietyLabelById = computed(() => {
    const map = new Map<string, string>()
    for (const varieties of Object.values(props.varietyOptions ?? {} as VarietyOptionsByVegetable)) {
        for (const variety of varieties) map.set(String(variety.id), variety.name)
    }
    return map
})

function varietyFilterFunction<T extends { value: unknown }>(items: T[], term: string): T[] {
    const needle = term.toLowerCase()
    return items.filter((item) => (varietyLabelById.value.get(String(item.value)) ?? '').toLowerCase().includes(needle))
}

function addItem(): void { form.items.push(blankItem()) }
function removeItem(index: number): void { form.items.splice(index, 1) }
function submit(): void { form.put(update(props.supply.id).url) }

const df = new DateFormatter('en-US', { dateStyle: 'long' })
const minDateValue = computed(() => today(getLocalTimeZone()).add({ days: 1 }))
const maxDateValue = computed(() => today(getLocalTimeZone()).add({ months: 3 }))

const calendarDate = computed({
    get(): CalendarDate | undefined {
        if (!form.scheduled_date) return undefined
        const [y, m, d] = form.scheduled_date.split('-').map(Number)
        return new CalendarDate(y, m, d)
    },
    set(val: CalendarDate | undefined): void { form.scheduled_date = val ? val.toString() : '' },
})

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: index().url },
    { title: `Edit — ${props.supply.scheduled_date}`, href: show(props.supply.id).url },
]
</script>

<template>
    <Head title="Edit Supply Schedule" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-6 p-4 lg:grid-cols-3 lg:p-6">
            <div class="lg:col-span-2 space-y-6">
                <Heading
                    title="Edit Supply Schedule"
                    :description="`Originally posted for ${supply.scheduled_date}`"
                />

                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                    <Button type="button" variant="outline" size="icon-sm" class="h-7 gap-1.5 text-xs" aria-label="Add supply row" @click="addItem">
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
                                                <ComboboxGroup v-for="(varieties, categoryName) in varietyOptions" :key="categoryName" :heading="categoryName">
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
                                    <Button type="button" variant="ghost" size="icon" class="size-9 text-muted-foreground hover:text-destructive" :aria-label="`Remove supply row ${index + 1}`" @click="removeItem(index)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex justify-end gap-3">
                    <Button variant="outline" as-child>
                        <a :href="show(supply.id).url">Cancel</a>
                    </Button>
                    <Button :disabled="form.processing" @click="submit">
                        <Spinner v-if="form.processing" />
                        Save Changes
                    </Button>
                </div>
            </div>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-sm">
                            <Users class="size-4" />
                            Others in this slot
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Deferred data="overlap">
                            <template #fallback>
                                <Skeleton class="h-20 w-full" />
                            </template>

                            <EmptyState
                                v-if="!overlap?.length"
                                title="No other farmers yet"
                                description="You're currently the only one scheduled for this date and slot."
                            />

                            <div v-else class="flex flex-col gap-2">
                                <PosterRow
                                    v-for="poster in overlap"
                                    :key="poster.post_id"
                                    :poster-name="poster.poster_name"
                                    :poster-phone="poster.poster_phone"
                                    :total-kg="poster.total_kg"
                                    status="ongoing"
                                    accent-class="text-primary"
                                    bg-class="bg-primary/5"
                                />
                            </div>
                        </Deferred>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>