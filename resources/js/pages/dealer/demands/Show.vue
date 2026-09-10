<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3'
import { Calendar1, ChevronsUpDown, SquarePen, Users } from '@lucide/vue'
import { ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import PosterRow from '@/components/shared/PosterRow.vue'
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible'
import { Item, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { edit, index } from '@/routes/dealer/demands'
import type { BreadcrumbItem, DealerDemandDataFixed, VegetableOverlapData } from '@/types'

const props = defineProps<{
    demand: DealerDemandDataFixed
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
</script>

<template>
    <Head :title="`Demand — ${demand.scheduled_date}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl space-y-6 p-4 lg:p-6">
            <div class="flex items-end justify-between gap-4">
                <Heading
                    :title="demand.scheduled_date"
                    :description="`${demand.time_slot} slot`"
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

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <Calendar1 class="size-4" />
                        Items ({{ demand.post_items?.length ?? 0 }})
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <Deferred data="overlap">
                        <template #fallback>
                            <div
                                v-for="item in demand.post_items"
                                :key="item.id"
                                class="space-y-2 rounded-lg border p-3"
                            >
                                <Item class="p-0">
                                    <ItemMedia variant="image">
                                        <Avatar>
                                            <AvatarImage
                                                v-if="item.vegetable_image_url"
                                                :src="item.vegetable_image_url"
                                                :alt="item.display_name!"
                                            />
                                        </Avatar>
                                    </ItemMedia>
                                    <ItemContent class="flex-row items-center justify-between">
                                        <ItemTitle>{{ item.display_name }}</ItemTitle>
                                        <Badge
                                            variant="secondary"
                                            class="capitalize"
                                        >{{ item.status }}</Badge>
                                    </ItemContent>
                                    <span class="font-mono text-sm">{{ item.quantity_kg }} kg</span>
                                </Item>
                                <Skeleton class="h-12 w-full" />
                            </div>
                        </template>

                        <div
                            v-for="item in demand.post_items"
                            :key="item.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <Item class="p-0">
                                    <ItemMedia variant="image">
                                        <Avatar>
                                            <AvatarImage
                                                v-if="item.vegetable_image_url"
                                                :src="item.vegetable_image_url"
                                                :alt="item.display_name!"
                                            />
                                        </Avatar>
                                    </ItemMedia>
                                    <ItemContent class="flex-row items-center justify-between gap-3">
                                        <div>
                                            <ItemTitle>{{ item.display_name }}</ItemTitle>
                                            <p class="text-xs text-muted-foreground">{{ item.quantity_kg }} kg</p>
                                        </div>
                                        <Badge
                                            variant="secondary"
                                            class="capitalize"
                                        >{{ item.status }}</Badge>
                                    </ItemContent>
                                </Item>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-lg"
                                    class="shrink-0"
                                    @click="toggleItemExpanded(item.id)"
                                >
                                    <ChevronsUpDown :class="[expandedItems[item.id] ? 'rotate-180' : '', 'size-4 transition-transform']" />
                                </Button>
                            </div>

                            <Collapsible v-model:open="expandedItems[item.id]">
                                <CollapsibleContent>
                                    <div class="mt-4 rounded-md bg-muted/30 p-3">
                                        <p class="mb-2 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                            <Users class="size-3.5" />
                                            Other activity for {{ item.display_name }} this slot
                                            <span
                                                v-if="overlap?.[item.id]"
                                                class="font-mono"
                                            >({{ overlap[item.id].total_kg }} kg)</span>
                                        </p>

                                        <EmptyState
                                            v-if="!overlap?.[item.id]?.posters.length"
                                            title="No overlap"
                                            description="No other farmers or dealers are active for this vegetable in this slot."
                                        />

                                        <div
                                            v-else
                                            class="space-y-3"
                                        >
                                            <div
                                                v-if="overlap[item.id].supply_posters.length"
                                                class="space-y-1.5"
                                            >
                                                <p class="text-xs font-medium text-muted-foreground">Farmers supplying</p>
                                                <PosterRow
                                                    v-for="(poster, i) in overlap[item.id].supply_posters"
                                                    :key="`supply-${i}`"
                                                    :poster-name="poster.poster_name"
                                                    :poster-phone="poster.poster_phone"
                                                    :total-kg="poster.quantity_kg"
                                                    status="ongoing"
                                                    accent-class="text-primary"
                                                    bg-class="bg-primary/5"
                                                />
                                            </div>
                                            <div
                                                v-if="overlap[item.id].demand_posters.length"
                                                class="space-y-1.5"
                                            >
                                                <p class="text-xs font-medium text-muted-foreground">Dealers requesting</p>
                                                <PosterRow
                                                    v-for="(poster, i) in overlap[item.id].demand_posters"
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
                                    </div>
                                </CollapsibleContent>
                            </Collapsible>
                        </div>
                    </Deferred>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>