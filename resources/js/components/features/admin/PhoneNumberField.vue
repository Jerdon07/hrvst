<script setup lang="ts">
import { Phone } from '@lucide/vue'
import EditPhoneDialog from '@/components/features/admin/dialogs/EditPhoneDialog.vue'

withDefaults(
    defineProps<{
        userId: number
        phoneNumber: string
        variant?: 'inline' | 'row'
    }>(),
    { variant: 'inline' },
)
</script>

<template>
    <div
        v-if="variant === 'row'"
        class="flex items-center justify-between text-sm"
    >
        <div class="flex items-center gap-1.5">
            <Phone class="size-3.5 text-primary" />
            <span>Phone Number</span>
        </div>
        <div class="flex items-center gap-1">
            <p class="text-muted-foreground">{{ phoneNumber }}</p>
            <EditPhoneDialog
                :user-id="userId"
                :current-phone="phoneNumber"
            />
        </div>
    </div>

    <div
        v-else
        class="flex items-center gap-2"
    >
        <Phone class="size-4 shrink-0" />
        <span>{{ phoneNumber }}</span>
        <EditPhoneDialog
            :user-id="userId"
            :current-phone="phoneNumber"
        />
    </div>
</template>
