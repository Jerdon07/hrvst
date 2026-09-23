<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { storeFarmer } from '@/actions/App/Http/Controllers/Admin/UserController'
import FarmLocationPicker from '@/components/forms/FarmLocationPicker.vue'
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Spinner } from '@/components/ui/spinner'
import { usePinRevealFlash } from '@/composables/usePinRevealFlash'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem } from '@/types'

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
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: admin.dashboard().url },
  { title: 'Farmers', href: admin.farmers.index().url },
  { title: 'Add Farmer' },
]

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
  name: '',
  phone_number: '',
  email: '',
  municipality_id: '' as string | number,
  barangay_id: '' as string | number,
  latitude: null as number | null,
  longitude: null as number | null,
})

// ─── Cascading address ────────────────────────────────────────────────────────

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

// ─── PIN modal ────────────────────────────────────────────────────────────────

const { pinModalOpen, revealedPin, closePinModal } = usePinRevealFlash({
  immediate: true,
  onClose: () => form.reset(),
})

// ─── Submit ───────────────────────────────────────────────────────────────────

function submit() {
  form.post(storeFarmer.url(), { forceFormData: true })
}
</script>

<template>
    <Head title="Add Farmer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Add Farmer"
                description="Verify the farmer's physical ID before registering their account."
            />

            <form
                class="space-y-10"
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
        
                <div class="sm:flex gap-10 border rounded p-5 shadow">

                    <!-- Address -->
                    <div class="flex flex-col sm:w-1/3 gap-4">

                        <!-- Municipality -->
                        <div class="grid gap-2">
                            <Label for="municipality_id">
                                Municipality
                                <Badge variant="destructive">Required</Badge>
                            </Label>
                            <Select
                                :model-value="String(form.municipality_id)"
                                required
                                @update:model-value="(v) => onMunicipalityChange(String(v ?? ''))"
                            >
                                <SelectTrigger
                                    id="municipality_id"
                                    class="w-full sm:w-3/4"
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

                        <!-- Barangay -->
                        <div class="grid gap-2">
                            <Label for="barangay_id">
                                Barangay
                                <Badge variant="destructive">Required</Badge>
                            </Label>
                            <Select
                                :model-value="String(form.barangay_id)"
                                :disabled="!form.municipality_id || loadingBarangays"
                                required
                                @update:model-value="(v) => (form.barangay_id = String(v ?? ''))"
                            >
                                <SelectTrigger
                                    id="barangay_id"
                                    class="w-full sm:w-3/4"
                                >
                                    <SelectValue :placeholder="loadingBarangays ? 'Loading…' : 'Select barangay'"/>
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

                    <!-- Location -->
                    <!-- Farm location map -->
                    <div class="grid gap-2 sm:w-1/2">
                        <Label>
                            Farm Location
                            <Badge variant="destructive">Required</Badge>
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

                <div class="flex gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        Create Farmer
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>

    <!-- PIN reveal modal — shown once after successful creation -->
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
                <DialogTitle>Farmer Created</DialogTitle>
                <DialogDescription>
                    Share this temporary PIN with the farmer in person.
                    It will not be shown again.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-3 py-6">
                <p class="text-sm text-muted-foreground">Temporary PIN</p>
                <p class="font-mono text-5xl sm:text-6xl font-bold tracking-[0.5em]">
                    {{ revealedPin }}
                </p>
                <p class="text-xs text-muted-foreground text-center max-w-[220px]">
                    The farmer will be asked to set a new PIN on their first login.
                </p>
            </div>

            <Button
                class="w-full"
                @click="closePinModal"
            >Done</Button>
        </DialogContent>
    </Dialog>
</template>