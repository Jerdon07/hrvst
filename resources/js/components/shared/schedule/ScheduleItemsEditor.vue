<script setup lang="ts" generic="TItem extends { _key: number; vegetable_id: string; quantity_kg: number | null }">
import { Check, ChevronsUpDown, Menu, Plus, Search, Trash2 } from '@lucide/vue'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Combobox, ComboboxAnchor, ComboboxEmpty, ComboboxGroup, ComboboxInput,
    ComboboxItem, ComboboxItemIndicator, ComboboxList, ComboboxTrigger, ComboboxViewport,
} from '@/components/ui/combobox'
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'
import { Item, ItemActions, ItemContent, ItemDescription, ItemTitle } from '@/components/ui/item'
import { Label } from '@/components/ui/label'
import {
    NumberField, NumberFieldContent, NumberFieldDecrement, NumberFieldIncrement, NumberFieldInput,
} from '@/components/ui/number-field'
import { Skeleton } from '@/components/ui/skeleton'
import { useVegetableAvailability } from '@/composables/useVegetableAvailability'
import type { PostTimeSlot, VarietyOptionsByVegetable, VegetableOverlapData } from '@/types'

const props = defineProps<{
    items: TItem[]
    varietyOptions?: VarietyOptionsByVegetable
    errors: Record<string, string>
    scheduledDate: string
    timeSlot: PostTimeSlot | ''
    overlap?: Record<number, VegetableOverlapData>
    overlapLoading: boolean
    netKgClass: (kg: number) => string
    formatNetKg: (kg: number) => string
}>()

const emit = defineEmits<{
    add: []
    remove: [index: number]
}>()

const varietyLabelById = computed(() => {
    const map = new Map<string, string>()
    for (const varieties of Object.values(props.varietyOptions ?? {})) {
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
    return props.overlap?.[Number(vegetableId)]
}

const { getState, getData } = useVegetableAvailability(
    () => props.scheduledDate,
    () => props.timeSlot,
    () => props.items.map((i) => i.vegetable_id),
)

const editingIndex = ref<number | null>(null)
const editingItem = computed(() => (editingIndex.value !== null ? props.items[editingIndex.value] : null))

function openItemDialog(index: number): void {
    editingIndex.value = index
}
function closeItemDialog(): void {
    editingIndex.value = null
}
function editingItemError(field: 'vegetable_id' | 'quantity_kg'): string | undefined {
    const index = editingIndex.value
    return index === null ? undefined : props.errors[`items.${index}.${field}`]
}

function addItem(): void {
    emit('add')
    editingIndex.value = props.items.length
}
function removeItem(index: number): void {
    emit('remove', index)
    editingIndex.value = null
}
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm font-medium">Vegetables ({{ items.length ?? 0 }})</p>

        <Item
            v-for="(item, i) in items"
            :key="item._key"
            variant="outline"
        >
            <ItemContent>
                <ItemTitle class="line-clamp-1">
                    {{ varietyLabelById.get(item.vegetable_id) ?? 'Unselected vegetable' }}
                </ItemTitle>

                <ItemDescription v-if="item.vegetable_id && scheduledDate">
                    <Skeleton
                        v-if="getState(item.vegetable_id).status === 'loading'"
                        class="h-3.5 w-20 rounded"
                    />
                    <template v-else-if="getData(item.vegetable_id)">
                        <span
                            :class="netKgClass(getData(item.vegetable_id)!.net_kg)"
                            class="text-xs font-medium tabular-nums"
                        >
                            {{ formatNetKg(getData(item.vegetable_id)!.net_kg) }}
                        </span>
                    </template>
                </ItemDescription>

                <ItemDescription v-if="item.quantity_kg">
                    {{ item.quantity_kg }} kg
                </ItemDescription>

                <p
                    v-if="errors[`items.${i}.vegetable_id`] || errors[`items.${i}.quantity_kg`]"
                    class="text-xs text-destructive"
                >
                    {{ errors[`items.${i}.vegetable_id`] || errors[`items.${i}.quantity_kg`] }}
                </p>
            </ItemContent>

            <ItemActions>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="shrink-0"
                    @click="openItemDialog(i)"
                >
                    <ChevronsUpDown class="size-4" />
                </Button>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon-lg"
                    class="text-destructive"
                    @click="removeItem(i)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </ItemActions>
        </Item>

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

        <Dialog
            :open="editingIndex !== null"
            @update:open="(open) => !open && closeItemDialog()"
        >
            <DialogContent
                v-if="editingItem"
                class="sm:max-w-md"
            >
                <DialogHeader>
                    <DialogTitle>Edit Vegetable</DialogTitle>
                    <DialogDescription>Update the vegetable and quantity for this line item.</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label>Vegetable</Label>
                        <Combobox
                            :model-value="editingItem.vegetable_id"
                            :filter-function="varietyFilterFunction"
                            @update:model-value="(value) => (editingItem!.vegetable_id = value == null ? '' : String(value))"
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
                                            !editingItem.vegetable_id && 'text-muted-foreground',
                                            editingItemError('vegetable_id') && 'border-destructive text-destructive',
                                        ]"
                                    >
                                        <span class="truncate">{{ varietyLabelById.get(editingItem.vegetable_id) ?? 'Select vegetable...' }}</span>
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
                            v-if="editingItemError('vegetable_id')"
                            class="text-xs text-destructive"
                        >
                            {{ editingItemError('vegetable_id') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label>Quantity</Label>
                        <NumberField
                            v-model="editingItem.quantity_kg"
                            :min="0"
                            :max="99999.99"
                            :step="0.1"
                            :format-options="{ style: 'unit', unit: 'kilogram', unitDisplay: 'short', minimumFractionDigits: 0, maximumFractionDigits: 1 }"
                        >
                            <NumberFieldContent class="w-full">
                                <NumberFieldDecrement />
                                <NumberFieldInput
                                    :class="{ 'border-destructive': editingItemError('quantity_kg') }"
                                    class="bg-card"
                                />
                                <NumberFieldIncrement />
                            </NumberFieldContent>
                        </NumberField>
                        <p
                            v-if="editingItemError('quantity_kg')"
                            class="text-xs text-destructive"
                        >
                            {{ editingItemError('quantity_kg') }}
                        </p>
                    </div>

                    <Skeleton
                        v-if="overlapLoading"
                        class="h-16 w-full rounded"
                    />
                    <div
                        v-else-if="editingItem.vegetable_id && overlapFor(editingItem.vegetable_id)?.posters.length"
                        class="rounded bg-muted/30 p-3 text-xs text-muted-foreground"
                    >
                        Other activity this slot: {{ overlapFor(editingItem.vegetable_id)!.posters.length }} posts
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeItemDialog"
                    >
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>