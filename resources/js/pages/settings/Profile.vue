<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3'
import { Camera } from '@lucide/vue'
import { computed, ref, useTemplateRef } from 'vue'
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController'
import DeleteUser from '@/components/DeleteUser.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { getInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import { edit } from '@/routes/profile'
import type { BreadcrumbItem } from '@/types'

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Profile settings', href: edit().url }]

const page = usePage()
const user = page.props.auth.user

// ── Avatar ────────────────────────────────────────────────────────────────────

const avatarInput = ref<HTMLInputElement | null>(null)
const avatarPreview = ref<string | null>(user.avatar ?? null)
const avatarForm = useForm({ avatar: null as File | null })

function handleAvatarChange(event: Event): void {
	const file = (event.target as HTMLInputElement).files?.[0]
	if (!file) return

	avatarForm.avatar = file
	avatarPreview.value = URL.createObjectURL(file)

	avatarForm.post(ProfileController.updateAvatar.url(), {
		forceFormData: true,
		onSuccess: () => avatarForm.reset(),
		onError: () => {
			avatarPreview.value = user.avatar ?? null
			avatarForm.reset()
		},
	})
}

// ── Profile info ──────────────────────────────────────────────────────────────
// phone_number doubles as the login identifier (see ChangePinRequest.php /
// FortifyServiceProvider) and this app has no password-reset fallback, so
// changing it is gated behind a password confirmation — same presentation
// as DeleteUser.vue's account-deletion confirmation: a DialogTrigger button
// opening a Dialog whose DialogContent contains a fully self-contained
// <Form>, with the same @error-focuses-password and Cancel-clears-errors
// behavior.
//
// Two separate <Form> instances below, never both mounted at once — a
// dialog's contents are teleported to <body>, so an input rendered inside
// one is not a DOM descendant of an outer form and can't submit with it.
// Splitting one <form> across that boundary silently drops fields; two
// independent forms sidesteps the problem entirely.

const name = ref(user.name)
const email = ref(user.email ?? '')
const phoneNumber = ref(user.phone_number ?? '')

const phoneNumberChanged = computed(
	() => phoneNumber.value !== (page.props.auth.user.phone_number ?? ''),
)

// Global error bag rather than either Form's own v-slot errors, so
// validation messages display consistently regardless of which of the two
// forms actually made the last request.
const errors = computed(() => page.props.errors as Record<string, string>)

function resetPhoneNumber(): void {
	phoneNumber.value = page.props.auth.user.phone_number ?? ''
}

const passwordInput = useTemplateRef('passwordInput')
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-10">

                <!-- Avatar -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Avatar"
                        description="Click your avatar to upload a new photo"
                    />

                    <button
                        type="button"
                        class="group relative size-20 cursor-pointer rounded-full"
                        :disabled="avatarForm.processing"
                        @click="avatarInput?.click()"
                    >
                        <Avatar class="size-20">
                            <AvatarImage
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                :alt="user.name"
                            />
                            <AvatarFallback class="text-xl font-semibold">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                            <Camera class="size-6 text-white" />
                        </div>
                    </button>

                    <input
                        ref="avatarInput"
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        @change="handleAvatarChange"
                    />

                    <InputError :message="avatarForm.errors.avatar" />
                </div>

                <Separator />

                <!-- Profile info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Profile information"
                        description="Update your name, email address, and phone number"
                    />

                    <!-- Phone unchanged: direct submit, no password gate.
                         This branch unmounts the instant phone_number goes
                         dirty, so its Form is never a route to bypassing
                         the dialog below. -->
                    <Form
                        v-if="!phoneNumberChanged"
                        v-slot="{ processing, recentlySuccessful }"
                        v-bind="ProfileController.update()"
                        class="space-y-4"
                        :options="{ preserveScroll: true }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="name"
                                name="name"
                                type="text"
                                autocomplete="name"
                                placeholder="Your full name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">
                                Email
                                <span class="text-muted-foreground font-normal text-xs">(optional)</span>
                            </Label>
                            <Input
                                id="email"
                                v-model="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                placeholder="your@email.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone_number">Phone number</Label>
                            <Input
                                id="phone_number"
                                v-model="phoneNumber"
                                name="phone_number"
                                type="tel"
                                autocomplete="tel"
                                placeholder="09171234567"
                            />
                            <InputError :message="errors.phone_number" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button :disabled="processing">Save</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-muted-foreground"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>

                    <!-- Phone changed: fields stay editable, but "Save"
                         becomes a DialogTrigger — nothing submits until the
                         password-confirmed Form inside the dialog fires. -->
                    <div
                        v-else
                        class="space-y-4"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                v-model="name"
                                type="text"
                                autocomplete="name"
                                placeholder="Your full name"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">
                                Email
                                <span class="text-muted-foreground font-normal text-xs">(optional)</span>
                            </Label>
                            <Input
                                id="email"
                                v-model="email"
                                type="email"
                                autocomplete="email"
                                placeholder="your@email.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone_number">Phone number</Label>
                            <Input
                                id="phone_number"
                                v-model="phoneNumber"
                                type="tel"
                                autocomplete="tel"
                                placeholder="09171234567"
                            />
                            <InputError :message="errors.phone_number" />
                        </div>

                        <Dialog>
                            <DialogTrigger as-child>
                                <Button type="button">Save</Button>
                            </DialogTrigger>

                            <DialogContent>
                                <Form
                                    v-slot="{ errors: dialogErrors, processing, reset, clearErrors }"
                                    v-bind="ProfileController.update()"
                                    :options="{ preserveScroll: true }"
                                    class="space-y-6"
                                    @error="() => passwordInput?.$el?.focus()"
                                >
                                    <input
                                        type="hidden"
                                        name="name"
                                        :value="name"
                                    >
                                    <input
                                        type="hidden"
                                        name="email"
                                        :value="email"
                                    >
                                    <input
                                        type="hidden"
                                        name="phone_number"
                                        :value="phoneNumber"
                                    >

                                    <DialogHeader class="space-y-3">
                                        <DialogTitle>Confirm your password</DialogTitle>
                                        <DialogDescription>
                                            Your phone number is also your login ID. Please enter
                                            your password to confirm this change.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-2">
                                        <Label
                                            for="current_password"
                                            class="sr-only"
                                        >Password</Label>
                                        <Input
                                            id="current_password"
                                            ref="passwordInput"
                                            type="password"
                                            name="current_password"
                                            placeholder="Password"
                                        />
                                        <InputError :message="dialogErrors.current_password" />
                                    </div>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button
                                                variant="secondary"
                                                @click="
                                                    () => {
                                                        clearErrors();
                                                        reset();
                                                        resetPhoneNumber();
                                                    }
                                                "
                                            >
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            :disabled="processing"
                                        >
                                            Confirm change
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <Separator />

                <!-- Delete account -->
                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>