<script setup lang="ts">
import { CalendarClock, ChevronDown, Package, ShoppingBag } from '@lucide/vue'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import PosterRow from '@/components/shared/PosterRow.vue'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { groupPostersByType } from '@/lib/scheduleGrouping'
import type { CalendarScheduleItem, CalendarTimeSlot, VegetableDaySchedule } from '@/types'

const props = defineProps<{
    open: boolean
    dateLabel: string
    schedule: VegetableDaySchedule | null
}>()

defineEmits<{ 'update:open': [value: boolean] }>()

const SLOT_LABELS: Record<CalendarTimeSlot, string> = {
    morning: 'Morning',
    afternoon: 'Afternoon',
    evening: 'Evening',
}

const SLOT_ORDER: CalendarTimeSlot[] = ['morning', 'afternoon', 'evening']

function itemsFor(slot: CalendarTimeSlot): CalendarScheduleItem[] {
    return props.schedule?.[slot]?.items ?? []
}

function hasSchedule(slot: CalendarTimeSlot): boolean {
    return itemsFor(slot).length > 0
}

function groupByPoster(slot: CalendarTimeSlot, type: 'supply' | 'demand') {
    return groupPostersByType(itemsFor(slot), type)
}

function netClass(netKg: number): string {
    if (netKg > 0) return 'text-primary'
    if (netKg < 0) return 'text-destructive'
    return 'text-muted-foreground'
}
</script>

<template>
    <DetailSheet
        :open="open"
        :title="dateLabel"
        @update:open="$emit('update:open', $event)"
    >
        <div class="flex flex-col gap-3">
            <Collapsible
                v-for="slot in SLOT_ORDER"
                :key="slot"
                :default-open="hasSchedule(slot)"
                class="rounded border"
            >
                <CollapsibleTrigger class="group/trigger flex w-full items-center justify-between gap-3 bg-muted/30 p-3 text-left transition-colors hover:bg-muted/50">
                    <div class="flex items-center gap-2">
                        <CalendarClock class="size-4 text-muted-foreground" />
                        <span class="text-sm font-semibold">{{ SLOT_LABELS[slot] }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            v-if="hasSchedule(slot)"
                            class="text-xs font-semibold tabular-nums"
                            :class="netClass(schedule?.[slot]?.net_kg ?? 0)"
                        >
                            Net {{ (schedule?.[slot]?.net_kg ?? 0).toLocaleString() }} kg
                        </span>
                        <span
                            v-else
                            class="text-xs text-muted-foreground"
                        >
                            No schedule
                        </span>
                        <ChevronDown class="size-4 text-muted-foreground transition-transform duration-200 group-data-[state=open]/trigger:rotate-180" />
                    </div>
                </CollapsibleTrigger>

                <CollapsibleContent class="border-t p-3">
                    <p
                        v-if="!hasSchedule(slot)"
                        class="py-2 text-center text-xs text-muted-foreground"
                    >
                        No schedule for this time slot
                    </p>

                    <div
                        v-else
                        class="flex flex-col gap-4"
                    >
                        <!-- Supply -->
                        <div class="flex flex-col gap-1.5">
                            <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                <Package class="size-3.5" />
                                Farmers Supplying ({{ groupByPoster(slot, 'supply').length }})
                            </p>

                            <p
                                v-if="!groupByPoster(slot, 'supply').length"
                                class="pl-5 text-xs text-muted-foreground"
                            >
                                No supply scheduled
                            </p>

                            <div
                                v-else
                                class="flex flex-col gap-1"
                            >
                                <PosterRow
                                    v-for="group in groupByPoster(slot, 'supply')"
                                    :key="`supply-${group.post_id}`"
                                    :poster-name="group.poster_name"
                                    :poster-phone="group.poster_phone"
                                    :total-kg="group.total_kg"
                                    :status="group.status"
                                    accent-class="text-primary"
                                    bg-class="bg-primary/5"
                                />
                            </div>
                        </div>

                        <!-- Demand -->
                        <div class="flex flex-col gap-1.5">
                            <p class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                <ShoppingBag class="size-3.5" />
                                Dealers Demand ({{ groupByPoster(slot, 'demand').length }})
                            </p>

                            <p
                                v-if="!groupByPoster(slot, 'demand').length"
                                class="pl-5 text-xs text-muted-foreground"
                            >
                                No demand scheduled
                            </p>

                            <div
                                v-else
                                class="flex flex-col gap-1"
                            >
                                <PosterRow
                                    v-for="group in groupByPoster(slot, 'demand')"
                                    :key="`demand-${group.post_id}`"
                                    :poster-name="group.poster_name"
                                    :poster-phone="group.poster_phone"
                                    :total-kg="group.total_kg"
                                    :status="group.status"
                                    accent-class="text-orange-600"
                                    bg-class="bg-orange-500/5"
                                />
                            </div>
                        </div>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </DetailSheet>
</template>