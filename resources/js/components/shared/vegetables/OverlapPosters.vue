<script setup lang="ts">
import { Ghost } from '@lucide/vue'
import { computed } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import SchedulePosters from '@/components/shared/vegetables/SchedulePosters.vue'
import { Badge } from '@/components/ui/badge'
import users from '@/routes/users'
import type { VegetableOverlapData } from '@/types'

const props = defineProps<{
    overlap?: VegetableOverlapData
}>()

const groups = computed(() => {
    if (!props.overlap) return []

    return [
        {
            key: 'supply',
            label: 'Farmers supplying',
            totalLabel: 'total supplies',
            totalKg: props.overlap.total_supplies_kg,
            posters: props.overlap.supply_posters,
            bgClass: 'bg-green-500/5',
        },
        {
            key: 'demand',
            label: 'Dealers demanding',
            totalLabel: 'total demands',
            totalKg: props.overlap.total_demands_kg,
            posters: props.overlap.demand_posters,
            bgClass: 'bg-orange-500/5',
        },
    ].filter((group) => group.posters.length > 0)
})
</script>

<template>
    <EmptyState
        v-if="!groups.length"
        :icon="Ghost"
        title="You're alone..."
    />

    <div
        v-else
        class="space-y-2"
    >
        <div
            v-for="group in groups"
            :key="group.key"
            class="space-y-2"
        >
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted-foreground">{{ group.label }}</p>
                <Badge class="tabular-nums">
                    {{ group.totalKg.toLocaleString() }} kg {{ group.totalLabel }}
                </Badge>
            </div>

            <SchedulePosters
                v-for="poster in group.posters"
                :key="poster.post_item_id"
                :link="users.show(poster.poster_id).url"
                :poster-name="poster.poster_name"
                :poster-phone="poster.poster_phone"
                :quantity-kg="poster.quantity_kg"
                :bg-class="group.bgClass"
            />
        </div>
    </div>
</template>