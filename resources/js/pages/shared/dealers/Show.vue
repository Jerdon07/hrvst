<script setup lang="ts">
import { Deferred, Head, usePage } from '@inertiajs/vue3'
import { Archive, ChevronDown, Mail, Package, PackageCheck, Phone } from '@lucide/vue'
import { computed } from 'vue'
import UserTeaser from '@/components/features/admin/charts/UserTeaser.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { getInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { dashboard } from '@/routes'
import type { BreadcrumbItem, DealerResource } from '@/types'

const props = defineProps<{
    dealer?: DealerResource
}>()

const ongoingItems = computed<App.Data.PostItem.PostItemData[]>(
    () => props.dealer?.demand_items?.filter((i) => i.status === 'ongoing') ?? [],
)
const archivedItems = computed<App.Data.PostItem.PostItemData[]>(
    () => props.dealer?.demand_items?.filter((i) => i.status === 'expired') ?? [],
)
const fulfilledItems = computed<App.Data.PostItem.PostItemData[]>(
    () => props.dealer?.demand_items?.filter((i) => i.status === 'fulfilled') ?? [],
)

const totalQuantity = computed(
    () => props.dealer?.demand_items?.reduce((sum, i) => sum + i.quantity_kg, 0) ?? 0,
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: useCapitalize(usePage().props.auth.user.roles[0]), href: dashboard().url },
    { title: props.dealer?.user?.name ?? 'Dealer' },
])
</script>

