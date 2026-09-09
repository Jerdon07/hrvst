<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Sprout, ShoppingBag } from '@lucide/vue'
import { computed, ref } from 'vue'
import FarmLocationPicker from '@/components/forms/FarmLocationPicker.vue'
import FileUpload from '@/components/forms/FileUpload.vue'
import InputError from '@/components/InputError.vue'
import AppLogo from '@/components/layout/AppLogo.vue'
import { Badge } from '@/components/ui/badge'
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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
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

    const res = await fetch(`/address/barangays?municipality_id=${value}`)
    barangays.value = await res.json()
    loadingBarangays.value = false
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

function submit(): void {
  form.post(store().url, {
    forceFormData: true,
    onSuccess: () => {
      form.reset()
      barangays.value = []
    },
  })
}
</script>

<template>
    <Head title="Request an Account" />

    <div class="flex min-h-dvh flex-col items-center bg-muted/30 px-4 py-10 sm:py-16">
        <Link
            href="/"
            class="mb-8 flex items-center gap-x-2"
        >
            <AppLogo />
        </Link>

        <div class="w-full max-w-2xl space-y-6">
            <div
                v-if="status"
                class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-400"
            >
                {{ status }}
            </div>

            <div class="rounded-xl border bg-card p-6 shadow-sm sm:p-8">
                <div class="mb-6 space-y-1.5 text-center">
                    <h1 class="text-xl font-semibold tracking-tight">
                        Request a Trading Post Account
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Submit your details below. An admin will review your request and
                        contact you once it's approved — no need to visit the Trading Post
                        to register.
                    </p>
                </div>

                <form
                    class="space-y-8"
                    @submit.prevent="submit"
                >
                    <!-- Role -->
                    <div class="space-y-2">
                        <Label>
                            I am a
                            <Badge
                                variant="destructive"
                                class="text-xs font-normal"
                            >Required</Badge>
                        </Label>
                        <ToggleGroup
                            :model-value="form.role"
                            type="single"
                            variant="outline"
                            class="grid w-full grid-cols-2"
                        >
                            <ToggleGroupItem
                                value="farmer"
                                class="gap-2 py-5"
                                @click="selectRole('farmer')"
                            >
                                <Sprout class="size-4" />
                                Farmer
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="dealer"
                                class="gap-2 py-5"
                                @click="selectRole('dealer')"
                            >
                                <ShoppingBag class="size-4" />
                                Dealer
                            </ToggleGroupItem>
                        </ToggleGroup>
                        <InputError :message="form.errors.role" />
                    </div>

                    <!-- Basic info -->
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="name">
                                Full Name
                                <Badge
                                    variant="destructive"
                                    class="text-xs font-normal"
                                >Required</Badge>
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

                        <div class="grid gap-2">
                            <Label for="phone_number">
                                Phone Number
                                <Badge
                                    variant="destructive"
                                    class="text-xs font-normal"
                                >Required</Badge>
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
                                <Badge
                                    variant="outline"
                                    class="text-xs font-normal"
                                >Optional</Badge>
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

                    <!-- Farm address — farmer only -->
                    <div
                        v-if="isFarmer"
                        class="space-y-4 rounded-lg border p-4"
                    >
                        <p class="text-sm font-medium">Farm Location</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="municipality_id">
                                    Municipality
                                    <Badge
                                        variant="destructive"
                                        class="text-xs font-normal"
                                    >Required</Badge>
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
                                    <Badge
                                        variant="destructive"
                                        class="text-xs font-normal"
                                    >Required</Badge>
                                </Label>
                                <Select
                                    :model-value="String(form.barangay_id)"
                                    :disabled="!form.municipality_id || loadingBarangays"
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
                                <Badge
                                    variant="destructive"
                                    class="text-xs font-normal"
                                >Required</Badge>
                            </Label>
                            <FarmLocationPicker
                                :municipality-coords="mapCenter"
                                :model-value="{ lat: form.latitude, lng: form.longitude }"
                                :lat-error="form.errors.latitude"
                                :lng-error="form.errors.longitude"
                                @update:model-value="({ lat, lng }) => { form.latitude = lat; form.longitude = lng }"
                            />
                        </div>
                    </div>

                    <div class="space-y-4 rounded-lg border p-4">
                        <div class="space-y-1">
                            <p class="text-sm font-medium">Valid ID <Badge
                                variant="outline"
                                class="text-xs font-normal"
                            >Optional</Badge></p>
                            <p class="text-xs text-muted-foreground">
                                Speeds up admin review, but isn't required to submit a request.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="id_type">ID Type</Label>
                                <Select
                                    :model-value="form.id_type"
                                    @update:model-value="(v) => { form.id_type = (v as typeof form.id_type) ?? ''; form.id_number = '' }"
                                >
                                    <SelectTrigger id="id_type"><SelectValue placeholder="Select ID type (optional)" /></SelectTrigger>
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

                    <!-- PIN -->
                    <div class="space-y-4 rounded-lg border p-4">
                        <div class="space-y-1">
                            <p class="text-sm font-medium">Choose a 6-Digit PIN</p>
                            <p class="text-xs text-muted-foreground">
                                You'll use this PIN to log in once your request is approved.
                                Remember it — an admin can't read it back to you.
                            </p>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div class="grid justify-items-center gap-2 sm:justify-items-start">
                                <Label for="pin">PIN</Label>
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
                                <InputError :message="form.errors.pin" />
                            </div>

                            <div class="grid justify-items-center gap-2 sm:justify-items-start">
                                <Label for="pin_confirmation">Confirm PIN</Label>
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
                                <InputError :message="form.errors.pin_confirmation" />
                            </div>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        Submit Request
                    </Button>
                </form>
            </div>

            <p class="text-center text-xs text-muted-foreground">
                Already have an account?
                <Link
                    href="/login"
                    class="font-medium text-foreground underline decoration-neutral-300 underline-offset-4 hover:decoration-current"
                >
                    Sign in
                </Link>
            </p>
        </div>
    </div>
</template>