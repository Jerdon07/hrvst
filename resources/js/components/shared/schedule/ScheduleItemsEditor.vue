<script setup lang="ts" generic="TItem extends { _key: number; vegetable_id: string; quantity_kg: number | null }">
import { Check, ChevronDown, ChevronsUpDown, Menu, Plus, Search, Trash2, Users } from '@lucide/vue'
import { computed, ref } from 'vue'
import OverlapPosters from '@/components/shared/vegetables/OverlapPosters.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
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
import { useNetKg } from '@/composables/useNetKg'
import type { ScheduleType } from '@/lib/scheduleRegistry'
import type { PostTimeSlot, VarietyOptionsByVegetable, VegetableOverlapData } from '@/types'

const props = defineProps<{
    type: ScheduleType
    items: TItem[]
    varietyOptions?: VarietyOptionsByVegetable
    errors: Record<string, string>
    scheduledDate: string
    timeSlot: PostTimeSlot | ''
    overlap?: Record<number, VegetableOverlapData>
    overlapLoading: boolean
}>()

const emit = defineEmits<{
    add: []
    remove: [index: number]
}>()

const { netKgClass, formatNetKg } = useNetKg(() => props.type)

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

// ─── Overlap (single source for posters AND net kg) ───────────────────────────

function overlapFor(vegetableId: string): VegetableOverlapData | undefined {
    if (!vegetableId) return undefined
    return props.overlap?.[Number(vegetableId)]
}

function overlapReady(vegetableId: string): boolean {
    return !!vegetableId && !!props.scheduledDate && !!props.timeSlot
}

function othersCount(vegetableId: string): number {
    return overlapFor(vegetableId)?.posters.length ?? 0
}

function netKgFor(vegetableId: string): number | null {
    const data = overlapFor(vegetableId)
    return data ? data.total_supplies_kg - data.total_demands_kg : null
}

// ─── Item dialog ──────────────────────────────────────────────────────────────

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

        <Collapsible
            v-for="(item, i) in items"
            :key="item._key"
            class="space-y-2"
        >
            <Item variant="outline">
                <ItemContent>
                    <ItemTitle class="line-clamp-1">
                        {{ varietyLabelById.get(item.vegetable_id) ?? 'Unselected vegetable' }}
                    </ItemTitle>

                    <ItemDescription v-if="overlapReady(item.vegetable_id)">
                        <Skeleton
                            v-if="overlapLoading"
                            class="h-3.5 w-20 rounded"
                        />
                        <span
                            v-else-if="netKgFor(item.vegetable_id) !== null"
                            :class="netKgClass(netKgFor(item.vegetable_id)!)"
                            class="text-xs font-medium tabular-nums"
                        >
                            {{ formatNetKg(netKgFor(item.vegetable_id)!) }}
                        </span>
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

            <!-- Loading: skeleton only, collapsible is not rendered -->
            <Skeleton
                v-if="overlapReady(item.vegetable_id) && overlapLoading"
                class="h-8 w-full rounded"
            />

            <!-- Loaded with at least one other poster -->
            <template v-else-if="overlapReady(item.vegetable_id) && othersCount(item.vegetable_id) > 0">
                <CollapsibleTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="group/trigger w-full justify-between text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5">
                            <Users class="size-3.5" />
                            Others in this slot
                            <Badge
                                variant="secondary"
                                class="tabular-nums"
                            >
                                {{ othersCount(item.vegetable_id) }}
                            </Badge>
                        </span>
                        <ChevronDown class="size-4 transition-transform duration-200 group-data-[state=open]/trigger:rotate-180" />
                    </Button>
                </CollapsibleTrigger>

                <CollapsibleContent>
                    <div class="rounded-md bg-muted/30 p-3">
                        <OverlapPosters :overlap="overlapFor(item.vegetable_id)" />
                    </div>
                </CollapsibleContent>
            </template>
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