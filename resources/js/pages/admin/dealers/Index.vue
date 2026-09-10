<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3'
import {
    Package,
    PackagePlus,
    UserPlus,
    UserRoundPlus,
    Users,
} from '@lucide/vue'
import axios from 'axios'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import { details as dealerDetails } from '@/actions/App/Http/Controllers/Admin/DealerController'
import DealerDetailSidebar from '@/components/features/admin/dialogs/DealerDetailSidebar.vue'
import DealerTable from '@/components/features/admin/tables/DealerTable.vue'
import Heading from '@/components/Heading.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import users from '@/routes/admin/users'
import type { AdminDealersProps, BreadcrumbItem, DealerResource } from '@/types'

const props = defineProps<AdminDealersProps>()

const selectedDealer = ref<DealerResource | null>(null)
const sidebarOpen = ref(false)
const loadingDealer = ref(false)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Dealers', href: admin.dealers.index().url },
]

const searchQuery = ref(props.filters?.search ?? '')

async function loadDealerDetails(dealerId: number) {
    loadingDealer.value = true
    selectedDealer.value = null
    sidebarOpen.value = true
    try {
        const { data } = await axios.get<DealerResource>(
            dealerDetails(dealerId).url,
        )
        selectedDealer.value = data
    } catch (error: unknown) {
        const message =
            error instanceof Error
                ? error.message
                : 'Failed to load dealer information'
        toast.error('Error loading dealer details', { description: message })
        sidebarOpen.value = false
    } finally {
        loadingDealer.value = false
    }
}

function openDealerSidebar(dealerId: number) {
    loadDealerDetails(dealerId)
}

function closeSidebar() {
    sidebarOpen.value = false
    selectedDealer.value = null
}

function handlePageChange(page: number) {
    router.visit(admin.dealers.index(), {
        data: { page, search: searchQuery.value || undefined },
        preserveState: true,
        preserveScroll: true,
    })
}

function handleSearch(query: string) {
    searchQuery.value = query
    router.visit(admin.dealers.index().url, {
        data: { search: query || undefined },
        preserveState: true,
        preserveScroll: true,
        only: ['dealers', 'filters'],
    })
}
</script>

<template>
    <Head title="Dealers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="Dealers"
                    description="Manage approved dealers and their activity metrics"
                />

                <Button
                    as-child
                    variant="outline"
                >
                    <Link :href="users.dealers.create()">
                        <UserRoundPlus :size="20" />
                        Register Dealer
                    </Link>
                </Button>
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

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-2">
                    <SmallCard
                        title="Total Dealers"
                        :value="summary.total_dealers"
                        subtext="all approved dealers"
                        :icon="Users"
                    />
                    <SmallCard
                        title="New Dealers"
                        :value="summary.new_dealers_this_month"
                        subtext="registered this month"
                        :icon="UserPlus"
                    />
                    <SmallCard
                        title="Total Demands"
                        :value="summary.total_demands"
                        subtext="all scheduled demands"
                        :icon="Package"
                    />
                    <SmallCard
                        title="New Demands"
                        :value="summary.new_demands_this_month"
                        subtext="scheduled this month"
                        :icon="PackagePlus"
                    />
                </div>
            </Deferred>

            <Deferred data="dealers">
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

                <DealerTable
                    :dealers="dealers"
                    :search-query="searchQuery"
                    @view-dealer="openDealerSidebar($event.id)"
                    @page-change="handlePageChange"
                    @search="handleSearch"
                />
            </Deferred>
        </div>
    </AppLayout>

    <DealerDetailSidebar
        :open="sidebarOpen"
        :dealer="selectedDealer"
        :loading="loadingDealer"
        @close="closeSidebar"
    />
</template>
