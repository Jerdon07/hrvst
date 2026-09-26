<script setup lang="ts">
import { watch } from 'vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'
import { useFlash } from '@/composables/useFlash'

const { flash } = useFlash()

watch(
    () => flash.value,
    (newFlash) => {
        if (!newFlash?.message || newFlash.type === 'pin') return

        if (newFlash.type === 'error') {
            toast.error(newFlash.message)
        } else {
            toast.success(newFlash.message)
        }
    },
    { deep: true, immediate: true },
)
</script>

<template>
    <Toaster
        rich-colors
        position="top-right"
    />
</template>