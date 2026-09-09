<script setup lang="ts">
import { File as FileIcon, Image as ImageIcon, Upload, X } from '@lucide/vue'
import { computed, ref, watch } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

interface Props {
    modelValue: File | null
    existingFileUrl?: string | null
    error?: string
    required?: boolean
    label?: string
    /** Comma-separated mime types, passed straight to the native input's `accept` */
    accept?: string
    maxSizeMb?: number
    helpText?: string
}

const props = withDefaults(defineProps<Props>(), {
    existingFileUrl: null,
    error: '',
    required: false,
    label: 'File',
    accept: 'image/jpeg,image/png,image/webp',
    maxSizeMb: 5,
    helpText: '',
})

const emit = defineEmits<{
    'update:modelValue': [file: File | null]
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const previewUrl = ref<string | null>(props.existingFileUrl)
const selectedFileName = ref<string | null>(null)
const clientError = ref<string>('')

const acceptedMimes = computed(() =>
    props.accept.split(',').map((m) => m.trim()).filter(Boolean),
)

const isImagePreviewable = (fileOrUrl: File | string | null): boolean => {
    if (!fileOrUrl) return false
    if (fileOrUrl instanceof File) return fileOrUrl.type.startsWith('image/')
    // existing URL — best-effort by extension
    return /\.(jpe?g|png|webp|gif)$/i.test(fileOrUrl)
}

watch(
    () => props.modelValue,
    (newFile) => {
        selectedFileName.value = newFile?.name ?? null

        if (newFile && isImagePreviewable(newFile)) {
            const reader = new FileReader()
            reader.onload = (e) => {
                previewUrl.value = e.target?.result as string
            }
            reader.readAsDataURL(newFile)
        } else if (newFile) {
            previewUrl.value = null // non-image file selected — show file chip instead
        } else if (!props.existingFileUrl) {
            previewUrl.value = null
        }
    },
)

watch(
    () => props.existingFileUrl,
    (url) => {
        if (!props.modelValue) {
            previewUrl.value = url
            selectedFileName.value = null
        }
    },
)

const hasPreviewImage = computed(() => previewUrl.value !== null && isImagePreviewable(previewUrl.value))
const hasNonImageFile = computed(() => selectedFileName.value !== null && !hasPreviewImage.value)
const hasAnyFile = computed(() => hasPreviewImage.value || hasNonImageFile.value)

function humanAccept(): string {
    return acceptedMimes.value
        .map((m) => m.split('/')[1]?.toUpperCase() ?? m)
        .join(', ')
}

function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    if (file) validateAndEmit(file)
}

function handleDrop(event: DragEvent) {
    isDragging.value = false
    const file = event.dataTransfer?.files[0]
    if (file) validateAndEmit(file)
}

function validateAndEmit(file: File) {
    clientError.value = ''

    if (!acceptedMimes.value.includes(file.type)) {
        clientError.value = `File must be one of: ${humanAccept()}.`
        return
    }

    const maxBytes = props.maxSizeMb * 1024 * 1024
    if (file.size > maxBytes) {
        clientError.value = `File must be less than ${props.maxSizeMb}MB.`
        return
    }

    emit('update:modelValue', file)
}

function clearFile() {
    previewUrl.value = props.existingFileUrl
    selectedFileName.value = null
    clientError.value = ''
    emit('update:modelValue', null)
    if (fileInput.value) fileInput.value.value = ''
}

function triggerFileInput() {
    fileInput.value?.click()
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label class="flex items-center gap-1.5">
            <ImageIcon class="size-3.5" />
            {{ label }}
            <Badge
                v-if="required"
                variant="destructive"
                class="text-xs font-normal"
            >Required</Badge>
        </Label>

        <!-- Upload Area -->
        <div
            v-if="!hasAnyFile"
            class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-8 transition-colors"
            :class="{
                'border-destructive bg-destructive/5': error || clientError,
                'border-primary bg-primary/5': isDragging && !error && !clientError,
                'border-input hover:border-primary/50': !isDragging && !error && !clientError,
            }"
            @click="triggerFileInput"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
        >
            <Upload class="size-8 text-muted-foreground" />
            <div class="flex flex-col items-center gap-1 text-center">
                <p class="text-sm font-medium">
                    {{ isDragging ? 'Drop file here' : 'Click to upload or drag and drop' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    {{ helpText || `${humanAccept()} up to ${maxSizeMb}MB` }}
                </p>
            </div>
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                class="hidden"
                @change="handleFileSelect"
            />
        </div>

        <!-- Image Preview -->
        <div
            v-else-if="hasPreviewImage"
            class="relative overflow-hidden rounded-lg border"
        >
            <img
                :src="previewUrl!"
                alt="File preview"
                class="h-48 w-full object-cover"
            />
            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity hover:opacity-100">
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    @click="triggerFileInput"
                >Change</Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="clearFile"
                >
                    <X class="mr-1.5 size-4" />Remove
                </Button>
            </div>
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                class="hidden"
                @change="handleFileSelect"
            />
        </div>

        <!-- Non-image File Chip (PDFs, etc.) -->
        <div
            v-else
            class="flex items-center justify-between gap-3 rounded-lg border p-3"
        >
            <div class="flex min-w-0 items-center gap-2">
                <FileIcon class="size-5 shrink-0 text-muted-foreground" />
                <span class="truncate text-sm font-medium">{{ selectedFileName }}</span>
            </div>
            <div class="flex shrink-0 gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="triggerFileInput"
                >Change</Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="clearFile"
                >
                    <X class="size-4" />
                </Button>
            </div>
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                class="hidden"
                @change="handleFileSelect"
            />
        </div>

        <!-- Error Message -->
        <p
            v-if="error || clientError"
            class="text-xs text-destructive"
        >
            {{ error || clientError }}
        </p>
        <p
            v-else
            class="text-xs text-muted-foreground"
        >
            Accepted: {{ humanAccept() }}
        </p>
    </div>
</template>