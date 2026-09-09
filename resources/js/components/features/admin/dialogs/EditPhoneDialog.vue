<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Pencil } from '@lucide/vue'
import { ref, watch } from 'vue'
import { updatePhone } from '@/actions/App/Http/Controllers/Admin/UserController'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'

const props = defineProps<{
    userId: number
    currentPhone: string
}>()

const open = ref(false)

const form = useForm({
    phone_number: props.currentPhone,
})

watch(open, (isOpen) => {
    if (!isOpen) return
    form.phone_number = props.currentPhone
    form.clearErrors()
})

function submit(): void {
    form.patch(updatePhone(props.userId).url, {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    })
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                class="size-5 text-muted-foreground hover:text-foreground"
                aria-label="Edit phone number"
            >
                <Pencil class="size-3.5" />
            </Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Edit Phone Number</DialogTitle>
            </DialogHeader>

            <div class="grid gap-2">
                <Label for="phone_number">Phone Number</Label>
                <Input
                    id="phone_number"
                    v-model="form.phone_number"
                    type="tel"
                    placeholder="09*********"
                    :class="{ 'border-destructive': form.errors.phone_number }"
                />
                <InputError :message="form.errors.phone_number" />
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    :disabled="form.processing"
                    @click="open = false"
                >
                    Cancel
                </Button>
                <Button
                    :disabled="form.processing"
                    @click="submit"
                >
                    <Spinner
                        v-if="form.processing"
                        class="size-3.5"
                    />
                    Update
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
