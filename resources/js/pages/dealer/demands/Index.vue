<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from '@inertiajs/vue3'
import { Plus, ShoppingBag } from '@lucide/vue'
import { ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import DemandItem from '@/components/features/dealer/DemandItem.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import dealer from '@/routes/dealer'
import { create, destroy, index } from '@/routes/dealer/demands'
import type {
    BreadcrumbItem,
    DealerDemandsProps,
    PostDataFixed,
} from '@/types'

defineProps<DealerDemandsProps>()

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteDialogOpen = ref(false)
const demandToDelete = ref<PostDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(demand: PostDataFixed) {
    demandToDelete.value = demand
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!demandToDelete.value) return
    deleteForm.delete(destroy(demandToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false
            demandToDelete.value = null
        },
    })
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function handlePageChange(page: number) {
    router.visit(index({ query: { page } }).url, {
        preserveScroll: true,
        only: ['demands'],
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dealer', href: dealer.dashboard().url },
    { title: 'Demands', href: dealer.demands.index().url },
]
</script>

<template>
    <Head title="Demands" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    title="My Demands"
                    description="Schedule vegetable demand."
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
                        <DemandItem
                            v-for="demand in needsAction"
                            :key="demand.id"
                            :demand="demand"
                            @delete="openDelete(demand)"
                        />
                    </div>
                </div>
            </Deferred>

            <!-- ── Demand list (Ongoing only) ─────────────────────────── -->
            <Deferred data="demands">
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
                    v-if="demands?.data.length === 0"
                    title="No Ongoing Demands"
                    description="Schedule a new demand to get started."
                    :icon="ShoppingBag"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <DemandItem
                            v-for="demand in demands!.data"
                            :key="demand.id"
                            :demand="demand"
                            @delete="openDelete(demand)"
                        />
                    </div>

                    <div
                        v-if="demands && demands.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="demands.current_page === 1"
                            @click="handlePageChange(demands.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ demands.current_page }} of
                            {{ demands.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                demands.current_page === demands.last_page
                            "
                            @click="handlePageChange(demands.current_page + 1)"
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
        title="Delete Schedule"
        :description="`Permanently delete this demand for ${demandToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>
