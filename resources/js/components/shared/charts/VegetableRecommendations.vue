<script setup lang="ts">
import { ChevronDown, ChevronUp, Info, OctagonX, TriangleAlert } from '@lucide/vue'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Item, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle } from '@/components/ui/item';
import type { RecommendationSeverity, VarietyRecommendation } from '@/types/resources/product'

const props = defineProps<{
  recommendations: VarietyRecommendation[]
}>()

const INITIAL_VISIBLE = 3
const expanded = ref(false)

const visible = computed(() =>
  expanded.value
    ? props.recommendations
    : props.recommendations.slice(0, INITIAL_VISIBLE),
)

const hasMore = computed(() => props.recommendations.length > INITIAL_VISIBLE)
const hiddenCount = computed(() => props.recommendations.length - INITIAL_VISIBLE)

interface SeverityConfig {
  icon: typeof OctagonX
  iconClass: string
  containerClass: string
}

function severityConfig(severity: RecommendationSeverity): SeverityConfig {
  switch (severity) {
    case 'critical':
      return {
        icon: OctagonX,
        iconClass: 'text-red-600 dark:text-red-400',
        containerClass:
          'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-800/50',
      }
    case 'warning':
      return {
        icon: TriangleAlert,
        iconClass: 'text-amber-600 dark:text-amber-400',
        containerClass:
          'bg-amber-50 border-amber-200 dark:bg-amber-950/20 dark:border-amber-800/50',
      }
    default:
      return {
        icon: Info,
        iconClass: 'text-blue-600 dark:text-blue-400',
        containerClass:
          'bg-blue-50 border-blue-200 dark:bg-blue-950/20 dark:border-blue-800/50',
      }
  }
}
</script>

<template>
    <div class="flex flex-col gap-2">

        <div class="flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                Recommendations
            </h2>
            <span class="text-xs text-muted-foreground">
                {{ recommendations.length }} signal{{ recommendations.length !== 1 ? 's' : '' }}
            </span>
        </div>

        <ItemGroup class="flex flex-col gap-2">
            <Item
                v-for="rec in visible"
                :key="rec.type"
                :class="['flex items-start gap-3 rounded-lg border p-3 transition-all', severityConfig(rec.severity).containerClass]"
            >
                <ItemMedia variant="icon">
                    <component
                        :is="severityConfig(rec.severity).icon"
                        :class="['size-4 shrink-0', severityConfig(rec.severity).iconClass]"
                    />
                </ItemMedia>

                <ItemContent>
                    <ItemTitle>{{ rec.title }}</ItemTitle>
                    <ItemDescription class="text-xs">{{ rec.body }}</ItemDescription>
                </ItemContent>
            </Item>
        </ItemGroup>

        <Button
            v-if="hasMore"
            variant="ghost"
            size="sm"
            class="w-fit gap-1.5 text-xs text-muted-foreground px-2"
            @click="expanded = !expanded"
        >
            <component
                :is="expanded ? ChevronUp : ChevronDown"
                class="size-3.5"
            />
            {{ expanded ? 'Show less' : `Show ${hiddenCount} more` }}
        </Button>

    </div>
</template>
