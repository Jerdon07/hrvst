<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import { ClipboardList, Mail, Package, Phone } from '@lucide/vue'
import DataTable from '@/components/shared/tables/DataTable.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Button } from '@/components/ui/button'
import type { DealerResource, Paginated } from '@/types'

defineProps<{
    dealers: Paginated<DealerResource>
    searchQuery?: string
}>()

defineEmits<{
    'view-dealer': [dealer: DealerResource]
    'page-change': [page: number]
    search: [query: string]
}>()

const columns: ColumnDef<DealerResource>[] = [
    { id: 'expander', header: '' },
    {
        id: 'dealer',
        header: 'Dealer',
        accessorFn: (row) => row.user?.name ?? '',
        enableSorting: true,
    },
    {
        id: 'ongoing_demands_count',
        header: 'Demands',
        accessorFn: (row) => row.ongoing_demands_count ?? 0,
        enableSorting: true,
    },
    {
        id: 'joined_at',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
    },
    { id: 'actions', header: 'Actions', enableSorting: false },
]
</script>

<template>
    <DataTable
        :data="dealers"
        :columns="columns"
        :search-query="searchQuery"
        search-placeholder="Search dealers..."
        entity-name="dealers"
        empty-message="No dealers found."
        enable-expand
        @page-change="$emit('page-change', $event)"
        @search="$emit('search', $event)"
    >
        <template #cell-dealer="{ row }">
            <div class="flex flex-col gap-0.5">
                <span class="font-medium">{{ row.user?.name }}</span>
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <div class="flex items-center gap-1">
                        <Mail class="size-3" />
                        {{ row.user?.email }}
                    </div>
                    <div class="flex items-center gap-1">
                        <Phone class="size-3" />
                        {{ row.user?.phone_number }}
                    </div>
                </div>
            </div>
        </template>

        <template #cell-ongoing_demands_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">{{
                    row.ongoing_demands_count ?? 0
                }}</span>
            </div>
        </template>

        <template #cell-joined_at="{ row }">
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
                        @click="$emit('view-dealer', row)"
                    >
                        <ClipboardList class="size-4" />
                    </Button>
                </AppTooltip>
            </div>
        </template>
    </DataTable>
</template>