<template>
    <Head :title="dealer?.user?.name ?? 'Dealer'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Deferred data="dealer">
                <template #fallback>
                    <div class="grid grid-cols-12 gap-5">
                        <div class="col-span-12 lg:col-span-3">
                            <Card class="space-y-5 p-5">
                                <Skeleton class="size-16 rounded-full" />
                                <Skeleton class="h-4 w-full" />
                                <Skeleton class="h-4 w-3/4" />
                            </Card>
                        </div>
                        <div class="col-span-12 space-y-4 lg:col-span-9">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <Skeleton
                                    v-for="i in 4"
                                    :key="i"
                                    class="h-20 rounded-xl"
                                />
                            </div>
                            <Skeleton class="h-64 w-full rounded-xl" />
                        </div>
                    </div>
                </template>

                <div
                    v-if="dealer"
                    class="grid grid-cols-12 gap-5"
                >
                    <!-- Sidebar -->
                    <div class="md:sticky h-fit top-6 col-span-12 lg:col-span-3">
                        <Card class="h-fit pt-0 overflow-hidden">
                            <!-- Image -->
                            <div class="h-15 w-full bg-primary/10 mb-6" />
                            <Avatar class="absolute top-5 right-5 size-20 border-4 border-background">
                                <AvatarImage
                                    v-if="dealer.user?.avatar_url"
                                    :src="dealer.user?.avatar_url"
                                    :alt="dealer.user.name"
                                />

                                <AvatarFallback class="bg-primary text-base font-semibold text-background">
                                    {{ getInitials(dealer.user?.name) }}
                                </AvatarFallback>
                            </Avatar>

                            <!-- Personal Info — no edit affordances here, unlike admin/dealers/Show -->
                            <CardHeader>
                                <CardTitle class="uppercase">{{ dealer.user?.name }}</CardTitle>
                                <CardDescription class="space-y-1">
                                    <div
                                        v-if="dealer.user"
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <Phone class="size-3.5 shrink-0" />
                                        <span>{{ dealer.user.phone_number }}</span>
                                    </div>
                                    <div
                                        v-if="dealer.user?.email"
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <Mail class="size-4 shrink-0" /><span class="truncate">{{ dealer.user?.email }}</span>
                                    </div>
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    </div>

                    <!-- Main -->
                    <div class="col-span-12 space-y-4 lg:col-span-9">
                        <!--
                            dealer.analytics_locked is driven by the VIEWER's own
                            subscription (see Shared\DealerController::show —
                            SubscriptionFeature::hasAccessFor($request->user())),
                            not the profile owner's.
                        -->
                        <UserTeaser
                            v-if="dealer.insights"
                            :insights="dealer.insights"
                            :locked="dealer.analytics_locked"
                            :total-quantity="totalQuantity"
                            feature-label="Premium Market Intelligence"
                            waste-title="Most Ordered Vegetables"
                            waste-description="By total kilograms demanded"
                            waste-unit-label="kg"
                            waste-guide-question="What does this dealer order most?"
                            volume-title="6-Month Demand Volume"
                        />

                        <Collapsible :default-open="false">
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-lg border bg-muted/20 px-4 py-2.5 text-sm font-medium hover:bg-muted/40">
                                Full Demand History
                                <ChevronDown class="size-4 text-muted-foreground transition-transform duration-200 data-[state=open]:rotate-180" />
                            </CollapsibleTrigger>
                            <CollapsibleContent class="pt-4">
                                <Card>
                                    <CardContent class="pt-4">
                                        <Tabs default-value="ongoing">
                                            <TabsList class="mb-4">
                                                <TabsTrigger
                                                    value="ongoing"
                                                    class="gap-1.5"
                                                >
                                                    <Package class="size-4" />Ongoing
                                                    <Badge
                                                        variant="secondary"
                                                        class="ml-1 px-1.5 py-0 text-xs"
                                                    >{{
                                                        ongoingItems.length
                                                    }}</Badge>
                                                </TabsTrigger>
                                                <TabsTrigger
                                                    value="expired"
                                                    class="gap-1.5"
                                                >
                                                    <Archive class="size-4" />Expired
                                                    <Badge
                                                        variant="secondary"
                                                        class="ml-1 px-1.5 py-0 text-xs"
                                                    >{{
                                                        archivedItems.length
                                                    }}</Badge>
                                                </TabsTrigger>
                                                <TabsTrigger
                                                    value="fulfilled"
                                                    class="gap-1.5"
                                                >
                                                    <PackageCheck class="size-4" />Fulfilled
                                                    <Badge
                                                        variant="secondary"
                                                        class="ml-1 px-1.5 py-0 text-xs"
                                                    >{{
                                                        fulfilledItems.length
                                                    }}</Badge>
                                                </TabsTrigger>
                                            </TabsList>

                                            <template
                                                v-for="(items, tab) in {
                                                    ongoing: ongoingItems,
                                                    expired: archivedItems,
                                                    fulfilled: fulfilledItems,
                                                }"
                                                :key="tab"
                                            >
                                                <TabsContent :value="tab">
                                                    <div
                                                        v-if="items.length === 0"
                                                        class="flex h-24 items-center justify-center text-sm text-muted-foreground"
                                                    >
                                                        No {{ tab }} items
                                                    </div>
                                                    <ItemGroup
                                                        v-else
                                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                                    >
                                                        <Item
                                                            v-for="item in items"
                                                            :key="item.id"
                                                            variant="outline"
                                                        >
                                                            <ItemMedia variant="image">
                                                                <img
                                                                    :src="item.image_url"
                                                                    :alt="item.display_name"
                                                                />
                                                            </ItemMedia>
                                                            <ItemContent class="min-w-0">
                                                                <ItemTitle class="line-clamp-1 text-sm">
                                                                    {{ item.display_name }}
                                                                </ItemTitle>
                                                                <ItemDescription class="mt-0.5 flex items-center gap-1.5">
                                                                    <span class="font-mono text-sm font-medium text-foreground">
                                                                        {{ item.scheduled_date }}
                                                                    </span>
                                                                </ItemDescription>
                                                            </ItemContent>

                                                            <ItemActions>
                                                                <span class="font-mono">{{ item.quantity_kg.toLocaleString() }} </span>kg
                                                            </ItemActions>
                                                        </Item>
                                                    </ItemGroup>
                                                </TabsContent>
                                            </template>
                                        </Tabs>
                                    </CardContent>
                                </Card>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>