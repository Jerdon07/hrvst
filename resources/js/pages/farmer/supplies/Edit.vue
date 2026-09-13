<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { CalendarDate, today, getLocalTimeZone, DateFormatter } from '@internationalized/date'
import { CalendarIcon, Check, ChevronsUpDown, Menu, Plus, Search, Trash2 } from '@lucide/vue'
import { computed, ref } from 'vue'
import { update } from '@/actions/App/Http/Controllers/Farmer/Schedule/SupplyController'
import Heading from '@/components/Heading.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger, ComboboxViewport } from '@/components/ui/combobox'
import { Label } from '@/components/ui/label'
import { NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput } from '@/components/ui/number-field'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { toInputDate } from '@/composables/useDateFormat'
import { useOverlapPreview } from '@/composables/useOverlapPreview'
import { useVegetableAvailability, netKgClassFarmer, formatNetKgFarmer } from '@/composables/useVegetableAvailability'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { edit, index, show } from '@/routes/farmer/supplies'
import type { BreadcrumbItem, PostDataFixed, PostTimeSlot, VarietyOptionsByVegetable, VegetableOverlapData } from '@/types'
import { Item, ItemActions, ItemContent, ItemTitle } from '@/components/ui/item'

const props = defineProps<{
    supply: PostDataFixed
    varietyOptions?: VarietyOptionsByVegetable
}>()

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

type SupplyFormItem = {
    _key: number
    id: number | null
    vegetable_id: string
    quantity_kg: number | null
}

