<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Vegan } from '@lucide/vue'
import { computed } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { useNetKg } from '@/composables/useNetKg'
import { useVegetableShowRoute } from '@/composables/useVegetableShowRoute'
import { barPercent } from '@/lib/marketBars'
import type { ScheduleType } from '@/lib/scheduleRegistry'

const props = defineProps<{
    row: App.Data.Market.MarketRowData
    side: ScheduleType | null
}>()

const { vegetableShowRoute } = useVegetableShowRoute()

// Admins have no side: use the demand wording (the neutral "excess supply /
// excess demand" phrasing) but keep the text muted instead of judging it.
const { netKgClass, formatNetKg } = useNetKg(() => props.side ?? 'demand')

const max = computed(() => Math.max(props.row.supply_kg, props.row.demand_kg))
const netClass = computed(() => (props.side ? netKgClass(props.row.net_kg) : 'text-muted-foreground'))
</script>

<template>
    <Link
        :href="vegetableShowRoute(row.vegetable_id).url"
        class="group"
    >
        <Card class="h-full gap-4 transition-shadow group-hover:shadow-md">
            <CardHeader class="flex flex-row items-center gap-3">
                <Avatar class="size-10 shrink-0 rounded-md">
                    <AvatarImage
                        :src="row.image_url"
                        :alt="row.display_name"
                    />
                    <AvatarFallback class="rounded-md bg-primary/10">
                        <Vegan class="size-4 text-primary" />
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <CardTitle class="line-clamp-1 text-sm">{{ row.display_name }}</CardTitle>
                    <Badge
                        v-if="row.category"
                        variant="outline"
                        class="mt-1 px-1.5 py-0 text-[10px] font-normal"
                    >
                        {{ row.category }}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent class="flex flex-col gap-3">
                <div class="space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-muted-foreground">Supply · {{ row.supply_posts }}</span>
                        <span class="font-semibold tabular-nums">{{ row.supply_kg.toLocaleString() }} kg</span>
                    </div>
                    <Progress
                        :model-value="barPercent(row.supply_kg, max)"
                        class="h-2 [&>div]:bg-green-500"
                    />
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-muted-foreground">Demand · {{ row.demand_posts }}</span>
                        <span class="font-semibold tabular-nums">{{ row.demand_kg.toLocaleString() }} kg</span>
                    </div>
                    <Progress
                        :model-value="barPercent(row.demand_kg, max)"
                        class="h-2 [&>div]:bg-orange-500"
                    />
                </div>

                <p
                    class="text-xs font-medium tabular-nums"
                    :class="netClass"
                >
                    {{ formatNetKg(row.net_kg) }}
                </p>
            </CardContent>
        </Card>
    </Link>
</template>