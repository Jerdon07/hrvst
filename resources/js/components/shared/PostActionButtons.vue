<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { CircleCheck, CircleX } from '@lucide/vue'
import { computed, ref } from 'vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    fulfillUrl: string
    expireUrl: string
    label: string
    only?: string[]
}>()

type PendingAction = 'fulfill' | 'expire' | null

const pendingAction = ref<PendingAction>(null)
const processing = ref(false)

const DIALOG_CONFIG = {
    fulfill: { title: 'Mark as Fulfilled', actionName: 'Fulfill', variant: 'default' as const },
    expire: { title: 'Mark as Expired', actionName: 'Expire', variant: 'destructive' as const },
}

const isOpen = computed({
    get: () => pendingAction.value !== null,
    set: (value: boolean) => {
        if (!value) pendingAction.value = null
    },
})

const activeConfig = computed(() =>
    pendingAction.value ? DIALOG_CONFIG[pendingAction.value] : null,
)

function submit(): void {
    if (!pendingAction.value) return

    const url = pendingAction.value === 'fulfill' ? props.fulfillUrl : props.expireUrl
    processing.value = true

    router.post(url, {}, {
        preserveScroll: true,
        only: props.only,
        onFinish: () => {
            processing.value = false
            pendingAction.value = null
        },
    })
}
</script>

<template>
    <div class="flex items-center gap-1">
        <AppTooltip content="Mark fulfilled">
            <Button
                variant="ghost"
                size="icon"
                class="text-primary hover:text-primary/80"
                aria-label="Mark fulfilled"
                @click="pendingAction = 'fulfill'"
            >
                <CircleCheck class="size-6" />
            </Button>
        </AppTooltip>

        <AppTooltip content="Mark expired">
            <Button
                variant="ghost"
                size="icon-sm"
                class="text-destructive hover:text-destructive/80"
                aria-label="Mark expired"
                @click="pendingAction = 'expire'"
            >
                <CircleX class="size-6" />
            </Button>
        </AppTooltip>
    </div>

    <ConfirmationDialog
        v-model:open="isOpen"
        :title="activeConfig?.title ?? ''"
        :description="`Confirm ${label}. This cannot be undone.`"
        :action-name="activeConfig?.actionName ?? ''"
        :variant="activeConfig?.variant ?? 'default'"
        :processing="processing"
        @action="submit"
    />
</template>
