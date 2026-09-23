import { usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import type { FlashMessage } from '@/types'

export interface UsePinRevealFlashOptions {
    immediate?: boolean
    onClose?: () => void
}

export function usePinRevealFlash(options: UsePinRevealFlashOptions = {}) {
    const page = usePage()
    const pinModalOpen = ref(false)
    const revealedPin = ref('')

    watch(
        () => page.props.flash as FlashMessage | null,
        (flash) => {
            if (flash?.type === 'pin' && flash.pin) {
                revealedPin.value = flash.pin
                pinModalOpen.value = true
            }
        },
        { immediate: options.immediate ?? false },
    )

    function closePinModal(): void {
        pinModalOpen.value = false
        revealedPin.value = ''
        options.onClose?.()
    }

    return { pinModalOpen, revealedPin, closePinModal }
}
