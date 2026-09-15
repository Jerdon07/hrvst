<script setup lang="ts">
import { Deferred, Head, Link, router, useForm } from '@inertiajs/vue3'
import { Package, Plus, ShoppingBag, TriangleAlert } from '@lucide/vue'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import PostItem from '@/components/shared/schedule/PostItem.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { scheduleRegistry, type ScheduleType } from '@/lib/scheduleRegistry'
import type { BreadcrumbItem, Paginated, PostDataFixed } from '@/types'

const props = defineProps<{
    type: ScheduleType
    needsAction?: PostDataFixed[]
    items: Paginated<PostDataFixed> | null
}>()

const config = computed(() => scheduleRegistry[props.type])
const emptyIcon = computed(() => (props.type === 'supply' ? Package : ShoppingBag))

const deleteDialogOpen = ref(false)
const scheduleToDelete = ref<PostDataFixed | null>(null)
const deleteForm = useForm({})

function openDelete(schedule: PostDataFixed) {
    scheduleToDelete.value = schedule
    deleteDialogOpen.value = true
}

function handleDelete() {
    if (!scheduleToDelete.value) return
    deleteForm.delete(config.value.routes.destroy(scheduleToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false
            scheduleToDelete.value = null
        },
    })
}

function handlePageChange(page: number) {
    router.visit(config.value.routes.index({ query: { page } }).url, {
        preserveScroll: true,
        only: ['items'],
    })
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: config.value.roleLabel, href: config.value.dashboard().url },
    { title: `My ${config.value.noun.plural}`, href: config.value.routes.index().url },
])
</script>

<template>
    <Head :title="config.noun.plural" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <div class="flex items-end justify-between">
                <Heading
                    :title="`My ${config.noun.plural}`"
                    :description="`Schedule vegetable ${config.entityLabel === 'Supply' ? 'supplies' : 'demand'}.`"
                />
                <Button
                    class="gap-2"
                    as-child
                >
                    <Link :href="config.routes.create().url">
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
                        <PostItem
                            v-for="schedule in needsAction"
                            :key="schedule.id"
                            :post="schedule"
                            :item-noun-singular="config.noun.singular"
                            :item-noun-plural="config.noun.plural"
                            :entity-label="config.entityLabel"
                            :show-url="config.routes.show(schedule.id).url"
                            :edit-url="config.routes.edit(schedule.id).url"
                            :fulfill-url="(id) => config.routes.fulfill(id).url"
                            :expire-url="(id) => config.routes.expire(id).url"
                            @delete="openDelete(schedule)"
                        />
                    </div>
                </div>
            </Deferred>

            <Deferred data="items">
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
                    v-if="items?.data.length === 0"
                    title="No Ongoing Schedules"
                    :description="`${config.entityLabel === 'Supply' ? 'Post' : 'Schedule'} a new ${config.noun.singular} to get started.`"
                    :icon="emptyIcon"
                />

                <template v-else>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <PostItem
                            v-for="schedule in items!.data"
                            :key="schedule.id"
                            :post="schedule"
                            :item-noun-singular="config.noun.singular"
                            :item-noun-plural="config.noun.plural"
                            :entity-label="config.entityLabel"
                            :show-url="config.routes.show(schedule.id).url"
                            :edit-url="config.routes.edit(schedule.id).url"
                            :fulfill-url="(id) => config.routes.fulfill(id).url"
                            :expire-url="(id) => config.routes.expire(id).url"
                            @delete="openDelete(schedule)"
                        />
                    </div>

                    <div
                        v-if="items && items.last_page > 1"
                        class="flex items-center justify-between border-t pt-4"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="items.current_page === 1"
                            @click="handlePageChange(items.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            Page {{ items.current_page }} of {{ items.last_page }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="items.current_page === items.last_page"
                            @click="handlePageChange(items.current_page + 1)"
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
        :title="`Delete ${config.entityLabel}`"
        :description="`Permanently delete this ${config.noun.singular} for ${scheduleToDelete?.scheduled_date}?`"
        :processing="deleteForm.processing"
        variant="destructive"
        @action="handleDelete"
    />
</template>