<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3'
import { Calendar1, SquarePen, Users } from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import PosterRow from '@/components/shared/PosterRow.vue'
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Item, ItemContent, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { edit, index } from '@/routes/farmer/supplies'
import type { BreadcrumbItem, FarmerSupplyDataFixed, VegetableOverlapData } from '@/types'

const props = defineProps<{
    supply: FarmerSupplyDataFixed
    overlap?: Record<number, VegetableOverlapData>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: index().url },
    { title: props.supply.scheduled_date },
]
</script>

<template>
    <Head :title="`Supply — ${supply.scheduled_date}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    :title="supply.scheduled_date"
                    :description="`${supply.time_slot} slot`"
                />
                <Button v-if="supply.post_items?.some((item) => item.status === 'ongoing')" as-child variant="outline">
                    <Link :href="edit(supply.id).url">
                        <SquarePen class="size-4" />
                        Edit
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <Calendar1 class="size-4" />
                        Items ({{ supply.post_items?.length ?? 0 }})
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <Deferred data="overlap">
                        <template #fallback>
                            <div
                                v-for="item in supply.post_items"
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
                                        <Badge variant="secondary" class="capitalize">{{ item.status }}</Badge>
                                    </ItemContent>
                                    <span class="font-mono text-sm">{{ item.quantity_kg }} kg</span>
                                </Item>
                                <Skeleton class="h-12 w-full" />
                            </div>
                        </template>

                        <div
                            v-for="item in supply.post_items"
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
                                    <Badge variant="secondary" class="capitalize">{{ item.status }}</Badge>
                                </ItemContent>
                                <span class="font-mono text-sm">{{ item.quantity_kg }} kg</span>
                            </Item>

                            <div class="pl-1">
                                <p class="mb-1.5 flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                    <Users class="size-3.5" />
                                    Others bringing {{ item.display_name }} this slot
                                    <span v-if="overlap?.[item.id]" class="font-mono">
                                        ({{ overlap[item.id].total_kg }} kg)
                                    </span>
                                </p>

                                <EmptyState
                                    v-if="!overlap?.[item.id]?.posters.length"
                                    title="No overlap"
                                    description="You're the only one bringing this vegetable in this slot."
                                />

                                <div v-else class="flex flex-col gap-1.5">
                                    <PosterRow
                                        v-for="(poster, i) in overlap[item.id].posters"
                                        :key="i"
                                        :poster-name="poster.poster_name"
                                        :poster-phone="poster.poster_phone"
                                        :total-kg="poster.quantity_kg"
                                        status="ongoing"
                                        accent-class="text-primary"
                                        bg-class="bg-primary/5"
                                    />
                                </div>
                            </div>
                        </div>
                    </Deferred>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>