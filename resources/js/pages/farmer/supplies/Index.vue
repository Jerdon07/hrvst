<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from '@inertiajs/vue3'
import { Package, Plus } from '@lucide/vue'
import { ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import SupplyForm from '@/components/features/farmer/SupplyForm.vue'
import SupplyItem from '@/components/features/farmer/SupplyItem.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { create, destroy, index } from '@/routes/farmer/supplies'
import type {
    BreadcrumbItem,
    FarmerSuppliesProps,
    FarmerSupplyDataFixed,
} from '@/types'

defineProps<FarmerSuppliesProps>()

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteDialogOpen = ref(false)
const supplyToDelete = ref<FarmerSupplyDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(supply: FarmerSupplyDataFixed) {
    supplyToDelete.value = supply
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!supplyToDelete.value) return
    deleteForm.delete(destroy(supplyToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false
            supplyToDelete.value = null
        },
    })
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function handlePageChange(page: number) {
    router.visit(index({ query: { page } }).url, {
        preserveScroll: true,
        only: ['supplies'],
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Supplies', href: farmer.supplies.index().url },
]
</script>

<template>
    <Head title="Supplies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="My Supplies"
                    description="Schedule vegetable supplies."
                />
                <Button
                    class="gap-2"
                    as-child
                >
                    <Link :href="create().url">
                        <Plus class="size-4" />
                        New Schedule
                    </Link>
                </Button>
            </div>

            <Deferred data="needsAction">
                <template #fallback>
                    <Skeleton class="h-20 w-full rounded-lg" />
                </template>

                <div
                    v-if="needsAction?.length"
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-center gap-2">
                        <TriangleAlert class="size-4 text-destructive" />
                        <h2 class="text-sm font-semibold">Needs Action</h2>
                        <span class="text-xs text-muted-foreground">
                            {{ needsAction.length }} schedule{{ needsAction.length === 1 ? '' : 's' }} completed
                        </span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <SupplyItem
                            v-for="supply in needsAction"
                            :key="supply.id"
                            :supply="supply"
                            @delete="openDelete(supply)"
                        />
                    </div>
                </div>
            </Deferred>

            <!-- ── Supply list (Ongoing only) ─────────────────────────── -->
            <Deferred data="supplies">
                <template #fallback>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Skeleton
                            v-for="i in 8"
                            :key="i"
                            class="h-36 rounded-lg"
                        />
                    </div>
                </template>

                <EmptyState
                    v-if="supplies?.data.length === 0"
                    title="No Ongoing Schedules"
                    description="Post a new schedule to get started."
                    :icon="Package"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <SupplyItem
                            v-for="supply in supplies!.data"
                            :key="supply.id"
                            :supply="supply"
                            @delete="openDelete(supply)"
                        />
                    </div>

                    <div
                        v-if="supplies && supplies.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="supplies.current_page === 1"
                            @click="handlePageChange(supplies.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ supplies.current_page }} of
                            {{ supplies.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                supplies.current_page === supplies.last_page
                            "
                            @click="handlePageChange(supplies.current_page + 1)"
                        >
                            Next
                        </Button>
                    </div>
                </template>
            </Deferred>
        </div>
    </AppLayout>

    <ConfirmationDialog
        v-model:open="deleteDialogOpen"
        title="Delete Supply"
        :description="`Permanently delete this supply for ${supplyToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
