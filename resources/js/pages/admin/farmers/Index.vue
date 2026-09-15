<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3'
import {
    List,
    Loader2,
    MapIcon,
    SearchX,
    UserRoundPlus,
} from '@lucide/vue'
import axios from 'axios'
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
    details as farmerDetails,
    markers as farmerMarkers,
} from '@/actions/App/Http/Controllers/Admin/FarmerController'
import EmptyState from '@/components/EmptyState.vue'
import FarmerBarangaySheet from '@/components/features/admin/dialogs/FarmerBarangaySheet.vue'
import FarmerDetailSidebar from '@/components/features/admin/dialogs/FarmerDetailSidebar.vue'
import FarmerMap from '@/components/features/admin/map/FarmerMap.vue'
import FarmerMapFilters from '@/components/features/admin/map/FarmerMapFilters.vue'
import FarmerTable from '@/components/features/admin/tables/FarmerTable.vue'
import Heading from '@/components/Heading.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import { index } from '@/routes/admin/farmers'
import users from '@/routes/admin/users'
import type {
    AdminFarmersProps,
    BreadcrumbItem,
    FarmerMarker,
    FarmerResource,
} from '@/types'

const props = defineProps<AdminFarmersProps>()

/* -- State -- */
const currentView = ref<'list' | 'map'>(props.view)
const markers = ref<FarmerMarker[]>([])
const selectedMunicipality = ref<string | null>(null)
const selectedVegetable = ref<string | null>(null)
const loadingMarkers = ref(false)
const sidebarOpen = ref(false)
const selectedFarmer = ref<FarmerResource | null>(null)
const loadingFarmer = ref(false)
const barangaySheetOpen = ref(false)
const barangayFarmers = ref<FarmerMarker[]>([])
const barangayName = ref('')
const mapBounds = ref<{
    north: number
    south: number
    east: number
    west: number
} | null>(null)

/* -- Computed -- */
const isListView = computed(() => currentView.value === 'list')
const isMapView = computed(() => currentView.value === 'map')

const totalVisiblePlantings = computed(() =>
    markers.value.reduce((sum, m) => sum + m.ongoing_supplies_count, 0),
)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Farmers', href: admin.farmers.index().url },
]

const searchQuery = ref(props.filters?.search ?? '')

/* -- View Toggle -- */
function switchView(newView: 'list' | 'map') {
    if (newView === currentView.value) return

    router.visit(admin.farmers.index().url, {
        data: { view: newView },
        preserveState: true,
        preserveScroll: true,
        only: newView === 'list' ? ['farmers', 'summary'] : [],
        onSuccess: (page) => {
            const landedOnFarmersMap =
                page.component === 'admin/farmers/Index' && page.props.view === newView

            if (landedOnFarmersMap) {
                currentView.value = newView
                localStorage.setItem('farmers_view', newView)
            } else {
                currentView.value = 'list'
                localStorage.setItem('farmers_view', 'list')
            }
        },
    })
}

/* -- Data Fetching -- */
async function fetchMarkers() {
    loadingMarkers.value = true
    try {
        const params: Record<string, unknown> = {}
        if (selectedMunicipality.value)
            params.municipality_id = selectedMunicipality.value
        if (selectedVegetable.value) params.vegetable_id = selectedVegetable.value
        if (mapBounds.value) params.bounds = mapBounds.value

        const { data } = await axios.get<{
            markers: FarmerMarker[]
            total: number
        }>(farmerMarkers().url, { params })
        markers.value = data.markers
    } catch (error: unknown) {
        const message =
            error instanceof Error
                ? error.message
                : 'Failed to load farmer markers'
        toast.error('Error loading markers', { description: message })
    } finally {
        loadingMarkers.value = false
    }
}

