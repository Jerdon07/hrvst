<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3'
import { Calendar1, SquarePen, Users } from 'lucide-vue-next'
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
import dealer from '@/routes/dealer'
import { edit, index } from '@/routes/dealer/demands'
import type { BreadcrumbItem, DealerDemandDataFixed } from '@/types'

interface OverlapPoster {
    post_id: number
    poster_name: string
    poster_phone: string
    total_kg: number
}

const props = defineProps<{
    demand: DealerDemandDataFixed
    overlap?: OverlapPoster[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dealer', href: dealer.dashboard().url },
    { title: 'Demands', href: index().url },
    { title: props.demand.scheduled_date },
]
</script>

<template>
    <Head :title="`Demand — ${demand.scheduled_date}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 gap-6 p-4 lg:grid-cols-3 lg:p-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-end justify-between">
                    <Heading
                        :title="demand.scheduled_date"
                        :description="`${demand.time_slot} slot`"
                    />
                    <Button v-if="demand.post_items?.some((item) => item.status === 'ongoing')" as-child variant="outline">
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
                    <CardContent class="space-y-2">
                        <Item
                            v-for="item in demand.post_items"
                            :key="item.id"
                            variant="outline"
                        >
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
                    </CardContent>
                </Card>
            </div>

            <Card class="h-fit">
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
                            title="No other dealers yet"
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
                                accent-class="text-orange-600"
                                bg-class="bg-orange-500/5"
                            />
                        </div>
                    </Deferred>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>