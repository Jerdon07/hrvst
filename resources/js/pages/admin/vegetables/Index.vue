<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import { Plus, Vegan } from '@lucide/vue'
import { ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import VegetableForm from '@/components/features/admin/forms/VegetableForm.vue'
import VegetableTable from '@/components/features/admin/tables/VegetableTable.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import admin, { dashboard } from '@/routes/admin'
import { destroy as destroyVeg, index } from '@/routes/admin/vegetables'
import type { AdminVegetablesProps, BreadcrumbItem, VegetableIndexData } from '@/types'

const props = defineProps<AdminVegetablesProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: dashboard().url },
    { title: 'Vegetables', href: index().url },
]

const searchQuery = ref(props.filters?.search ?? '')
const categoryFilter = ref(props.filters?.category_id ? String(props.filters.category_id) : 'all')

const formOpen = ref(false)
const editTarget = ref<VegetableIndexData | null>(null)
const deleteOpen = ref(false)
const deleteTarget = ref<VegetableIndexData | null>(null)

function openCreate(): void {
    editTarget.value = null
    formOpen.value = true
}

function openEdit(row: VegetableIndexData): void {
    editTarget.value = row
    formOpen.value = true
}

function openDelete(row: VegetableIndexData): void {
    deleteTarget.value = row
    deleteOpen.value = true
}

function handleDelete(): void {
    if (!deleteTarget.value) return
    router.delete(destroyVeg(deleteTarget.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false
            deleteTarget.value = null
        },
    })
}

function visit(overrides: { search?: string; category_id?: string } = {}) {
    const search = overrides.search ?? searchQuery.value
    const categoryId = overrides.category_id ?? categoryFilter.value

    router.visit(index().url, {
        data: {
            search: search || undefined,
            category_id: categoryId !== 'all' ? categoryId : undefined,
        },
        preserveState: true,
        preserveScroll: true,
        only: ['vegetables', 'filters'],
    })
}

function handlePageChange(page: number) {
    router.visit(index().url, {
        data: {
            page,
            search: searchQuery.value || undefined,
            category_id: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
        },
        preserveState: true,
        preserveScroll: true,
    })
}

function handleSearch(query: string): void {
    searchQuery.value = query
    visit({ search: query })
}

function handleCategoryFilterChange(value: unknown): void {
    const next = value == null ? 'all' : String(value)
    categoryFilter.value = next
    visit({ category_id: next })
}
</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <div class="flex flex-col sm:flex-row space-y-2 items-end justify-between">
                <Heading
                    title="Vegetables"
                    description="Manage all vegetable entries across every category."
                />
                <Button @click="openCreate">
                    <Plus />
                    Add Vegetable
                </Button>
            </div>

            <Deferred data="vegetables">
                <template #fallback>
                    <div class="flex flex-col gap-3">
                        <Skeleton class="h-9 w-72" />
                        <div class="rounded-lg border">
                            <div class="space-y-2 p-4">
                                <Skeleton
                                    v-for="i in 6"
                                    :key="i"
                                    class="h-11 w-full"
                                />
                            </div>
                        </div>
                    </div>
                </template>

                <VegetableTable
                    v-if="vegetables"
                    :vegetables="vegetables"
                    :search-query="searchQuery"
                    @open-edit-vegetable="openEdit"
                    @open-delete-vegetable="openDelete"
                    @open-vegetable-details="(row) => router.visit(admin.vegetables.show({ vegetable: row.id }).url)"
                    @page-change="handlePageChange"
                    @search="handleSearch"
                >
                    <template #toolbar-actions>
                        <Select
                            :model-value="categoryFilter"
                            @update:model-value="handleCategoryFilterChange"
                        >
                            <SelectTrigger
                                size="sm"
                                class="w-full sm:w-60"
                            >
                                <Vegan />
                                <SelectValue placeholder="All categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All categories</SelectItem>
                                <SelectItem
                                    v-for="c in categories"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </template>
                </VegetableTable>
            </Deferred>
        </div>
    </AppLayout>

    <VegetableForm
        :open="formOpen"
        :vegetable="editTarget"
        :categories="categories"
        @update:open="formOpen = $event"
        @success="formOpen = false"
    />

    <ConfirmationDialog
        v-model:open="deleteOpen"
        title="Delete Vegetable"
        :description="`Are you sure you want to delete '${deleteTarget?.display_name}'? This cannot be undone.`"
        variant="destructive"
        @action="handleDelete"
    />
</template>