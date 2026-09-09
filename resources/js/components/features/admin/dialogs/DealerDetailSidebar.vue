<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3'
import {
    Calendar1,
    CalendarSync,
    Info,
    KeyRound,
    Mail,
    Trash,
} from '@lucide/vue'
import { ref, watch } from 'vue'
import {
    destroy,
    show,
} from '@/actions/App/Http/Controllers/Admin/DealerController'
import { resetPin } from '@/actions/App/Http/Controllers/Admin/UserController'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import EmptyState from '@/components/EmptyState.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemMedia,
    ItemSeparator,
    ItemTitle,
} from '@/components/ui/item'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { useInitials } from '@/composables/useInitials'
import type { DealerResource, FlashMessage } from '@/types'
import PhoneNumberField from '../PhoneNumberField.vue'

const props = defineProps<{
    open: boolean
    dealer: DealerResource | null
    loading: boolean
}>()

defineEmits<{
    close: []
}>()

const { getInitials } = useInitials()

const isDeleteDialogOpen = ref(false)
const pinModalOpen = ref(false)
const revealedPin = ref('')

const resetPinForm = useForm({})
const deleteForm = useForm({})

const page = usePage()
watch(
    () => page.props.flash as FlashMessage | null,
    (flash) => {
        if (flash?.type === 'pin' && flash.pin) {
            revealedPin.value = flash.pin
            pinModalOpen.value = true
        }
    },
)

function handleResetPin() {
    if (!props.dealer) return
    resetPinForm.post(resetPin(props.dealer.user?.id ?? 0).url, {
        preserveScroll: true,
    })
}

const handleDelete = () => {
    if (!props.dealer) return
    deleteForm.delete(destroy(props.dealer.id).url)
}
</script>

<template>
    <DetailSheet
        :open="open"
        title="Dealer Details"
        @update:open="!$event && $emit('close')"
    >
        <!-- Loading Skeleton -->
        <div
            v-if="loading"
            class="space-y-6"
        >
            <div class="flex items-start gap-4">
                <Skeleton class="size-16 shrink-0 rounded-lg" />
                <div class="flex-1 space-y-2">
                    <Skeleton class="h-5 w-40" />
                    <Skeleton class="h-4 w-56" />
                    <Skeleton class="h-4 w-36" />
                    <Skeleton class="h-4 w-32" />
                </div>
            </div>
            <Separator />
            <div class="space-y-2">
                <Skeleton class="h-4 w-24" />
                <Skeleton class="h-4 w-64" />
            </div>
        </div>

        <!-- Dealer Details -->
        <div
            v-else-if="dealer"
            class="space-y-6"
        >
            <Item variant="outline">
                <ItemMedia>
                    <Avatar class="size-16">
                        <AvatarImage
                            v-if="dealer.user?.avatar_url"
                            :src="dealer.user.avatar_url"
                            :alt="dealer.user.name"
                        />
                        <AvatarFallback class="bg-primary/10 text-lg font-semibold text-primary">
                            {{ getInitials(dealer.user?.name) }}
                        </AvatarFallback>
                    </Avatar>
                </ItemMedia>
                <ItemContent>
                    <ItemTitle class="truncate text-base font-semibold">{{
                        dealer.user?.name
                    }}</ItemTitle>
                    <ItemDescription class="flex items-center gap-3">
                        <Calendar1 class="size-4" />
                        Joined {{ dealer.joined_at_human }}
                    </ItemDescription>
                </ItemContent>
            </Item>

            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <Mail class="size-3.5 text-primary" />
                        <span>Email</span>
                    </div>
                    <p class="text-muted-foreground">
                        {{ dealer.user?.email }}
                    </p>
                </div>
                <PhoneNumberField
                    v-if="dealer.user"
                    variant="row"
                    :user-id="dealer.user.id"
                    :phone-number="dealer.user.phone_number"
                />
            </div>

            <Separator />

            <div class="space-y-4">
                <div class="space-y-1">
                    <h3 class="text-lg font-semibold tracking-tight">
                        Today's Demand
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Expecting {{ dealer.demands?.length ?? 'no' }} demand
                    </p>
                </div>

                <ItemGroup v-if="dealer.demands?.length">
                    <template
                        v-for="(item, index) in dealer.demands"
                        :key="item.id"
                    >
                        <ItemSeparator v-if="index !== dealer.demands!.length - 1" />
                        <Item size="sm">
                            <ItemMedia variant="image">
                                <Avatar>
                                    <AvatarImage
                                        v-if="item.vegetable_image_url"
                                        :src="item.vegetable_image_url"
                                        :alt="item.display_name"
                                    />
                                </Avatar>
                            </ItemMedia>

                            <ItemContent>
                                <ItemTitle>{{ item.display_name }}</ItemTitle>
                            </ItemContent>

                            <ItemActions>
                                <Badge>{{ item.quantity_kg }} kg</Badge>
                            </ItemActions>
                        </Item>
                        
                    </template>
                </ItemGroup>

                <EmptyState
                    v-else
                    title="No vegetable demand"
                    :icon="CalendarSync"
                    class="mx-5 h-30"
                />
            </div>
        </div>

        <template #footer>
            <template v-if="loading">
                <Skeleton />
            </template>
            <div
                v-else-if="dealer"
                class="flex justify-end gap-3"
            >
                <Button
                    variant="outline"
                    size="sm"
                    class="cursor-pointer"
                    @click="router.visit(show(dealer.id).url)"
                >
                    <Info />
                    More Details
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="resetPinForm.processing"
                    @click="handleResetPin"
                >
                    <Spinner
                        v-if="resetPinForm.processing"
                        class="size-3.5"
                    />
                    <KeyRound
                        v-else
                        class="size-4"
                    />
                    Reset PIN
                </Button>

                <Button
                    variant="destructive"
                    size="sm"
                    :disabled="deleteForm.processing"
                    @click="isDeleteDialogOpen = true"
                >
                    <Spinner
                        v-if="deleteForm.processing"
                        class="size-3.5"
                    />
                    <Trash
                        v-else
                        class="size-4"
                    />
                    Delete
                </Button>
            </div>
        </template>
    </DetailSheet>

    <ConfirmationDialog
        v-model:open="isDeleteDialogOpen"
        title="Delete Dealer"
        :description="`Are you sure you want to delete ${dealer?.user?.name}?`"
        variant="destructive"
        @action="handleDelete"
    />

    <!-- PIN reveal after reset -->
    <Dialog
        :open="pinModalOpen"
        @update:open="!$event && (pinModalOpen = false)"
    >
        <DialogContent
            class="sm:max-w-fit"
            @pointer-down-outside.prevent
            @escape-key-down.prevent
        >
            <DialogHeader class="items-center text-center">
                <DialogTitle>PIN Reset</DialogTitle>
                <DialogDescription>
                    Share this temporary PIN with the dealer in person. It will
                    not be shown again.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-3 py-6">
                <p class="text-sm text-muted-foreground">Temporary PIN</p>
                <p class="font-mono text-5xl sm:text-6xl font-bold tracking-[0.5em]">
                    {{ revealedPin }}
                </p>
                <p class="max-w-[220px] text-center text-xs text-muted-foreground">
                    The dealer will be asked to set a new PIN on their next
                    login.
                </p>
            </div>

            <Button
                class="w-full"
                @click="pinModalOpen = false"
            >Done</Button>
        </DialogContent>
    </Dialog>
</template>
