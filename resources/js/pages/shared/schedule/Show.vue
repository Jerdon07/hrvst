<script setup lang="ts">
import { Deferred, Head, Link } from '@inertiajs/vue3'
import { Calendar1, ChevronsUpDown, SquarePen } from '@lucide/vue'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import OverlapPosters from '@/components/shared/vegetables/OverlapPosters.vue'
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import { useNetKg } from '@/composables/useNetKg'
import AppLayout from '@/layouts/AppLayout.vue'
import { scheduleRegistry, type ScheduleType } from '@/lib/scheduleRegistry'
import type { BreadcrumbItem, PostDataFixed, VegetableOverlapData } from '@/types'

const props = defineProps<{
    type: ScheduleType
    schedule: PostDataFixed
    overlap?: Record<number, VegetableOverlapData>
}>()

const config = computed(() => scheduleRegistry[props.type])
const { netKgClass, formatNetKg } = useNetKg(() => props.type)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: config.value.roleLabel, href: config.value.dashboard().url },
    { title: config.value.noun.plural, href: config.value.routes.index().url },
    { title: props.schedule.scheduled_date },
])

function netKgFor(itemId: number): number | null {
    const o = props.overlap?.[itemId]
    if (!o) return null
    return o.total_supplies_kg - o.total_demands_kg
}
</script>

<template>
    <Head :title="`${config.entityLabel} — ${schedule.scheduled_date}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between gap-4">
                <Heading
                    :title="schedule.scheduled_date"
                    :description="`${schedule.time_slot} slot for this schedule`"
                />
                <Button
                    v-if="schedule.post_items?.some((item) => item.status === 'ongoing')"
                    as-child
                    variant="outline"
                >
                    <Link :href="config.routes.edit(schedule.id).url">
                        <SquarePen class="size-4" />
                        Edit
                    </Link>
                </Button>
            </div>

            <p class="flex items-center gap-2 font-bold">
                <Calendar1 class="size-4" />
                <span>Items ({{ schedule.post_items?.length ?? 0 }})</span>
            </p>

            <Collapsible
                v-for="item in schedule.post_items"
                :key="item.id"
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
                                :class="netKgClass(netKgFor(item.id)!)"
                                class="text-xs font-medium tabular-nums"
                            >
                                {{ formatNetKg(netKgFor(item.id)!) }}
                            </span>
                        </ItemDescription>
                    </ItemContent>

                    <ItemActions>
                        <CollapsibleTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="shrink-0"
                            >
                                <ChevronsUpDown class="size-4" />
                            </Button>
                        </CollapsibleTrigger>
                    </ItemActions>
                </Item>

                <CollapsibleContent>
                    <div class="mt-4 rounded-md bg-muted/30 p-3">
                        <Deferred data="overlap">
                            <template #fallback>
                                <Skeleton class="h-16 w-full rounded" />
                            </template>

                            <OverlapPosters :overlap="overlap?.[item.id]" />
                        </Deferred>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </AppLayout>
</template>