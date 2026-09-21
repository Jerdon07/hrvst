<script setup lang="ts">
import ScheduleForm from '@/components/shared/schedule/ScheduleForm.vue'
import type { ScheduleType } from '@/lib/scheduleRegistry'
import type { VarietyOptionsByVegetable } from '@/types'

defineProps<{
    type: ScheduleType
    varietyOptions?: VarietyOptionsByVegetable
}>()
</script>

<template>
    <ScheduleForm
        :type="type"
        :variety-options="varietyOptions"
    />
</template>