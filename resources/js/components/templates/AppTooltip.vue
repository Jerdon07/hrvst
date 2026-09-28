<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';


interface Props {
    content?: string
    side?: 'top' | 'right' | 'bottom' | 'left'
    align?: 'start' | 'center' | 'end'
    delayDuration?: number
}

withDefaults(defineProps <Props> (), {
    content: '',
    side: 'top',
    align: 'center',
    delayDuration: 200,
})
</script>

<template>
    <TooltipProvider :delay-duration="delayDuration">
        <Tooltip>
            <TooltipTrigger as-child>
                <slot />
            </TooltipTrigger>

            <TooltipContent
                :side="side"
                :align="align"
            >
                <slot :name="content">
                    <p class="text-sm">{{ content }}</p>
                </slot>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>