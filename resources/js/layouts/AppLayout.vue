<script setup lang="ts">
import FlashToaster from '@/components/layout/FlashToaster.vue'
import OnboardingGuide from '@/components/layout/OnboardingGuide.vue'
import AppLayout from '@/layouts/app/AppHeaderLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
  breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
})
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <FlashToaster />
        <OnboardingGuide />
    </AppLayout>
</template>
