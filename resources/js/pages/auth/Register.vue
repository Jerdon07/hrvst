<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { IdCard, KeyRound, MapPin, ShoppingBag, Sprout, User } from '@lucide/vue'
import { computed, ref } from 'vue'
import type { Component } from 'vue'
import FarmLocationPicker from '@/components/forms/FarmLocationPicker.vue'
import FileUpload from '@/components/forms/FileUpload.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Spinner } from '@/components/ui/spinner'
import {
    Stepper,
    StepperIndicator,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import AuthBase from '@/layouts/AuthLayout.vue'
import { store } from '@/routes/register'

interface Municipality {
    id: number
    name: string
    latitude: number
    longitude: number
}

interface Barangay {
    id: number
    name: string
}

const props = defineProps<{
    municipalities: Municipality[]
    status?: string
}>()

type Role = 'farmer' | 'dealer'
type StepKey = 'account' | 'farm' | 'id' | 'pin'

const form = useForm({
    role: 'farmer' as Role,
    name: '',
    phone_number: '',
    email: '',
    municipality_id: '' as string | number,
    barangay_id: '' as string | number,
    latitude: null as number | null,
    longitude: null as number | null,
    id_type: '' as '' | 'drivers_license' | 'philippine_national_id' | 'philippine_passport' | 'voters_id',
    id_number: '',
    supporting_document: null as File | null,
    pin: '',
    pin_confirmation: '',
})

const isFarmer = computed(() => form.role === 'farmer')

// ─── Steps ────────────────────────────────────────────────────────────────────

const steps = computed(() => {
    const list: { key: StepKey; label: string; heading: string; icon: Component }[] = [
        { key: 'account', label: 'Account', heading: 'Your details', icon: User },
    ]
    if (isFarmer.value) {
        list.push({ key: 'farm', label: 'Farm', heading: 'Farm location', icon: MapPin })
    }
    list.push(
        { key: 'id', label: 'ID', heading: 'Valid ID (optional)', icon: IdCard },
        { key: 'pin', label: 'PIN', heading: 'Choose a 6-digit PIN', icon: KeyRound },
    )
    return list.map((s, i) => ({ ...s, step: i + 1 }))
})

const currentStep = ref(1)
const current = computed(() => steps.value[currentStep.value - 1])
const isLastStep = computed(() => currentStep.value === steps.value.length)

const canContinue = computed(() => {
    switch (current.value?.key) {
        case 'account':
            return form.name.trim() !== '' && form.phone_number.trim() !== ''
        case 'farm':
            return (
                !!form.municipality_id &&
                !!form.barangay_id &&
                form.latitude !== null &&
                form.longitude !== null
            )
        default:
            return true
    }
})

function back(): void {
    if (currentStep.value > 1) currentStep.value--
}

// ─── Cascading address (farmer only) ──────────────────────────────────────────

const barangays = ref<Barangay[]>([])
const loadingBarangays = ref(false)

const selectedMunicipality = computed<Municipality | null>(
    () => props.municipalities.find((m) => m.id === Number(form.municipality_id)) ?? null,
)

const mapCenter = computed(() =>
    selectedMunicipality.value
        ? { lat: selectedMunicipality.value.latitude, lng: selectedMunicipality.value.longitude }
        : null,
)

async function onMunicipalityChange(value: string) {
    form.municipality_id = value
    form.barangay_id = ''
    form.latitude = null
    form.longitude = null
    barangays.value = []
    loadingBarangays.value = true

    try {
        const res = await fetch(`/address/barangays?municipality_id=${value}`)
        barangays.value = await res.json()
    } finally {
        loadingBarangays.value = false
    }
}

function selectRole(role: Role): void {
    if (form.role === role) return
    form.role = role
    form.municipality_id = ''
    form.barangay_id = ''
    form.latitude = null
    form.longitude = null
    barangays.value = []
}

// ─── Submit ───────────────────────────────────────────────────────────────────

const fieldStep: Record<string, StepKey> = {
    role: 'account',
    name: 'account',
    phone_number: 'account',
    email: 'account',
    municipality_id: 'farm',
    barangay_id: 'farm',
    latitude: 'farm',
    longitude: 'farm',
    id_type: 'id',
    id_number: 'id',
    supporting_document: 'id',
    pin: 'pin',
    pin_confirmation: 'pin',
}

/** After a failed submit, jump to the earliest step that has an error. */
function goToFirstErrorStep(errors: Record<string, string>): void {
    const failing = new Set(Object.keys(errors).map((f) => fieldStep[f]))
    const target = steps.value.find((s) => failing.has(s.key))
    if (target) currentStep.value = target.step
}

function submit(): void {
    if (!isLastStep.value) {
        if (canContinue.value) currentStep.value++
        return
    }

    form.post(store().url, {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
            barangays.value = []
            currentStep.value = 1
        },
        onError: goToFirstErrorStep,
    })
}
</script>

