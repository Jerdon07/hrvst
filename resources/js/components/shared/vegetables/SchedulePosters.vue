<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { User } from '@lucide/vue';

const props = defineProps<{
    posterName: string
    quantityKg: number
    bgClass: string
}>()
</script>

<template>
    <div :class="['flex items-center justify-between rounded border px-2.5 py-2 text-xs', bgClass]">
        
        <!-- User Information -->
        <div class="flex items-center space-x-2">
            <User class="size-3.5" />
            <span class="font-medium">{{ posterName }}</span>
        </div>

        <!-- Quantity Kg -->
        <Badge
            variant="outline"
            class="tabular-nums"
            >
            {{ quantityKg.toLocaleString() }} kg
        </Badge>
    </div>
</template>