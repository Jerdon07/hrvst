<script setup lang="ts">
import { CheckCircle2, CircleDot, Phone, User, XCircle } from '@lucide/vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'

const props = defineProps<{
    posterName: string
    posterPhone: string
    totalKg: number
    status: string
    accentClass: string
    bgClass: string
}>()

function statusIcon() {
    if (props.status === 'fulfilled') return CheckCircle2
    if (props.status === 'expired') return XCircle
    return CircleDot
}

function statusIconClass(): string {
    if (props.status === 'fulfilled') return 'text-green-600 dark:text-green-400'
    if (props.status === 'expired') return 'text-red-600 dark:text-red-400'
    return 'text-amber-600 dark:text-amber-400'
}
</script>

<template>
    <div :class="['flex items-center justify-between gap-2 rounded border px-2.5 py-2 text-xs', bgClass]">
        <div class="flex min-w-0 items-center gap-2">
            <User :class="['size-3.5 shrink-0', accentClass]" />
            <span class="truncate font-medium">{{ posterName }}</span>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <span class="flex items-center gap-1 text-muted-foreground">
                <Phone class="size-3" />
                {{ posterPhone }}
            </span>
            <span class="font-semibold tabular-nums">{{ totalKg.toLocaleString() }} kg</span>
            <AppTooltip :content="`Status: ${status}`">
                <component
                    :is="statusIcon()"
                    :class="['size-4 shrink-0', statusIconClass()]"
                />
            </AppTooltip>
        </div>
    </div>
</template>
