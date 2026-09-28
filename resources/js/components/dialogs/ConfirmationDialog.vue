<script setup lang="ts">
import {
	AlertDialog,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'

type ButtonVariant = 'default' | 'destructive'

type Props = {
	title: string
	description: string
	actionName?: string
	variant?: ButtonVariant
	processing?: boolean
}

const props = withDefaults(defineProps<Props>(), {
	actionName: 'Confirm',
	variant: 'default',
	processing: false,
})

const open = defineModel<boolean>('open', { default: false })

const emit = defineEmits<{
	action: []
}>()

function handleAction() {
	emit('action')
}
</script>

<template>
    <AlertDialog v-model:open="open">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>{{ description }}</AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel :disabled="processing">Cancel</AlertDialogCancel>
                <Button
                    :variant="props.variant"
                    :disabled="props.processing"
                    @click="handleAction"
                >
                    <span v-if="processing">Processing…</span>
                    <span v-else>{{ actionName }}</span>
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
