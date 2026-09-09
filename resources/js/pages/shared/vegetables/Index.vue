<script setup lang="ts">
import { Deferred, Head, router, usePage } from '@inertiajs/vue3'
import { Search, Vegan } from '@lucide/vue'
import { computed, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import VegetableItem from '@/components/shared/cards/VegetableItem.vue'
import { Button } from '@/components/ui/button'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { dashboard } from '@/routes'
import { index } from '@/routes/vegetables'
import type { BreadcrumbItem, SharedVegetablesProps } from '@/types'

const props = defineProps<SharedVegetablesProps>()

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: useCapitalize(usePage().props.auth.user.roles[0]),
        href: dashboard().url,
    },
    { title: 'Vegetables', href: index().url },
])

const searchQuery = ref(props.filters?.search ?? '')
const categoryFilter = ref(
    props.filters?.category_id ? String(props.filters.category_id) : 'all',
)

let searchTimeout: ReturnType<typeof setTimeout> | null = null

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

function handleSearchInput(): void {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => visit({ search: searchQuery.value }), 300)
}

function handleCategoryFilterChange(value: unknown): void {
    const next = value == null ? 'all' : String(value)
    categoryFilter.value = next
    visit({ category_id: next })
}

function handlePageChange(page: number): void {
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
</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Vegetables"
                description="Browse market activity and forecast per vegetable."
            />

            <div class="flex flex-wrap items-center gap-2">
                <InputGroup class="max-w-xs">
                    <InputGroupInput
                        v-model="searchQuery"
                        placeholder="Search vegetables..."
                        @input="handleSearchInput"
                    />
                    <InputGroupAddon>
                        <Search />
                    </InputGroupAddon>
                </InputGroup>

                <Select
                    :model-value="categoryFilter"
                    @update:model-value="handleCategoryFilterChange"
                >
                    <SelectTrigger
                        size="sm"
                        class="w-44"
                    >
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
            </div>

            <Deferred data="vegetables">
                <template #fallback>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Skeleton
                            v-for="i in 6"
                            :key="i"
                            class="h-16 w-full rounded-lg"
                        />
                    </div>
                </template>

                <EmptyState
                    v-if="vegetables?.data.length === 0"
                    title="No vegetables found"
                    description="Try a different search or category."
                    :icon="Vegan"
                />

                <template v-else-if="vegetables">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <VegetableItem
                            v-for="vegetable in vegetables.data"
                            :key="vegetable.id"
                            :vegetable="vegetable"
                        />
                    </div>

                    <div
                        v-if="vegetables.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="vegetables.current_page === 1"
                            @click="handlePageChange(vegetables.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ vegetables.current_page }} of {{ vegetables.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="vegetables.current_page === vegetables.last_page"
                            @click="handlePageChange(vegetables.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </template>
            </Deferred>
        </div>
    </AppLayout>
</template>