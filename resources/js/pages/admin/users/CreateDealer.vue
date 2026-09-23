<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { storeDealer } from '@/actions/App/Http/Controllers/Admin/UserController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import { usePinRevealFlash } from '@/composables/usePinRevealFlash'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: admin.dashboard().url },
  { title: 'Dealers', href: admin.dealers.index().url },
  { title: 'Add Dealer' },
]

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
  name: '',
  phone_number: '',
  email: '',
})

// ─── PIN modal ────────────────────────────────────────────────────────────────
// immediate: true — same reasoning as CreateFarmer: the redirect-back lands
// with the pin flash already in props on first render.

const { pinModalOpen, revealedPin, closePinModal } = usePinRevealFlash({
  immediate: true,
  onClose: () => form.reset(),
})

// ─── Submit ───────────────────────────────────────────────────────────────────

function submit() {
  form.post(storeDealer.url(), { forceFormData: true })
}
</script>

<template>
    <Head title="Add Dealer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Add Dealer"
                description="Verify the dealer's physical ID before registering their account."
            />

            <form
                class="space-y-6"
                @submit.prevent="submit"
            >
                <div class="grid sm:grid-cols-3 border rounded p-6 gap-4">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">
                            Full Name
                            <Badge variant="destructive">Required</Badge>
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="..."
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Phone -->
                    <div class="grid gap-2">
                        <Label for="phone_number">
                            Phone Number
                            <Badge variant="destructive">Required</Badge>
                        </Label>
                        <Input
                            id="phone_number"
                            v-model="form.phone_number"
                            type="tel"
                            placeholder="09*********"
                        />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <!-- Email (optional) -->
                    <div class="grid gap-2">
                        <Label for="email">
                            Email
                            <Badge variant="outline">Optional</Badge>
                        </Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="..."
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                </div>
        

                <div class="flex gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        Create Dealer
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>

    <!-- PIN reveal modal -->
    <Dialog
        :open="pinModalOpen"
        @update:open="!$event && closePinModal()"
    >
        <DialogContent
            class="sm:max-w-fit"
            @pointer-down-outside.prevent
            @escape-key-down.prevent
        >
            <DialogHeader class="items-center text-center">
                <DialogTitle>Dealer Created</DialogTitle>
                <DialogDescription>
                    Share this temporary PIN with the dealer in person.
                    It will not be shown again.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-3 py-6">
                <p class="text-sm text-muted-foreground">Temporary PIN</p>
                <p class="font-mono text-5xl sm:text-6xl font-bold tracking-[0.5em]">
                    {{ revealedPin }}
                </p>
                <p class="text-xs text-muted-foreground text-center max-w-[220px]">
                    The dealer will be asked to set a new PIN on their first login.
                </p>
            </div>

            <Button
                class="w-full"
                @click="closePinModal"
            >Done</Button>
        </DialogContent>
    </Dialog>
</template>