<script setup lang="ts">
import { Search } from '@lucide/vue'
import type { Component } from 'vue'
import { Button } from './ui/button'
import {
	Empty,
	EmptyContent,
	EmptyDescription,
	EmptyHeader,
	EmptyMedia,
	EmptyTitle,
} from './ui/empty'

type Props = {
	title: string
	description?: string | null
	icon?: Component
	button?: string | null
    iconClass?: string | null
}

withDefaults(defineProps<Props>(), {
	icon: () => Search,
    description: null,
    button: null,
    iconClass: null,
})
</script>

<template>
    <Empty class="border border-dashed">
        <EmptyHeader>
            <EmptyMedia
                variant="icon"
                class="bg-primary/10"
            >
                <component
                    :is="icon"
                    :class="iconClass"
                />
            </EmptyMedia>
            <EmptyTitle>{{ title }}</EmptyTitle>
            <EmptyDescription v-if="description">
                {{ description }}
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent v-if="button">
            <Button size="sm" >
                {{ button }}
            </Button>
        </EmptyContent>
    </Empty>
</template>