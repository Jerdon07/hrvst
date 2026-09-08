<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, User } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';

defineProps<{
    link?: string
    posterName: string
    posterPhone: string
    quantityKg: number
    bgClass: string
}>()
</script>

<template>
    <Item
        size="sm" 
        variant="outline"
        :class="bgClass"
        as-child
    >
        <Link :href="link">
            <ItemMedia variant="icon">
                <User class="size-3.5" />
            </ItemMedia>

            <ItemContent>
                <ItemTitle>{{ posterName }}</ItemTitle>
                <ItemDescription class="text-xs text-muted-foreground">{{ posterPhone }}</ItemDescription>
            </ItemContent>

            <ItemActions>
                <!-- Quantity Kg -->
                <Badge
                    variant="outline"
                    class="tabular-nums"
                >
                    {{ quantityKg.toLocaleString() }} kg
                </Badge>

                <ChevronRight class="size-3.5 text-muted-foreground" />
            </ItemActions>
        </Link>
    </Item>
</template>