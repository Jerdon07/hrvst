<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3'
import { Calendar1, ChevronsUpDown, SquarePen } from '@lucide/vue'
import { ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import SchedulePosters from '@/components/shared/vegetables/SchedulePosters.vue'
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible'
import { Item, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item'
import { netKgClassDealer, formatNetKgDealer } from '@/composables/useVegetableAvailability'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { edit, index } from '@/routes/dealer/demands'
import type { BreadcrumbItem, PostDataFixed, VegetableOverlapData } from '@/types'

const props = defineProps<{
    demand: PostDataFixed
    overlap?: Record<number, VegetableOverlapData>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dealer', href: dealer.dashboard().url },
    { title: 'Demands', href: index().url },
    { title: props.demand.scheduled_date },
]

const expandedItems = ref<Record<number, boolean>>({})

function toggleItemExpanded(itemId: number): void {
    expandedItems.value[itemId] = !expandedItems.value[itemId]
}

function netKgFor(itemId: number): number | null {
    const o = props.overlap?.[itemId]
    if (!o) return null
    return o.total_supplies_kg - o.total_demands_kg
}
</script>

<template>
    <Head :title="`Demand — ${demand.scheduled_date}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between gap-4">
                <Heading
                    :title="demand.scheduled_date"
                    :description="`${demand.time_slot} slot for this schedule`"
                />
                <Button
                    v-if="demand.post_items?.some((item) => item.status === 'ongoing')"
                    as-child
                    variant="outline"
                >
                    <Link :href="edit(demand.id).url">
                        <SquarePen class="size-4" />
                        Edit
                    </Link>
                </Button>
            </div>

            <p class="flex gap-2 items-center font-bold">
                <Calendar1 class="size-4" />
                <span>Items ({{ demand.post_items?.length ?? 0 }})</span>
            </p>

            <Collapsible
                v-for="item in demand.post_items"
                :key="item.id"
                v-model:open="expandedItems[item.id]"
            >
                <Item
                    variant="outline"
                    size="sm"
                >
                    <ItemMedia variant="image">
                        <Avatar>
                            <AvatarImage
                                v-if="item.vegetable_image_url"
                                :src="item.vegetable_image_url"
                                :alt="item.display_name"
                            />
                        </Avatar>
                    </ItemMedia>

                    <ItemContent>
                        <ItemTitle>{{ item.display_name }}</ItemTitle>
                        <ItemDescription v-if="netKgFor(item.id) !== null">
                            <span
                                :class="netKgClassDealer(netKgFor(item.id)!)"
                                class="text-xs font-medium tabular-nums"
                            >
                                {{ formatNetKgDealer(netKgFor(item.id)!) }}
                            </span>
                        </ItemDescription>
                    </ItemContent>

                    <ItemActions>
                        <CollapsibleTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="shrink-0"
                                @click="toggleItemExpanded(item.id)"
                            >
                                <ChevronsUpDown class="size-4"/>
                            </Button>
                        </CollapsibleTrigger>
                    </ItemActions>
                </Item>

                <CollapsibleContent>
                    <div class="mt-4 rounded-md bg-muted/30 p-3">
                        <Deferred data="overlap">
                            <template #fallback>
                                <div>Defering..</div>
                            </template>

                            <EmptyState
                                v-if="!overlap?.[item.id]?.posters.length"
                                title="Schedule is Empty"
                                description="No other farmers or dealers are active for this vegetable in this slot."
                            />

                            <div 
                                v-else
                                class="space-y-2"
                            >
                                <div 
                                    v-if="overlap[item.id].supply_posters.length"
                                    class="space-y-2"
                                >
                                    <div class="flex justify-between items-center">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            Farmers supplying
                                        </p>

                                        <Badge class="tabular-nums">{{ overlap[item.id].total_supplies_kg.toLocaleString() }} kg total supplies</Badge>
                                    </div>

                                    <SchedulePosters
                                        v-for="(poster, i) in overlap[item.id].supply_posters"
                                        :key="`supply-${i}`"
                                        :poster-name="poster.poster_name"
                                        :quantity-kg="poster.quantity_kg"
                                        bg-class="bg-green-500/5"
                                    />
                                </div>

                                <div
                                    v-if="overlap[item.id].demand_posters.length"
                                    class="space-y-2"
                                >
                                    <div class="flex justify-between items-center">
                                        <p class="text-xs font-medium text-muted-foreground">
                                            Dealers demanding
                                        </p>

                                        <Badge class="tabular-nums">
                                            {{ overlap[item.id].total_demands_kg.toLocaleString() }} kg total demands
                                        </Badge>
                                    </div>

                                    <SchedulePosters
                                        v-for="(poster, i) in overlap[item.id].demand_posters"
                                        :key="`demand-${i}`"
                                        :poster-name="poster.poster_name"
                                        :quantity-kg="poster.quantity_kg"
                                        bg-class="bg-orange-500/5"
                                    />
                                </div>
                            </div>
                        </Deferred>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </AppLayout>
</template>