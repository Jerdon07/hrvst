<script setup lang="ts">
import { Deferred, router } from '@inertiajs/vue3'
import { CalendarClock, CheckCircle2, ChevronDown, ChevronRight } from '@lucide/vue'
import PostActionButtons from '@/components/shared/PostActionButtons.vue'
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Marker, MarkerContent, MarkerIcon } from '@/components/ui/marker'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { daysOverdue, isDueToday, urgencyClass, urgencyLabel } from '@/composables/usePostItemUrgency'
import type { ExpiringPostDataFixed } from '@/types'

const props = defineProps<{
    items: ExpiringPostDataFixed[]
    deferredKey: string
    viewAllUrl: string
    emptyMessage: string
    fulfillUrl: (itemId: number) => string
    expireUrl: (itemId: number) => string
}>()
</script>

<template>
    <Card class="py-2 gap-0">
        <CardHeader>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CalendarClock class="size-4" />
                    <CardTitle class="text-sm font-semibold">Action Needed</CardTitle>
                </div>

                <Button
                    variant="ghost"
                    class="text-xs text-muted-foreground transition-colors"
                    @click="router.visit(viewAllUrl)"
                >
                    View All
                    <ChevronRight />
                </Button>
            </div>
        </CardHeader>

        <Separator />

        <Deferred :data="deferredKey">
            <template #fallback>
                <CardContent class="space-y-3 pt-4">
                    <Skeleton
                        v-for="i in 3"
                        :key="i"
                        class="h-20 w-full"
                    />
                </CardContent>
            </template>

            <CardContent class="pt-2 px-2">
                <Marker
                    v-if="!items.length"
                    class="flex-col"
                >
                    <MarkerIcon>
                        <CheckCircle2 />
                    </MarkerIcon>
                    <MarkerContent>
                        {{ emptyMessage }}
                    </MarkerContent>
                </Marker>

                <div
                    v-else
                    class="space-y-3"
                >
                    <Collapsible
                        v-for="entry in items"
                        :key="entry.id"
                        :default-open="isDueToday(entry.scheduled_date)"
                        class="rounded border overflow-hidden"
                    >
                        <CollapsibleTrigger class="group/trigger flex w-full bg-primary/10 items-center justify-between gap-3 p-3 text-left transition-colors hover:bg-primary/5">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <CalendarClock class="size-3.5 shrink-0" />
                                {{ entry.scheduled_date }}
                                <Badge variant="outline">
                                    {{ entry.time_slot_label }}
                                </Badge>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="shrink-0 text-xs font-semibold tabular-nums"
                                    :class="urgencyClass(daysOverdue(entry.scheduled_date))"
                                >
                                    {{ urgencyLabel(daysOverdue(entry.scheduled_date)) }}
                                </span>
                                <ChevronDown class="size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]/trigger:rotate-180" />
                            </div>
                        </CollapsibleTrigger>

                        <CollapsibleContent class="bg-primary/5">
                            <Separator />
                            <div>
                                <Item
                                    v-for="item in entry.items"
                                    :key="item.id"
                                    size="sm"
                                    class="px-2 py-1"
                                >
                                    <ItemMedia variant="image">
                                        <Avatar class="size-8 shrink-0 rounded-md">
                                            <AvatarImage
                                                :src="item.vegetable_image_url!"
                                                :alt="item.display_name"
                                            />
                                        </Avatar>
                                    </ItemMedia>

                                    <ItemContent class="gap-0">
                                        <ItemTitle>{{ item.display_name }}</ItemTitle>
                                        <ItemDescription>{{ item.quantity_kg.toLocaleString() }} kg</ItemDescription>
                                    </ItemContent>

                                    <ItemActions>
                                        <PostActionButtons
                                            :fulfill-url="fulfillUrl(item.id)"
                                            :expire-url="expireUrl(item.id)"
                                            :label="item.display_name!"
                                            :only="[deferredKey]"
                                        />
                                    </ItemActions>
                                </Item>
                            </div>
                        </CollapsibleContent>
                    </Collapsible>
                </div>
            </CardContent>
        </Deferred>
    </Card>
</template>