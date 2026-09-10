<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { type ColumnDef } from '@tanstack/vue-table'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { archived, index } from '@/routes/farmer/supplies'
import type { BreadcrumbItem, PostDataFixed, Paginated } from '@/types'

interface Props {
    filters: { status: string }
    supplies?: Paginated<PostDataFixed>
}

const props = defineProps<Props>()

// ─── Column definitions ───────────────────────────────────────────────────────

const columns: ColumnDef<PostDataFixed>[] = [
    {
        id: 'items',
        header: 'Items',
        enableSorting: false,
    },
    {
        accessorKey: 'scheduled_date',
        header: 'Scheduled',
        enableSorting: false,
    },
    {
        accessorKey: 'time_slot',
        header: 'Slot',
        enableSorting: false,
    },
    {
        accessorKey: 'created_at_human',
        header: 'Posted',
        enableSorting: false,
    },
]

// ─── Status tabs + pagination ─────────────────────────────────────────────────

const currentStatus = computed(() => props.filters.status)

function handleStatusChange(value: string | number) {
    router.visit(archived({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['supplies', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(archived({ query: { status: props.filters.status, page } }).url, {
        preserveScroll: true,
        only: ['supplies'],
    })
}

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: index().url },
    { title: 'Archived', href: archived().url },
]
</script>

<template>
    <Head title="Archived Supplies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Archived Supplies"
                description="Expired and fulfilled supply schedules."
            />

            <!-- ── Status tabs ────────────────────────────────────────── -->
            <Tabs
                :model-value="currentStatus"
                @update:model-value="handleStatusChange"
            >
                <TabsList>
                    <TabsTrigger value="expired">Expired</TabsTrigger>
                    <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
                </TabsList>
            </Tabs>

            <!-- ── Table ─────────────────────────────────────────────── -->
            <Deferred data="supplies">
                <template #fallback>
                    <div class="space-y-2">
                        <Skeleton class="h-10 w-full rounded-lg" />
                        <Skeleton
                            v-for="i in 8"
                            :key="i"
                            class="h-14 w-full rounded-lg"
                        />
                    </div>
                </template>

                <DataTable
                    v-if="supplies"
                    :data="supplies"
                    :columns="columns"
                    :enable-search="false"
                    entity-name="supplies"
                    empty-message="No archived supplies found."
                    @page-change="handlePageChange"
                >
                    <template #cell-items="{ row }">
                        <div
                            v-if="row.post_items?.length"
                            class="space-y-0.5"
                        >
                            <p
                                v-for="item in row.post_items"
                                :key="item.id"
                                class="text-sm"
                            >
                                <span class="font-medium">
                                    {{ item.display_name }}
                                </span>
                                <span class="text-muted-foreground">
                                    &mdash; {{ item.quantity_kg }} kg</span>
                            </p>
                        </div>
                        <span
                            v-else
                            class="text-muted-foreground"
                        >&mdash;</span>
                    </template>

                    <template #cell-time_slot="{ row }">
                        <span class="capitalize">{{
                            row.time_slot?.replace(/_/g, ' ') ?? '&mdash;'
                        }}</span>
                    </template>
                </DataTable>
            </Deferred>
        </div>
    </AppLayout>
</template>