<template>
    <AuthBase
        title="Request a Trading Post Account"
        description="An admin will review your request and contact you once it's approved."
    >
        <Head title="Request an Account" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form
            class="flex flex-col gap-4"
            @submit.prevent="submit"
        >
            <!-- Progress -->
            <div class="grid gap-2">
                <Stepper
                    v-model="currentStep"
                    class="w-full"
                >
                    <StepperItem
                        v-for="s in steps"
                        :key="s.key"
                        :step="s.step"
                        :disabled="s.step > currentStep"
                        class="relative flex w-full flex-col items-center"
                    >
                        <StepperTrigger>
                            <StepperIndicator>
                                <component
                                    :is="s.icon"
                                    class="size-4"
                                />
                            </StepperIndicator>
                        </StepperTrigger>
                        <StepperSeparator
                            v-if="s.step < steps.length"
                            class="absolute top-5 right-[calc(-50%+20px)] left-[calc(50%+20px)] h-0.5"
                        />
                        <StepperTitle class="mt-1 text-xs">{{ s.label }}</StepperTitle>
                    </StepperItem>
                </Stepper>
            </div>

            <!-- Step: Account -->
            <div
                v-if="current.key === 'account'"
                class="grid gap-6"
            >
                <div class="grid gap-2">
                    <Label>
                        I am a
                        <span
                            class="inline-block size-1.5 rounded-full bg-destructive"
                            aria-hidden="true"
                        />
                    </Label>
                    <ToggleGroup
                        :model-value="form.role"
                        type="single"
                        size="sm"
                        variant="outline"
                        class="grid w-full grid-cols-2 border"
                    >
                        <ToggleGroupItem
                            value="farmer"
                            class="gap-2 py-2"
                            @click="selectRole('farmer')"
                        >
                            <Sprout class="size-4" />
                            Farmer
                        </ToggleGroupItem>
                        <ToggleGroupItem
                            value="dealer"
                            class="gap-2 py-2"
                            @click="selectRole('dealer')"
                        >
                            <ShoppingBag class="size-4" />
                            Dealer
                        </ToggleGroupItem>
                    </ToggleGroup>
                    <InputError :message="form.errors.role" />
                </div>

                <div class="grid gap-2">
                    <Label for="name">
                        Full Name
                        <span
                            class="inline-block size-1.5 rounded-full bg-destructive"
                            aria-hidden="true"
                        />
                    </Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Juan Dela Cruz"
                        autocomplete="name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-6 sm:grid grid-cols-2 gap-2 items-baseline">
                    <div class="grid gap-2">
                        <Label for="phone_number">
                            Phone Number
                            <span
                                class="inline-block size-1.5 rounded-full bg-destructive"
                                aria-hidden="true"
                            />
                        </Label>
                        <Input
                            id="phone_number"
                            v-model="form.phone_number"
                            type="tel"
                            placeholder="09*********"
                            autocomplete="tel"
                        />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">
                            Email
                        </Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="you@example.com"
                            autocomplete="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                </div>
            </div>

            <!-- Step: Farm (farmer only) -->
            <div
                v-else-if="current.key === 'farm'"
                class="grid gap-6"
            >
                <div class="sm:grid grid-cols-2 gap-2">
                    <div class="grid gap-2">
                        <Label for="municipality_id">
                            Municipality
                            <span
                                class="inline-block size-1.5 rounded-full bg-destructive"
                                aria-hidden="true"
                            />
                        </Label>
                        <Select
                            :model-value="String(form.municipality_id)"
                            @update:model-value="(v) => onMunicipalityChange(String(v ?? ''))"
                        >
                            <SelectTrigger
                                id="municipality_id"
                                class="w-full"
                            >
                                <SelectValue placeholder="Select municipality" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="m in municipalities"
                                    :key="m.id"
                                    :value="String(m.id)"
                                >
                                    {{ m.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.municipality_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="barangay_id">
                            Barangay
                            <span
                                class="inline-block size-1.5 rounded-full bg-destructive"
                                aria-hidden="true"
                            />
                        </Label>
                        <Select
                            :model-value="String(form.barangay_id)"
                            :disabled="!form.municipality_id || loadingBarangays"
                            class="w-full"
                            @update:model-value="(v) => (form.barangay_id = String(v ?? ''))"
                        >
                            <SelectTrigger
                                id="barangay_id"
                                class="w-full"
                            >
                                <SelectValue :placeholder="loadingBarangays ? 'Loading…' : 'Select barangay'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="b in barangays"
                                    :key="b.id"
                                    :value="String(b.id)"
                                >
                                    {{ b.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.barangay_id" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>
                        Pin Your Farm
                        <span
                            class="inline-block size-1.5 rounded-full bg-destructive"
                            aria-hidden="true"
                        />
                    </Label>
                    <FarmLocationPicker
                        :municipality-coords="mapCenter"
                        :model-value="{ lat: form.latitude, lng: form.longitude }"
                        :lat-error="form.errors.latitude"
                        :lng-error="form.errors.longitude"
                        size="sm"
                        @update:model-value="({ lat, lng }) => { form.latitude = lat; form.longitude = lng }"
                    />
                </div>
            </div>

            <!-- Step: Valid ID -->
            <div
                v-else-if="current.key === 'id'"
                class="grid gap-6"
            >
                <p class="text-sm text-muted-foreground">
                    Speeds up admin review, but isn't required to submit a request.
                </p>

                <div class="grid grid-cols-2 space-x-2">
                    <div class="grid gap-2">
                        <Label for="id_type">ID Type</Label>
                        <Select
                            :model-value="form.id_type"
                            @update:model-value="(v) => { form.id_type = (v as typeof form.id_type) ?? ''; form.id_number = '' }"
                        >
                            <SelectTrigger
                                id="id_type"
                                class="w-full line-clamp-1 truncate"
                            >
                                <SelectValue placeholder="Select ID type (optional)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="drivers_license">Driver's License</SelectItem>
                                <SelectItem value="philippine_national_id">Philippine National ID (PhilSys)</SelectItem>
                                <SelectItem value="philippine_passport">Philippine Passport</SelectItem>
                                <SelectItem value="voters_id">Voter's ID</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.id_type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="id_number">ID Number</Label>
                        <Input
                            id="id_number"
                            v-model="form.id_number"
                            type="text"
                            :disabled="!form.id_type"
                            placeholder="Select an ID type first"
                        />
                        <InputError :message="form.errors.id_number" />
                    </div>
                </div>

                <FileUpload
                    v-model="form.supporting_document"
                    :error="form.errors.supporting_document"
                    label="Supporting Document"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    :max-size-mb="5"
                    help-text="Business permit or lot title — JPEG, PNG, WebP, or PDF, up to 5MB"
                />
            </div>

            <!-- Step: PIN -->
            <div
                v-else-if="current.key === 'pin'"
                class="grid gap-6"
            >
                <p class="text-sm text-muted-foreground">
                    You'll use this PIN to log in once your request is approved.
                    Remember it — an admin can't read it back to you.
                </p>

                <div class="grid gap-2">
                    <Label for="pin">PIN</Label>
                    <div class="flex justify-center">
                        <InputOTP
                            id="pin"
                            v-model="form.pin"
                            :maxlength="6"
                            :disabled="form.processing"
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="index in 6"
                                    :key="index"
                                    :index="index - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="form.errors.pin" />
                </div>

                <div class="grid gap-2">
                    <Label for="pin_confirmation">Confirm PIN</Label>
                    <div class="flex justify-center">
                        <InputOTP
                            id="pin_confirmation"
                            v-model="form.pin_confirmation"
                            :maxlength="6"
                            :disabled="form.processing"
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="index in 6"
                                    :key="index"
                                    :index="index - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="form.errors.pin_confirmation" />
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex gap-3">
                <Button
                    v-if="currentStep > 1"
                    type="button"
                    variant="outline"
                    class="flex-1"
                    :disabled="form.processing"
                    @click="back"
                >
                    Back
                </Button>
                <Button
                    type="submit"
                    class="flex-1"
                    :disabled="form.processing || (!isLastStep && !canContinue)"
                >
                    <Spinner v-if="form.processing" />
                    {{ isLastStep ? 'Submit Request' : 'Continue' }}
                </Button>
            </div>

            <p class="text-center text-sm text-muted-foreground">
                Already have an account?
                <Link
                    href="/login"
                    class="font-medium text-foreground underline decoration-neutral-300 underline-offset-4 hover:decoration-current"
                >
                    Sign in
                </Link>
            </p>
        </form>
    </AuthBase>
</template>