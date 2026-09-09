<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import { ClipboardList, Mail, MapPin, Package, Phone } from '@lucide/vue'
import DataTable from '@/components/shared/tables/DataTable.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Button } from '@/components/ui/button'
import type { FarmerResource, Paginated } from '@/types'

defineProps<{
    farmers: Paginated<FarmerResource>
    searchQuery?: string
}>()

defineEmits<{
    'view-farmer': [farmer: FarmerResource]
    'page-change': [page: number]
    search: [query: string]
}>()

const columns: ColumnDef<FarmerResource>[] = [
    { id: 'expander', header: '' },
    {
        id: 'farmer',
        header: 'Farmer',
        accessorFn: (row) => row.user?.name ?? '',
        enableSorting: true,
    },
    {
        id: 'ongoing_supplies_count',
        header: 'Supplies',
        accessorFn: (row) => row.ongoing_supplies_count ?? 0,
    },
    {
        id: 'address',
        header: 'Address',
        accessorFn: (row) =>
            `${row.barangay}`,
        enableSorting: true,
    },
    {
        id: 'joined',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
    },
    { id: 'actions', header: 'Actions', enableSorting: false },
]
</script>

<template>
    <DataTable
        :data="farmers"
        :columns="columns"
        :search-query="searchQuery"
        search-placeholder="Search farmers..."
        entity-name="farmers"
        empty-message="No farmers found"
        @page-change="$emit('page-change', $event)"
        @search="$emit('search', $event)"
    >
        <template #cell-farmer="{ row }">
            <div class="flex items-center gap-3">
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ row.user?.name }}</span>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <Phone class="size-3" />
                            {{ row.user?.phone_number }}
                        </div>
                        <div
                            v-if="row.user?.email"
                            class="flex items-center gap-1"
                        >
                            <Mail class="size-3" />
                            {{ row.user.email }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #cell-ongoing_supplies_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">{{
                    row.ongoing_supplies_count ?? 0
                }}</span>
            </div>
        </template>

        <template #cell-address="{ row }">
            <div class="flex items-start gap-2">
                <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{
                        row.barangay
                    }}</span>
                    <span class="text-xs text-muted-foreground">
                        {{ row.municipality }},
                        {{ row.province }}
                    </span>
                </div>
            </div>
        </template>

        <template #cell-joined="{ row }">
            <AppTooltip :content="`Joined on ${row.joined_at}`">
                <span class="cursor-help text-sm">{{ row.joined_at_human }}</span>
            </AppTooltip>
        </template>

        <template #cell-actions="{ row }">
            <div class="flex items-center gap-1.5">
                <AppTooltip content="View details">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="text-muted-foreground hover:text-foreground"
                        @click="$emit('view-farmer', row)"
                    >
                        <ClipboardList class="size-4" />
                    </Button>
                </AppTooltip>
            </div>
        </template>
    </DataTable>
</template>
