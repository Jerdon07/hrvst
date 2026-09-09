
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { ArrowRight, ChevronDown, ChevronUp, Vegan } from '@lucide/vue'
import { computed, ref } from 'vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { show as adminShow } from '@/routes/admin/vegetables'
import { show as sharedShow } from '@/routes/vegetables'
import type { TopVegetableData, VegetableStabilityData, VegetableWasteData } from '@/types/resources/product'

type RankedItem = VegetableWasteData | VegetableStabilityData | TopVegetableData

const props = defineProps<{
    title: string
    description: string
    items?: RankedItem[]
    unitLabel?: string
    initialVisible?: number
    guideQuestion: string
    variant?: 'default' | 'destructive'
}>()

const isAdmin = computed(() => usePage().props.auth.user.roles.includes('admin'))
function showRoute(id: number) {
    return isAdmin.value ? adminShow({ vegetable: id }) : sharedShow({ vegetable: id })
}

const expanded = ref(false)

const visible = computed(() => {
    if (!props.items?.length) return []
    if (!props.initialVisible || expanded.value) return props.items
    return props.items.slice(0, props.initialVisible)
})

const hasMore = computed(
    () => !!props.initialVisible && (props.items?.length ?? 0) > props.initialVisible,
)
const hiddenCount = computed(() => (props.items?.length ?? 0) - (props.initialVisible ?? 0))
const maxKg = computed(() => Math.max(...(props.items ?? []).map((i) => i.value_kg), 1))

function barPct(kg: number): string {
    return `${Math.round((kg / maxKg.value) * 100)}%`
}

function maturityLabel(item: RankedItem): string | null {
    if (!('confidence' in item)) return null
    if (item.confidence === 'early') return 'Early data'
    if (item.confidence === 'developing') return 'Building history'
    return null
}

function maturityTooltip(item: RankedItem): string | undefined {
    return 'months_observed' in item
        ? `Based on ${item.months_observed} month${item.months_observed === 1 ? '' : 's'} of data`
        : undefined
}

const barFillClass = computed(() =>
    props.variant === 'destructive' ? 'bg-destructive/70' : 'bg-primary/70',
)
const badgeHoverClass = computed(() =>
    props.variant === 'destructive' ? 'group-hover:bg-destructive' : 'group-hover:bg-primary',
)
</script>

<template>
    <Card>
        <CardHeader>
            <AppTooltip :content="guideQuestion">
                <CardTitle class="text-sm font-semibold cursor-help">{{ title }}</CardTitle>
            </AppTooltip>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div
                v-if="!items?.length"
                class="flex h-24 items-center justify-center text-center text-sm text-muted-foreground"
            >
                No data available for this period.
            </div>

            <template v-else>
                <ol class="flex flex-col gap-1">
                    <li
                        v-for="(item, index) in visible"
                        :key="item.id"
                        class="group"
                    >
                        <Link
                            :href="showRoute(item.id).url"
                            class="group -mx-2 flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-muted"
                        >
                            <span
                                class="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground duration-200"
                                :class="badgeHoverClass"
                            >
                                {{ index + 1 }}
                            </span>

                            <Avatar class="size-8 shrink-0 rounded-md">
                                <AvatarImage
                                    :src="item.image_url"
                                    :alt="item.display_name"
                                />
                                <AvatarFallback class="rounded-md bg-primary/10">
                                    <Vegan class="size-4 text-primary" />
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <p class="truncate text-sm font-medium">{{ item.display_name }}</p>
                                    <Badge
                                        v-if="maturityLabel(item)"
                                        variant="outline"
                                        :title="maturityTooltip(item)"
                                        class="shrink-0 px-1.5 py-0 text-[10px] font-normal text-muted-foreground"
                                    >
                                        {{ maturityLabel(item) }}
                                    </Badge>
                                </div>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full rounded-full"
                                        :class="barFillClass"
                                        :style="{ width: barPct(item.value_kg) }"
                                    />
                                </div>
                            </div>

                            <span class="shrink-0 text-xs font-semibold tabular-nums text-muted-foreground">
                                {{ item.value_kg.toLocaleString() }} {{ unitLabel ?? 'kg' }}
                            </span>

                            <ArrowRight class="hidden size-4 shrink-0 text-muted-foreground transition-transform duration-300 group-hover:translate-x-1 group-hover:text-foreground sm:block" />
                        </Link>
                    </li>
                </ol>

                <Button
                    v-if="hasMore"
                    variant="ghost"
                    size="sm"
                    class="mt-2 w-fit gap-1.5 px-2 text-xs text-muted-foreground"
                    @click="expanded = !expanded"
                >
                    <component
                        :is="expanded ? ChevronUp : ChevronDown"
                        class="size-3.5"
                    />
                    {{ expanded ? 'Show less' : `Show ${hiddenCount} more` }}
                </Button>
            </template>
        </CardContent>
    </Card>
</template>
