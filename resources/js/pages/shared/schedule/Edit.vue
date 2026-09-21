<script setup lang="ts">
import ScheduleForm from '@/components/shared/schedule/ScheduleForm.vue'
import type { ScheduleType } from '@/lib/scheduleRegistry'
import type { PostDataFixed, VarietyOptionsByVegetable } from '@/types'

defineProps<{
    type: ScheduleType
    schedule: PostDataFixed
    varietyOptions?: VarietyOptionsByVegetable
}>()
</script>

<template>
    <ScheduleForm
        :type="type"
        :schedule="schedule"
        :variety-options="varietyOptions"
    />
</template>