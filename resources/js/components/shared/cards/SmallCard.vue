<script setup lang="ts">
import type { Component } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

withDefaults(
	defineProps<{
		title?: string | null
		value?: number | string | null
		valueClass?: string | null
		subtext?: string | null
		icon?: Component
		iconClass?: string
		cardClass?: string | null
		subtextBelow?: boolean
	}>(),
	{
        title: null,
		value: 0,
        valueClass: null,
		subtext: null,
		icon: undefined,
		iconClass: 'size-6',
		cardClass: 'grid-span-1',
		subtextBelow: false,
	},
)
</script>

<template>
    <Card
        class="relative gap-0 py-4 overflow-hidden justify-center hover:shadow-md transition-all"
        :class="cardClass"
    >
        <CardContent class="px-4">
            <CardDescription class="text-xs line-clamp-1">{{ title }}</CardDescription>
        </CardContent>
        <CardHeader class="px-6 flex items-end justify-between">
            <CardTitle
                class="text-xl space-x-1 line-clamp-1"
                :class="valueClass"
            >
                <span class="font-mono">{{ value }}</span>
                <span
                    v-if="!subtextBelow"
                    class="text-muted-foreground font-light text-xs truncate"
                >
                    <slot name="subtext">{{ subtext }}</slot>
                </span>
            </CardTitle>
            <component
                :is="icon"
                v-if="icon"
                :class="iconClass"
            />
        </CardHeader>
        <CardContent
            v-if="subtextBelow"
            class="px-6 pt-0"
        >
            <span class="text-muted-foreground font-light text-xs truncate">
                <slot name="subtext">{{ subtext }}</slot>
            </span>
        </CardContent>

        <div
            class="pointer-events-none absolute -bottom-44 -right-16 size-56 rounded-full opacity-20"
            aria-hidden="true"
            style="
                background: radial-gradient(
                    circle,
                    #2563eb,
                    transparent
                );
            "
        />
    </Card>
</template>
