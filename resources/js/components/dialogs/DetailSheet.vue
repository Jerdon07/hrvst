<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core'
import { computed } from 'vue'
import { ScrollArea } from '@/components/ui/scroll-area'
import {
    Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle,
} from '@/components/ui/sheet'

const props = withDefaults(
    defineProps<{
        open: boolean
        title: string
        description?: string | null
        side?: 'right' | 'left' | 'top' | 'bottom'
    }>(),
    { side: 'right', description: null },
)

defineEmits<{
    'update:open': [value: boolean]
}>()

const isMobile = useMediaQuery('(max-width: 639px)')
const effectiveSide = computed(() =>
    props.side === 'right' && isMobile.value ? 'bottom' : props.side
)
</script>

<template>
    <Sheet
        :open="open"
        @update:open="$emit('update:open', $event)"
    >
        <SheetContent
            :side="effectiveSide"
            class="flex flex-col gap-0 p-0 sm:max-w-[480px] max-h-[85vh] sm:max-h-none"
        >
            <SheetHeader class="border-b px-6 py-4">
                <SheetTitle>{{ title }}</SheetTitle>
                <SheetDescription v-if="description">
                    {{ description }}
                </SheetDescription>
                <SheetDescription
                    v-else
                    class="sr-only"
                >Sheet Description</SheetDescription>
            </SheetHeader>

            <ScrollArea class="flex-1 min-h-0">
                <div class="px-6 py-4">
                    <slot />
                </div>
            </ScrollArea>

            <div
                v-if="$slots.footer"
                class="border-t px-6 py-4"
            >
                <slot name="footer" />
            </div>
        </SheetContent>
    </Sheet>
</template>
