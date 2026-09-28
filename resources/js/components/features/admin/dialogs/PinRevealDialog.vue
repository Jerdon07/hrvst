<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

defineProps<{
    open: boolean
    pin: string
    userLabel: string
}>()

defineEmits<{ close: [] }>()
</script>

<template>
    <Dialog
        :open="open"
        @update:open="!$event && $emit('close')"
    >
        <DialogContent
            class="sm:max-w-fit"
            @pointer-down-outside.prevent
            @escape-key-down.prevent
        >
            <DialogHeader class="items-center text-center">
                <DialogTitle>PIN Reset</DialogTitle>
                <DialogDescription>
                    Share this temporary PIN with the {{ userLabel }} in person. It will
                    not be shown again.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-3 py-6">
                <p class="text-sm text-muted-foreground">Temporary PIN</p>
                <p class="font-mono text-5xl sm:text-6xl font-bold tracking-[0.5em]">
                    {{ pin }}
                </p>
                <p class="max-w-[220px] text-center text-xs text-muted-foreground">
                    The {{ userLabel }} will be asked to set a new PIN on their next
                    login.
                </p>
            </div>

            <Button
                class="w-full"
                @click="$emit('close')"
            >Done</Button>
        </DialogContent>
    </Dialog>
</template>