async function loadFarmerDetails(farmerId: number) {
    loadingFarmer.value = true
    sidebarOpen.value = true
    selectedFarmer.value = null

    try {
        const { data } = await axios.get<FarmerResource>(
            farmerDetails(farmerId).url,
        )
        selectedFarmer.value = data
    } catch (error: unknown) {
        const message =
            error instanceof Error
                ? error.message
                : 'Failed to load farmer information'
        toast.error('Error loading farmer details', { description: message })
        sidebarOpen.value = false
    } finally {
        loadingFarmer.value = false
    }
}

/* -- Event Handlers -- */
function openFarmerSidebar(farmerId: number) {
    loadFarmerDetails(farmerId)
}

function closeSidebar() {
    sidebarOpen.value = false
    selectedFarmer.value = null
}

function handleBarangayClick(farmers: FarmerMarker[], name: string): void {
    barangayFarmers.value = farmers
    barangayName.value = name
    barangaySheetOpen.value = true
}

function handleFarmerFromBarangay(farmerId: number): void {
    barangaySheetOpen.value = false
    openFarmerSidebar(farmerId)
}

function handleSearch(query: string) {
    searchQuery.value = query
    router.visit(index().url, {
        data: { search: query || undefined },
        preserveState: true,
        preserveScroll: true,
        only: ['farmers', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(admin.farmers.index().url, {
        data: { page, view: 'list', search: searchQuery.value || undefined },
        preserveState: true,
        preserveScroll: true,
    })
}

function handleBoundsChange(bounds: {
    north: number
    south: number
    east: number
    west: number
}) {
    mapBounds.value = bounds
}

function handleClearFilters() {
    selectedMunicipality.value = null
    selectedVegetable.value = null
}

/* -- Watchers -- */
watch(
    [currentView, selectedMunicipality, selectedVegetable, mapBounds],
    () => {
        if (currentView.value === 'map') {
            fetchMarkers()
        }
    },
    { immediate: true },
)

const storedView = localStorage.getItem('farmers_view') as 'list' | 'map' | null
if (storedView === 'map' && props.view !== 'map') {
    switchView(storedView)
}
</script>

<template>
    <Head title="Farmers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <!-- Header -->
            <div class="flex items-end justify-between">
                <Heading
                    title="Farmers"
                    description="Manage farmers and their supplies."
                />

                <div class="flex items-center gap-2">
                    <ToggleGroup
                        :model-value="currentView"
                        variant="outline"
                        type="single"
                    >
                        <ToggleGroupItem
                            value="list"
                            aria-label="List view"
                            @click="switchView('list')"
                        >
                            <List class="size-4" />
                            <span class="hidden sm:inline">List</span>
                        </ToggleGroupItem>
                        <ToggleGroupItem
                            value="map"
                            aria-label="Map view"
                            @click="switchView('map')"
                        >
                            <MapIcon class="size-4" />
                            <span class="hidden sm:inline">Map</span>
                        </ToggleGroupItem>
                    </ToggleGroup>

                    <Button as-child >
                        <Link :href="users.farmers.create()">
                            <UserRoundPlus :size="20" />
                            Register Farmer
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Summary Cards -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-2">
                        <Skeleton
                            v-for="i in 4"
                            :key="i"
                            class="h-20"
                        />
                    </div>
                </template>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-2">
                    <SmallCard
                        title="Total Farmers"
                        :value="summary.total_farmers.toLocaleString()"
                        subtext="all registered"
                    />
                    <SmallCard
                        title="New Farmers"
                        :value="summary.new_farmers_this_month.toLocaleString()"
                        subtext="new this month"
                    />
                    <SmallCard
                        title="Total Supplies"
                        :value="summary.total_supplies.toLocaleString()"
                        subtext="all supplies posted"
                    />
                    <SmallCard
                        title="New Supplies"
                        :value="summary.new_supplies_this_month.toLocaleString()"
                        subtext="supplies this month"
                    />
                </div>
            </Deferred>

            <!-- LIST VIEW -->
            <div v-if="isListView">
                <Deferred data="farmers">
                    <template #fallback>
                        <div class="flex flex-col gap-4">
                            <Skeleton class="h-10 w-80" />
                            <div class="space-y-3 rounded-lg border p-4">
                                <Skeleton
                                    v-for="i in 5"
                                    :key="i"
                                    class="h-16 w-full"
                                />
                            </div>
                        </div>
                    </template>

                    <FarmerTable
                        v-if="farmers"
                        :farmers="farmers"
                        :search-query="searchQuery"
                        @view-farmer="openFarmerSidebar($event.id)"
                        @page-change="handlePageChange"
                        @search="handleSearch"
                    />

                    <EmptyState
                        v-else
                        title="No Farmers Yet"
                        description="Please register some farmers to see them listed here."
                        :icon="SearchX"
                    />
                </Deferred>
            </div>

            <!-- MAP VIEW -->
            <div
                v-if="isMapView"
                class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]"
            >
                <div class="relative h-full min-h-[600px] w-full overflow-hidden rounded-lg border shadow-sm">
                    <Transition
                        enter-active-class="transition-opacity duration-200"
                        leave-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0"
                        leave-to-class="opacity-0"
                    >
                        <div
                            v-if="loadingMarkers"
                            class="absolute inset-0 z-30 flex items-center justify-center bg-background/80 backdrop-blur-sm"
                        >
                            <div class="flex items-center gap-2 rounded-lg border bg-card p-4 shadow-lg">
                                <Loader2 class="size-4 animate-spin" />
                                <span class="text-sm font-medium">Loading farmers...</span>
                            </div>
                        </div>
                    </Transition>

                    <FarmerMap
                        :markers="markers"
                        :center="mapConfig.center"
                        :zoom="mapConfig.defaultZoom ?? 13"
                        @barangay-click="handleBarangayClick"
                        @bounds-change="handleBoundsChange"
                    />
                </div>

                <div class="flex flex-col gap-4">
                    <FarmerMapFilters
                        :municipalities="filters.municipalities"
                        :plantings="filters.supplies"
                        :selected-municipality="selectedMunicipality"
                        :selected-variety="selectedVegetable"
                        @update:selected-municipality="
                            selectedMunicipality = $event
                        "
                        @update:selected-variety="selectedVegetable = $event"
                        @clear="handleClearFilters"
                    />

                    <div class="rounded-lg border bg-card p-4">
                        <p class="mb-3 text-sm font-semibold">Map Statistics</p>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Visible farmers</span>
                                <span class="font-mono font-medium">{{
                                    markers.length
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Total active plantings</span>
                                <span class="font-mono font-medium">{{
                                    totalVisiblePlantings
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-4">
                        <p class="mb-3 text-sm font-semibold">Legend</p>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-blue-500"/>
                                <span>Cluster (multiple farmers)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-green-500"/>
                                <span>Leafy vegetables</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-orange-500"/>
                                <span>Root vegetables</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-red-500"/>
                                <span>Fruiting vegetables</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="size-3 shrink-0 rounded-full bg-gray-500"/>
                                <span>Other varieties</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-dashed bg-muted/30 p-4">
                        <p class="mb-2 text-xs font-medium">How to use</p>
                        <ul class="space-y-1 text-xs text-muted-foreground">
                            <li>• Click markers to view farmer details</li>
                            <li>• Use filters to narrow results</li>
                            <li>• Zoom in to see individual farmers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <FarmerDetailSidebar
            :open="sidebarOpen"
            :farmer="selectedFarmer"
            :loading="loadingFarmer"
            @close="closeSidebar"
        />

        <FarmerBarangaySheet
            :open="barangaySheetOpen"
            :farmers="barangayFarmers"
            :barangay-name="barangayName"
            @close="barangaySheetOpen = false"
            @view-farmer="handleFarmerFromBarangay"
        />
    </AppLayout>
</template>