const form = useForm<{
    scheduled_date: string
    time_slot: PostTimeSlot | ''
    items: SupplyFormItem[]
}>({
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

const { overlap, loading: overlapLoading } = useOverlapPreview({
    type: 'supply',
    postId: props.supply.id,
    scheduledDate: () => form.scheduled_date,
    timeSlot: () => form.time_slot,
    vegetableIds: () => form.items.map((i) => i.vegetable_id),
})

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

function overlapFor(vegetableId: string): VegetableOverlapData | undefined {
    if (!vegetableId) return undefined
    return overlap.value[Number(vegetableId)]
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
    { title: `${props.supply.scheduled_date}`, href: show(props.supply.id).url },
    { title: 'Edit', href: edit(props.supply.id).url },
]

const expandedItems = ref<Record<number, boolean>>({})

function toggleItemExpanded(itemKey: number): void {
    expandedItems.value[itemKey] = !expandedItems.value[itemKey]
}
</script>

<template>
    <Head title="Edit Supply Schedule" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Edit Supply Schedule"
                :description="`Originally posted for ${supply.scheduled_date}`"
            />

            <div class="space-y-6">
                <div class="grid grid-cols-1 bg-muted p-4 rounded gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label class="flex items-center gap-1.5">
                                Delivery Day
                                <Badge
                                    variant="destructive"
                                    class="text-xs font-normal"
                                >
                                    Required
                                </Badge>
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
                                >
                                    Required
                                </Badge>
                            </Label>

                            <Select v-model="form.time_slot">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.time_slot }" class="w-full bg-background">
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

                <p class="font-medium text-sm">Vegetables ({{ form.items.length ?? 0 }})</p>

                <Collapsible
                    v-for="(item, i) in form.items"
                    :key="item._key"
                >
                    <Item
                        variant="outline"
                    >
                        <ItemContent>
                            <ItemTitle class="line-clamp-1">
                                {{ varietyLabelById.get(item.vegetable_id) ?? '' }}
                            </ItemTitle>

                            <ItemDescription v-if="item.vegetable_id && form.scheduled_date">
                                <Skeleton
                                    v-if="getState(item.vegetable_id).status === 'loading'"
                                    class="h-3.5 w-20 rounded"
                                />
                                <template v-else-if="getData(item.vegetable_id)">
                                    <span
                                        :class="netKgClassFarmer(getData(item.vegetable_id)!.net_kg)"
                                        class="text-xs font-medium tabular-nums"
                                    >
                                        {{ formatNetKgFarmer(getData(item.vegetable_id)!.net_kg) }}
                                    </span>
                                </template>
                            </ItemDescription>
                        </ItemContent>

                        <ItemActions>
                            <Button 
                                type="button"
                                variant="ghost"
                                size="icon-lg"
                                class="text-destructive"
                                @click="removeItem(i)"
                            >
                                <Trash2 class="size-4" />
                            </Button>

                            <CollapsibleTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="shrink-0"
                                    @click="toggleItemExpanded(item.id!)"
                                >
                                    <ChevronsUpDown class="size-4" />
                                </Button>
                            </CollapsibleTrigger>
                        </ItemActions>
                    </Item>

                    <CollapsibleContent>
                        <div class="mt-4 rounded bg-muted p-3 space-y-2">
                            <div class="space-y-2">
                                <Combobox
                                    :model-value="item.vegetable_id"
                                    :filter-function="varietyFilterFunction"
                                    @update:model-value="(value) => (item.vegetable_id = value == null ? '' : String(value))"
                                >
                                    <ComboboxAnchor
                                        as-child
                                        class="w-full"
                                    >
                                        <ComboboxTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                role="combobox"
                                                :class="[
                                                    'w-full justify-between font-normal',
                                                    !item.vegetable_id && 'text-muted-foreground',
                                                    form.errors[`items.${i}.vegetable_id`] && 'border-destructive text-destructive',
                                                ]"
                                            >
                                                <span class="truncate">{{ varietyLabelById.get(item.vegetable_id) ?? 'Select supply...' }}</span>
                                                <Menu class="ml-2 size-4 shrink-0 text-muted-foreground" />
                                            </Button>
                                        </ComboboxTrigger>
                                    </ComboboxAnchor>

                                    <ComboboxList class="w-(--reka-combobox-anchor-width)">
                                        <div class="relative">
                                            <ComboboxInput
                                                class="pl-9"
                                                placeholder="Search vegetable or variety..."
                                            />
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <Search class="size-4 text-muted-foreground" />
                                            </span>
                                        </div>
                                        <ComboboxEmpty>No vegetable found.</ComboboxEmpty>
                                        <ComboboxViewport>
                                            <ComboboxGroup
                                                v-for="(varieties, categoryName) in varietyOptions"
                                                :key="categoryName"
                                                :heading="categoryName"
                                            >
                                                <ComboboxItem
                                                    v-for="v in varieties"
                                                    :key="v.id"
                                                    :value="String(v.id)"
                                                >
                                                    {{ v.name }}
                                                    <ComboboxItemIndicator><Check class="size-4" /></ComboboxItemIndicator>
                                                </ComboboxItem>
                                            </ComboboxGroup>
                                        </ComboboxViewport>
                                    </ComboboxList>
                                </Combobox>

                                <p
                                    v-if="form.errors[`items.${i}.vegetable_id`]"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors[`items.${i}.vegetable_id`] }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <NumberField
                                    v-model="item.quantity_kg"
                                    :min="0"
                                    :max="99999.99"
                                    :step="0.1"
                                    :format-options="{ style: 'unit', unit: 'kilogram', unitDisplay: 'short', minimumFractionDigits: 0, maximumFractionDigits: 1 }"
                                >
                                    <NumberFieldContent class="w-full">
                                        <NumberFieldDecrement />
                                        <NumberFieldInput
                                            :class="{ 'border-destructive': form.errors[`items.${i}.quantity_kg`] }"
                                            class="bg-card"
                                        />
                                        <NumberFieldIncrement />
                                    </NumberFieldContent>
                                </NumberField>
                                <p
                                    v-if="form.errors[`items.${i}.quantity_kg`]"
                                    class="text-xs text-destructive"
                                >
                                    {{ form.errors[`items.${i}.quantity_kg`] }}
                                </p>
                            </div>
                        </div>
                    </CollapsibleContent>
                </Collapsible>

                <div class="flex items-end justify-end lg:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        class="w-auto"
                        @click="addItem"
                    >
                        <Plus class="mr-2 size-4" />
                        Add Vegetable
                    </Button>
                </div>

                <div class="flex justify-end gap-3">
                    <Button
                        variant="outline"
                        as-child
                    >
                        <a :href="index().url">Cancel</a>
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
