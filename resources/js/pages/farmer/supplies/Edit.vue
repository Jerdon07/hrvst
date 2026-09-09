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

                <!-- identical form body to Create.vue: date picker, time slot, items table -->
                <!-- (copy the <div class="grid ..."> through <Table>...</Table> block from Create.vue verbatim) -->

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