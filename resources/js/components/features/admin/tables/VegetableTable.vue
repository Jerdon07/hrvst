<script setup lang="ts">
import { EllipsisVertical, Eye, Pen, Trash, Vegan } from '@lucide/vue'
import {
    type ColumnDef,
} from '@tanstack/vue-table'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import type { Paginated } from '@/types'
import type { VegetableIndexData } from '@/types/resources/product'

const props = defineProps<{
    vegetables: Paginated<VegetableIndexData>
    searchQuery?: string
    sortKey?: string | null
    sortDirection?: 'asc' | 'desc' | null
}>()

const emit = defineEmits<{
    'open-edit-vegetable': [row: VegetableIndexData]
    'open-delete-vegetable': [row: VegetableIndexData]
    'open-vegetable-details': [row: VegetableIndexData]
    'page-change': [page: number]
    search: [query: string]
    sort: [payload: { key: string; direction: 'asc' | 'desc' }]
}>()

const columns: ColumnDef<VegetableIndexData>[] = [
    { 
        id: 'vegetable', 
        header: 'Vegetables', 
        accessorFn: (row) => row.display_name ?? '',
        enableSorting: true 
    },
    {
        id: 'category',
        header: 'Category',
        accessorFn: (row) => row.category?.name ?? '',
        enableSorting: true,
    },
    { 
        id: 'volume', 
        header: 'Supply & Demand', 
        enableSorting: false 
    },
    { 
        id: 'actions', 
        header: '', 
        enableSorting: false 
    },
]
</script>

<template>
    <DataTable
        :data="props.vegetables"
        :columns="columns"
        :search-query="searchQuery"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        search-placeholder="Search vegetables..."
        entity-name="vegetables"
        empty-message="No vegetables found"
        @page-change="$emit('page-change', $event)"
        @search="$emit('search', $event)"
        @sort="emit('sort', $event)"
    >
        <template #toolbar-actions>
            <slot name="toolbar-actions" />
        </template>

        <template #cell-vegetable="{ row }">
            <div class="flex items-center gap-2">
                <Avatar class="size-8 shrink-0 rounded-md">
                    <AvatarImage
                        v-if="row.image_url"
                        :src="row.image_url"
                        :alt="row.display_name"
                        class="object-cover"
                    />
                    <AvatarFallback class="rounded-md bg-primary/10 text-xs font-semibold text-primary">
                        <Vegan class="size-4" />
                    </AvatarFallback>
                </Avatar>
                <div class="flex flex-col">
                    <span class="font-semibold">{{ row.vegetable_name }} {{ row.variety_name ? `: ${row.variety_name}` : ''}}</span>
                    <span
                        v-if="row.local_name"
                        class="text-xs font-normal text-muted-foreground"
                    >
                        {{ row.local_name }}
                    </span>
                </div>
            </div>
        </template>

        <template #cell-category="{ row }">
            <Badge
                v-if="row.category"
                variant="outline"
            >
                {{ row.category.name }}
            </Badge>
            <span
                v-else
                class="text-xs text-muted-foreground"
            >&mdash;</span>
        </template>

        <template #cell-volume="{ row }">
            <div class="flex flex-col gap-1">
                <div class="flex w-fit items-center gap-1.5 text-xs">
                    <span class="size-1.5 rounded-full bg-green-500" />
                    <span class="font-medium tabular-nums">{{ row.supply_count ?? 0 }}</span>
                    <span class="text-muted-foreground">supplies</span>
                </div>
                <div class="flex w-fit items-center gap-1.5 text-xs">
                    <span class="size-1.5 rounded-full bg-orange-500" />
                    <span class="font-medium tabular-nums">{{ row.demand_count ?? 0 }}</span>
                    <span class="text-muted-foreground">demands</span>
                </div>
            </div>
        </template>

        <template #cell-actions="{ row }">
            <div class="flex items-center justify-end gap-1">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-muted-foreground hover:text-foreground"
                            aria-label="Row actions"
                        >
                            <EllipsisVertical />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent>
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                class="cursor-pointer"
                                @click="emit('open-vegetable-details', row)"
                            >
                                <Eye />
                                View Details
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                class="cursor-pointer"
                                @click="emit('open-edit-vegetable', row)"
                            >
                                <Pen />
                                Edit
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                class="cursor-pointer text-destructive focus:text-destructive"
                                @click="emit('open-delete-vegetable', row)"
                            >
                                <Trash />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </template>
    </DataTable>
</template>