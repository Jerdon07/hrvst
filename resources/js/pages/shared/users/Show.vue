<script setup lang="ts">
import { Deferred, Head, usePage } from '@inertiajs/vue3'
import { Archive, ChevronDown, Mail, Package, PackageCheck, Phone } from '@lucide/vue'
import { computed } from 'vue'
import UserTeaser from '@/components/features/admin/charts/UserTeaser.vue'
import LeafletMap from '@/components/LeafletMap.vue'
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
import type { BreadcrumbItem, DealerResource, FarmerResource } from '@/types'
import type { UserResource } from '@/types/resources/user'

type ProfileType = 'farmer' | 'dealer' | 'basic'

const props = defineProps<{
    profileType: ProfileType
    meta: {
        userId: number
        name: string
    }
    profile?: FarmerResource | DealerResource | UserResource
}>()

// Narrowing helpers — profileType (not deferred) is always present on first
// load, so these are safe to use even before the deferred `profile` prop
// resolves.
const farmer = computed<FarmerResource | null>(() =>
    props.profileType === 'farmer' ? (props.profile as FarmerResource) : null,
)
const dealer = computed<DealerResource | null>(() =>
    props.profileType === 'dealer' ? (props.profile as DealerResource) : null,
)
const basicUser = computed<UserResource | null>(() =>
    props.profileType === 'basic' ? (props.profile as UserResource) : null,
)

// Shared header fields, regardless of which of the three shapes resolved.
const displayName = computed(
    () => farmer.value?.user?.name ?? dealer.value?.user?.name ?? basicUser.value?.name ?? props.meta.name,
)
const avatarUrl = computed(
    () => farmer.value?.user?.avatar_url ?? dealer.value?.user?.avatar_url ?? basicUser.value?.avatar_url ?? null,
)
const phoneNumber = computed(
    () => farmer.value?.user?.phone_number ?? dealer.value?.user?.phone_number ?? basicUser.value?.phone_number ?? null,
)
const email = computed(
    () => farmer.value?.user?.email ?? dealer.value?.user?.email ?? basicUser.value?.email ?? null,
)

const ongoingItems = computed<App.Data.PostItem.PostItemData[]>(() => {
    const items = farmer.value?.supply_items ?? dealer.value?.demand_items ?? []
    return items.filter((i) => i.status === 'ongoing')
})
const archivedItems = computed<App.Data.PostItem.PostItemData[]>(() => {
    const items = farmer.value?.supply_items ?? dealer.value?.demand_items ?? []
    return items.filter((i) => i.status === 'expired')
})
const fulfilledItems = computed<App.Data.PostItem.PostItemData[]>(() => {
    const items = farmer.value?.supply_items ?? dealer.value?.demand_items ?? []
    return items.filter((i) => i.status === 'fulfilled')
})
const totalQuantity = computed(() => {
    const items = farmer.value?.supply_items ?? dealer.value?.demand_items ?? []
    return items.reduce((sum, i) => sum + i.quantity_kg, 0)
})

const historyLabel = computed(() => (props.profileType === 'farmer' ? 'Full Post History' : 'Full Demand History'))
const insights = computed(() => farmer.value?.insights ?? dealer.value?.insights ?? null)
const analyticsLocked = computed(() => farmer.value?.analytics_locked ?? dealer.value?.analytics_locked ?? true)
const teaserProps = computed(() =>
    props.profileType === 'farmer'
        ? {
              featureLabel: 'Premium Demand Forecasts',
              wasteTitle: 'Most Supplied Vegetables',
              wasteDescription: 'By total kilograms supplied',
              wasteGuideQuestion: 'What does this farmer sell most?',
              volumeTitle: '6-Month Supply Volume',
          }
        : {
              featureLabel: 'Premium Market Intelligence',
              wasteTitle: 'Most Ordered Vegetables',
              wasteDescription: 'By total kilograms demanded',
              wasteGuideQuestion: 'What does this dealer order most?',
              volumeTitle: '6-Month Demand Volume',
          },
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: useCapitalize(usePage().props.auth.user.roles[0]), href: dashboard().url },
    { title: displayName.value },
])
</script>

<template>
    <Head :title="displayName" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Deferred data="profile">
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
                            <Skeleton class="h-52 w-full rounded-xl" />
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
                    v-if="profile"
                    class="md:grid grid-cols-12 gap-5"
                >
                    <!-- Sidebar — identical across all three profile types -->
                    <div class="md:sticky h-fit top-6 col-span-12 lg:col-span-3">
                        <Card class="h-fit pt-0 overflow-hidden">
                            <div class="h-15 w-full bg-primary/10 mb-6" />
                            <Avatar class="absolute top-5 right-5 size-20 border-4 border-background">
                                <AvatarImage
                                    v-if="avatarUrl"
                                    :src="avatarUrl"
                                    :alt="displayName"
                                />
                                <AvatarFallback class="bg-primary text-base font-semibold text-background">
                                    {{ getInitials(displayName) }}
                                </AvatarFallback>
                            </Avatar>

                            <CardHeader>
                                <CardTitle class="uppercase">{{ displayName }}</CardTitle>
                                <CardDescription class="space-y-1">
                                    <div
                                        v-if="phoneNumber"
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <Phone class="size-3.5 shrink-0" />
                                        <span>{{ phoneNumber }}</span>
                                    </div>
                                    <div
                                        v-if="email"
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <Mail class="size-4 shrink-0" /><span class="truncate">{{ email }}</span>
                                    </div>
                                </CardDescription>
                            </CardHeader>

                            <!-- Farm location — farmer only, dealers/admins have no coordinates -->
                            <CardContent
                                v-if="farmer?.coordinates"
                                class="rounded overflow-hidden"
                            >
                                <LeafletMap
                                    :lat="farmer.coordinates.lat"
                                    :lng="farmer.coordinates.lng"
                                    :markers="[
                                        {
                                            lat: farmer.coordinates.lat,
                                            lng: farmer.coordinates.lng,
                                            popup: farmer.full_address,
                                        },
                                    ]"
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Main -->
                    <div class="col-span-12 space-y-4 lg:col-span-9">
                        <!--
                            analyticsLocked is driven by the VIEWER's own
                            subscription (SubscriptionFeature::hasAccessFor in
                            Shared\UserController), not the profile owner's.
                        -->
                        <UserTeaser
                            v-if="insights"
                            :insights="insights"
                            :locked="analyticsLocked"
                            :total-quantity="totalQuantity"
                            v-bind="teaserProps"
                        />

                        <!-- Basic (admin / roleless) profiles have no post history to show -->
                        <Collapsible
                            v-if="profileType !== 'basic'"
                            :default-open="false"
                        >
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-lg border bg-muted/20 px-4 py-2.5 text-sm font-medium hover:bg-muted/40">
                                {{ historyLabel }}
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
                                                                    <span class="text-sm font-medium text-foreground">
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

                        <Card v-else>
                            <CardContent class="flex h-24 items-center justify-center text-sm text-muted-foreground">
                                No supply or demand activity for this account.
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>