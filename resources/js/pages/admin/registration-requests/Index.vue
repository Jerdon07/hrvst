<script setup lang="ts">
import { Deferred, Head, useForm } from '@inertiajs/vue3'
import { FileText, IdCard } from '@lucide/vue'
import {
    CircleCheck,
    Mail,
    MapPin,
    Phone,
    ShoppingBag,
    Sprout,
    UserCheck,
} from '@lucide/vue'
import { ref } from 'vue'
import { approve, reject } from '@/actions/App/Http/Controllers/Admin/RegistrationRequestController'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import { Textarea } from '@/components/ui/textarea'
import { useInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes/admin'
import type { BreadcrumbItem } from '@/types'

interface RegistrationRequestResource {
    id: number
    name: string
    phone_number: string
    email: string | null
    role: 'farmer' | 'dealer'
    created_at: string
    municipality: string | null
    barangay: string | null
    id_type: string | null
    id_type_label: string | null
    id_number: string | null
    document_url: string | null
}

defineProps<{
    requests?: RegistrationRequestResource[]
}>()

const { getInitials } = useInitials()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: dashboard().url },
    { title: 'Registration Requests' },
]

function roleLabel(role: 'farmer' | 'dealer'): string {
    return role === 'farmer' ? 'Farmer' : 'Dealer'
}

function submittedAt(iso: string): string {
    return new Date(iso).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

// ─── Approve ───────────────────────────────────────────────────────────────

const approveForm = useForm({})
const approvingId = ref<number | null>(null)

function handleApprove(request: RegistrationRequestResource): void {
    approvingId.value = request.id
    approveForm.post(approve(request.id).url, {
        preserveScroll: true,
        onFinish: () => (approvingId.value = null),
    })
}

// ─── Reject ────────────────────────────────────────────────────────────────

const rejectDialogOpen = ref(false)
const requestToReject = ref<RegistrationRequestResource | null>(null)
const rejectForm = useForm({ reason: '' })

function openReject(request: RegistrationRequestResource): void {
    requestToReject.value = request
    rejectForm.reset()
    rejectForm.clearErrors()
    rejectDialogOpen.value = true
}

function handleReject(): void {
    if (!requestToReject.value) return

    rejectForm.post(reject(requestToReject.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogOpen.value = false
            requestToReject.value = null
        },
    })
}
</script>

<template>
    <Head title="Registration Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Registration Requests"
                description="Review remote sign-ups before an account is created. Verify the applicant matches a real Trading Post participant before approving."
            />

            <Deferred data="requests">
                <template #fallback>
                    <div class="space-y-3 rounded-lg border p-4">
                        <Skeleton
                            v-for="i in 4"
                            :key="i"
                            class="h-20 w-full"
                        />
                    </div>
                </template>

                <EmptyState
                    v-if="!requests?.length"
                    title="No pending requests"
                    description="New sign-up requests from farmers and dealers will appear here for review."
                    :icon="UserCheck"
                />

                <div
                    v-else
                    class="flex flex-col gap-3"
                >
                    <Item
                        v-for="request in requests"
                        :key="request.id"
                        variant="outline"
                        class="flex-col items-stretch gap-4 sm:flex-row sm:items-center"
                    >
                        <ItemMedia>
                            <Avatar class="size-11">
                                <AvatarFallback class="bg-primary/10 font-semibold text-primary">
                                    {{ getInitials(request.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </ItemMedia>

                        <ItemContent>
                            <ItemTitle class="flex flex-wrap items-center gap-1.5">
                                {{ request.name }}
                                <Badge
                                    variant="outline"
                                    class="gap-1"
                                >
                                    <Sprout
                                        v-if="request.role === 'farmer'"
                                        class="size-3"
                                    />
                                    <ShoppingBag
                                        v-else
                                        class="size-3"
                                    />
                                    {{ roleLabel(request.role) }}
                                </Badge>
                            </ItemTitle>
                            <ItemDescription class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="flex items-center gap-1">
                                    <Phone class="size-3" />
                                    {{ request.phone_number }}
                                </span>
                                <span
                                    v-if="request.email"
                                    class="flex items-center gap-1"
                                >
                                    <Mail class="size-3" />
                                    {{ request.email }}
                                </span>
                                <span
                                    v-if="request.municipality"
                                    class="flex items-center gap-1"
                                >
                                    <MapPin class="size-3" />
                                    {{ request.barangay }}, {{ request.municipality }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <IdCard class="size-3" />
                                    <template v-if="request.id_type">
                                        {{ request.id_type_label }} · {{ request.id_number }}
                                    </template>
                                    <span
                                        v-else
                                        class="text-muted-foreground/60"
                                    >No ID provided</span>
                                </span>

                                <a
                                    v-if="request.document_url"
                                    :href="request.document_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-1 text-primary hover:underline"
                                    @click.stop
                                >
                                    <FileText class="size-3" />
                                    View Document
                                </a>
                                <span
                                    v-else
                                    class="flex items-center gap-1 text-muted-foreground/60"
                                >
                                    <FileText class="size-3" />
                                    No document
                                </span>
                                <span class="text-muted-foreground/70">
                                    Submitted {{ submittedAt(request.created_at) }}
                                </span>
                            </ItemDescription>
                        </ItemContent>

                        <ItemActions class="flex shrink-0 gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="approveForm.processing || rejectForm.processing"
                                @click="openReject(request)"
                            >
                                Reject
                            </Button>
                            <Button
                                size="sm"
                                class="gap-1.5"
                                :disabled="approveForm.processing || rejectForm.processing"
                                @click="handleApprove(request)"
                            >
                                <CircleCheck
                                    v-if="approvingId !== request.id"
                                    class="size-4"
                                />
                                {{ approvingId === request.id ? 'Approving…' : 'Approve' }}
                            </Button>
                        </ItemActions>
                    </Item>
                </div>
            </Deferred>
        </div>
    </AppLayout>

    <!-- Reject reason dialog -->
    <Dialog
        :open="rejectDialogOpen"
        @update:open="rejectDialogOpen = $event"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Reject Request</DialogTitle>
                <DialogDescription>
                    Reject the request from {{ requestToReject?.name }}? They can submit a
                    new request later if this was a mistake.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <label
                    for="reason"
                    class="text-sm font-medium"
                >
                    Reason
                    <span class="font-normal text-muted-foreground">(optional, not shared automatically)</span>
                </label>
                <Textarea
                    id="reason"
                    v-model="rejectForm.reason"
                    placeholder="e.g. Could not confirm this person trades at the post"
                    rows="3"
                />
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    :disabled="rejectForm.processing"
                    @click="rejectDialogOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="rejectForm.processing"
                    @click="handleReject"
                >
                    {{ rejectForm.processing ? 'Rejecting…' : 'Reject Request' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>