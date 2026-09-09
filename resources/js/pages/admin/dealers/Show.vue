<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import { ChevronDown } from '@lucide/vue'
import {
    Archive,
    Mail,
    Package,
    PackageCheck,
} from '@lucide/vue'
import { computed } from 'vue'
import UserTeaser from '@/components/features/admin/charts/UserTeaser.vue'
import PhoneNumberField from '@/components/features/admin/PhoneNumberField.vue'
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
import admin from '@/routes/admin'
import type {
    BreadcrumbItem,
    DealerResource,
} from '@/types'

const props = defineProps<{
    dealer?: DealerResource
}>()

const ongoingItems = computed<App.Data.PostItem.PostItemData[]>(
    () =>
        props.dealer?.demand_items?.filter((i) => i.status === 'ongoing') ?? [],
)
const archivedItems = computed<App.Data.PostItem.PostItemData[]>(
    () =>
        props.dealer?.demand_items?.filter((i) => i.status === 'expired') ?? [],
)
const fulfilledItems = computed<App.Data.PostItem.PostItemData[]>(
    () =>
        props.dealer?.demand_items?.filter((i) => i.status === 'fulfilled') ??
        [],
)

const totalQuantity = computed(
    () =>
        props.dealer?.demand_items?.reduce(
            (sum, i) => sum + i.quantity_kg,
            0,
        ) ?? 0,
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Dealers', href: admin.dealers.index().url },
    ...(props.dealer
        ? [
              {
                  title: props.dealer.user?.name ?? 'Dealer',
                  href: admin.dealers.show(props.dealer.id).url,
              },
          ]
        : []),
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

                            <!-- Personal Info -->
                            <CardHeader>
                                <CardTitle class="uppercase">{{ dealer.user?.name }}</CardTitle>
                                <CardDescription>
                                    <PhoneNumberField
                                        v-if="dealer.user"
                                        :user-id="dealer.user.id"
                                        :phone-number="dealer.user.phone_number"
                                    />
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
                        <UserTeaser
                            v-if="dealer.insights"
                            :insights="dealer.insights"
                            :locked="dealer.analytics_locked"
                            :total-quantity="totalQuantity"
                            feature-label="Platform Analytics License"
                            waste-title="Most Ordered Varieties"
                            waste-description="By total kilograms demands"
                            waste-unit-label="kg of demands"
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
                                                    <PackageCheck class="size-4"/>Fulfilled
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
