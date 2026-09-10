<script setup lang="ts">
import { Deferred, router } from '@inertiajs/vue3'
import { CalendarClock, CheckCircle2, ChevronDown, ChevronRight } from '@lucide/vue';
import { expire, fulfill } from '@/actions/App/Http/Controllers/Farmer/Schedule/PostItemController';
import PostActionButtons from '@/components/shared/PostActionButtons.vue';
import { Avatar, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Marker, MarkerContent, MarkerIcon } from '@/components/ui/marker';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { daysOverdue, isDueToday, urgencyClass, urgencyLabel } from '@/composables/usePostItemUrgency';
import farmer from '@/routes/farmer';
import type { ExpiringPostDataFixed } from '@/types';

defineProps<{
    expiringSupplies: ExpiringPostDataFixed[]
}>()

</script>

<template>
    <Card class="py-2 gap-0">
        <CardHeader>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CalendarClock class="size-4" />
                    <CardTitle class="text-sm fonte-semibold">Action Needed</CardTitle>
                </div>

                <Button 
                    variant="ghost"
                    class="text-xs text-muted-foreground transition-colors"
                    @click="router.visit(farmer.supplies.index().url)"
                >
                    View All
                    <ChevronRight />
                </Button>
            </div>
        </CardHeader>

        <Separator />

        <Deferred data="expiringSupplies">
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
                    v-if="!expiringSupplies.length"
                    class="flex-col"
                >
                    <MarkerIcon>
                        <CheckCircle2/>
                    </MarkerIcon>
                    <MarkerContent>
                        All ongoing supplies are still within schedule.
                    </MarkerContent>
                </Marker>

                <div
                    v-else
                    class="spave-y-3"
                >
                    <Collapsible
                        v-for="supply in expiringSupplies"
                        :key="supply.id"
                        :default-open="isDueToday(supply.scheduled_date)"
                        class="rounded border overflow-hidden"
                    >
                        <CollapsibleTrigger class="group/trigger flex w-full bg-primary/10 items-cente justify-between gap-3 p-3 text-left transition-colors hover:bg-primary/5">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <CalendarClock class="size-3.5 shrink-0"/>
                                {{ supply.scheduled_date }}
                                <Badge variant="outline">
                                    {{ supply.time_slot_label }}
                                </Badge>
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="shrink-0 text-xs font-semibold tabular-nums"
                                    :class="urgencyClass(daysOverdue(supply.scheduled_date))"
                                >
                                    {{ urgencyLabel(daysOverdue(supply.scheduled_date)) }}
                                </span>
                                <ChevronDown class="size-4 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[state=open]/trigger:rotate-180"/>
                            </div>
                        </CollapsibleTrigger>

                        <CollapsibleContent class="bg-primary/5">
                            <Separator />
                            <div>
                                <Item
                                    v-for="item in supply.items"
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
                                            :fulfill-url="fulfill(item.id).url"
                                            :expire-url="expire(item.id).url"
                                            :label="item.display_name!"
                                            :only="['expiringSupplies']"
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