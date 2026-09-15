<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { type ColumnDef } from '@tanstack/vue-table'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AppLayout from '@/layouts/AppLayout.vue'
import { scheduleRegistry, type ScheduleType } from '@/lib/scheduleRegistry'
import type { BreadcrumbItem, Paginated, PostDataFixed } from '@/types'

const props = defineProps<{
    type: ScheduleType
    filters: { status: string }
    items?: Paginated<PostDataFixed>
}>()

const config = computed(() => scheduleRegistry[props.type])

const columns: ColumnDef<PostDataFixed>[] = [
    { id: 'items', header: 'Items', enableSorting: false },
    { accessorKey: 'scheduled_date', header: 'Scheduled', enableSorting: false },
    { accessorKey: 'time_slot', header: 'Slot', enableSorting: false },
    { accessorKey: 'created_at_human', header: 'Posted', enableSorting: false },
]

function handleStatusChange(value: string | number) {
    router.visit(config.value.routes.archived({ query: { status: value } }).url, {
        preserveState: true,
        preserveScroll: true,
        only: ['items', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(config.value.routes.archived({ query: { status: props.filters.status, page } }).url, {
        preserveScroll: true,
        only: ['items'],
    })
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: config.value.roleLabel, href: config.value.dashboard().url },
    { title: config.value.entityLabel === 'Supply' ? 'Supplies' : 'Demands', href: config.value.routes.index().url },
    { title: 'Archived' },
])
</script>

<template>
    <Head :title="`Archived ${config.noun.plural}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <Heading
                :title="`Archived ${config.noun.plural}`"
                description="Expired and fulfilled schedules."
            />

            <Tabs
                :model-value="filters.status"
                @update:model-value="handleStatusChange"
            >
                <TabsList>
                    <TabsTrigger value="expired">Expired</TabsTrigger>
                    <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
                </TabsList>
            </Tabs>

            <Deferred data="items">
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
                    v-if="items"
                    :data="items"
                    :columns="columns"
                    :enable-search="false"
                    :entity-name="config.noun.plural"
                    :empty-message="`No archived ${config.noun.plural} found.`"
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
                                <span class="font-medium">{{ item.display_name }}</span>
                                <span class="text-muted-foreground"> &mdash; {{ item.quantity_kg }} kg</span>
                            </p>
                        </div>
                        <span
                            v-else
                            class="text-muted-foreground"
                        >&mdash;</span>
                    </template>

                    <template #cell-time_slot="{ row }">
                        <span class="capitalize">{{ row.time_slot?.replace(/_/g, ' ') ?? '&mdash;' }}</span>
                    </template>
                </DataTable>
            </Deferred>
        </div>
    </AppLayout>
</template>