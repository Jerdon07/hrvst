<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Lock, Sparkles } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { show as billingShow } from '@/routes/billing'

withDefaults(
    defineProps<{
        featureLabel?: string | null
        title?: string
        description?: string
    }>(),
    {
        featureLabel: null,
        title: 'Full analytics locked',
        description:
            'Subscribe to see 5-year trends and seasonal forecasts for this vegetable.',
    },
)
</script>

<template>
    <Card class="border-dashed bg-muted/20">
        <CardContent class="flex flex-col items-center gap-3 py-10 text-center">
            <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Lock class="size-5" />
            </div>
            <div class="space-y-1">
                <p class="text-sm font-semibold">{{ title }}</p>
                <p class="max-w-xs text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <Button
                as-child
                size="sm"
                class="mt-1 gap-1.5"
            >
                <Link :href="billingShow().url">
                    <Sparkles class="size-3.5" />
                    {{ featureLabel ?? 'Subscribe' }}
                </Link>
            </Button>
        </CardContent>
    </Card>
</template>
