<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { CalendarDate, today, getLocalTimeZone, DateFormatter } from '@internationalized/date'
import { CalendarIcon, Check, ChevronsUpDown, Plus, Search, Trash2 } from '@lucide/vue'
import { computed, watch } from 'vue'
import { store, update } from '@/actions/App/Http/Controllers/Farmer/Schedule/SupplyController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput, ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger, ComboboxViewport } from '@/components/ui/combobox'
import { Label } from '@/components/ui/label'
import { NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput } from '@/components/ui/number-field'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { toInputDate } from '@/composables/useDateFormat'
import { useVegetableAvailability, netKgClassFarmer, formatNetKgFarmer } from '@/composables/useVegetableAvailability'
import type { FarmerSupplyDataFixed, PostTimeSlot, VarietyOptionsByVegetable, VegetableOptionsByCategory } from '@/types'

interface Props {
    open: boolean
    supply?: FarmerSupplyDataFixed | null
    vegetableOptions?: VegetableOptionsByCategory | null
    varietyOptions?: VarietyOptionsByVegetable | null
}

const props = withDefaults(
    defineProps<Props>(), { 
        supply: null, 
        vegetableOptions: null,
        varietyOptions: null,
    }
)
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const isEditMode = computed(() => !!props.supply)

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

const form = useForm({
    scheduled_date: '',
    time_slot: '',
    items: [] as Array<{
        _key: number
        id: number | null
        vegetable_id: string
        quantity_kg: number | null
        status: string
    }>,
})

const { getState, getData } = useVegetableAvailability(
    () => form.scheduled_date,
    () => form.time_slot,
    () => form.items.map((i) => i.vegetable_id),
)

const varietyLabelById = computed(() => {
    const map = new Map<string, string>()
    for (const varieties of Object.values(props.varietyOptions ?? {})) {
        for (const variety of varieties) {
            map.set(String(variety.id), variety.name)
        }
    }
    return map
})

function varietyFilterFunction<T extends { value: unknown }>(items: T[], term: string): T[] {
    const needle = term.toLowerCase()
    return items.filter((item) => {
        const label = varietyLabelById.value.get(String(item.value)) ?? ''
        return label.toLowerCase().includes(needle)
    })
}

function blankItem() {
    return {
        _key: nextKey(),
        id: null,
        vegetable_id: '',
        quantity_kg: null,
        status: 'ongoing',
    }
}

function addItem(): void {
    form.items.push(blankItem())
}

function removeItem(index: number): void {
    form.items.splice(index, 1)
}

function handleSubmit(): void {
    const options = {
        preserveScroll: true,
        only: ['supplies', 'summary'],
        onSuccess: () => {
            emit('update:open', false)
            form.reset()
            form.items = []
        },
    }

    if (isEditMode.value) {
        form.put(update(props.supply!.id).url, options)
    } else {
        form.post(store().url, options)
    }
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

watch(
    () => [props.open, props.supply] as const,
    ([isOpen, s]) => {
        if (!isOpen) return

        form.scheduled_date = s ? toInputDate(s.scheduled_date) : ''
        form.time_slot = (s?.time_slot ?? '') as PostTimeSlot | ''
        form.items = s
            ? (s.post_items ?? []).map((item) => ({
                  _key: nextKey(),
                  id: item.id,
                  vegetable_id: String(item.vegetable_id ?? ''),
                  quantity_kg: item.quantity_kg ?? 0,
                  status: item.status ?? 'ongoing',
              }))
            : [blankItem()]
        form.clearErrors()
    },
)
</script>

<template>
    <DialogForm
        :open="open"
        :title="isEditMode ? `Edit ${supply?.scheduled_date} supplies` : 'New Supply Schedule'"
        :form="form"
        :submit-label="isEditMode ? 'Save Changes' : 'Create Schedule'"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <!-- Scheduled Date -->
                <div class="col-span-1 space-y-2">
                    <Label
                        for="scheduled_date"
                        class="flex items-center gap-1.5"
                    >
                        Delivery Day
                        <Badge
                            variant="destructive"
                            class="text-xs font-normal"
                        >Required</Badge>
                    </Label>
                    <Popover v-slot="{ close }">
                        <PopoverTrigger as-child>
                            <Button
                                id="scheduled_date"
                                variant="outline"
                                :class="[
                                    'justify-start text-left font-normal',
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
                    >
                        {{ form.errors.scheduled_date }}
                    </p>
                </div>

                <!-- Time Slot -->
                <div class="col-span-1 space-y-2">
                    <Label
                        for="time_slot"
                        class="flex items-center gap-1.5"
                    >
                        Time Slot
                        <Badge
                            variant="destructive"
                            class="text-xs font-normal"
                        >Required</Badge>
                    </Label>
                    <Select v-model="form.time_slot">
                        <SelectTrigger
                            id="time_slot"
                            :class="{ 'border-destructive': form.errors.time_slot }"
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
                    >
                        {{ form.errors.time_slot }}
                    </p>
                </div>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Vegetable Supplies <span class="text-destructive">*</span></TableHead>
                        <TableHead class="text-center">Kilogram <span class="text-destructive">*</span></TableHead>
                        <TableHead class="text-end">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon-sm"
                                class="h-7 gap-1.5 text-xs"
                                aria-label="Add supply row"
                                @click="addItem"
                            >
                                <Plus class="size-3" />
                            </Button>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableEmpty
                        v-if="form.items.length === 0"
                        :colspan="3"
                    >
                        <span :class="form.errors.items ? 'text-destructive' : ''">
                            No supplies yet. Add at least one supply.
                        </span>
                    </TableEmpty>

                    <TableRow
                        v-for="(item, index) in form.items"
                        :key="item._key"
                    >
                        <TableCell class="relative px-0 pb-6 align-top">
                            <Combobox
                                :model-value="item.vegetable_id"
                                :filter-function="varietyFilterFunction"
                                @update:model-value="(value) => (item.vegetable_id = value == null ? '' : String(value))"
                            >
                                <ComboboxAnchor
                                    as-child
                                    class="max-w-35 sm:max-w-full"
                                >
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
                                            <span class="truncate">
                                                {{ varietyLabelById.get(item.vegetable_id) ?? 'Select supply...' }}
                                            </span>
                                            <ChevronsUpDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
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
                                                <ComboboxItemIndicator>
                                                    <Check class="size-4" />
                                                </ComboboxItemIndicator>
                                            </ComboboxItem>
                                        </ComboboxGroup>
                                    </ComboboxViewport>
                                </ComboboxList>
                            </Combobox>

                            <div
                                v-if="item.vegetable_id && form.scheduled_date"
                                class="absolute bottom-1 text-xs"
                            >
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
                            </div>

                            <p
                                v-if="form.errors[`items.${index}.vegetable_id`]"
                                class="absolute bottom-1 text-xs text-destructive"
                            >
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
                            <p
                                v-if="form.errors[`items.${index}.quantity_kg`]"
                                class="absolute bottom-1 text-xs text-destructive"
                            >
                                {{ form.errors[`items.${index}.quantity_kg`] }}
                            </p>
                        </TableCell>

                        <TableCell class="text-end align-top">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-9 text-muted-foreground hover:text-destructive"
                                :aria-label="`Remove supply row ${index + 1}`"
                                @click="removeItem(index)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </DialogForm>
</template>
