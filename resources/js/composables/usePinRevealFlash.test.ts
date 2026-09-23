import { describe, expect, it, vi } from 'vitest'
import { nextTick, reactive } from 'vue'

interface FlashState {
    type: string
    pin?: string
    message?: string
}

const pageMock = reactive({ props: { flash: null as FlashState | null } })

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageMock,
}))

const { usePinRevealFlash } = await import('@/composables/usePinRevealFlash')

describe('usePinRevealFlash', () => {
    describe('default (immediate: false) — matches the sidebar use case', () => {
        it('does not open the modal on setup even if a pin flash already exists', () => {
            pageMock.props.flash = { type: 'pin', pin: '123456' }

            const { pinModalOpen, revealedPin } = usePinRevealFlash()

            expect(pinModalOpen.value).toBe(false)
            expect(revealedPin.value).toBe('')
        })

        it('opens the modal and captures the pin once flash transitions to a pin flash', async () => {
            pageMock.props.flash = null
            const { pinModalOpen, revealedPin } = usePinRevealFlash()

            pageMock.props.flash = { type: 'pin', pin: '654321' }
            await nextTick()

            expect(pinModalOpen.value).toBe(true)
            expect(revealedPin.value).toBe('654321')
        })

        it('ignores flashes of any other type (success/error/warning)', async () => {
            pageMock.props.flash = null
            const { pinModalOpen } = usePinRevealFlash()

            pageMock.props.flash = { type: 'success', message: 'Saved' }
            await nextTick()

            expect(pinModalOpen.value).toBe(false)
        })

        it('ignores a "pin"-typed flash that has no pin value — must not open on a malformed flash', async () => {
            pageMock.props.flash = null
            const { pinModalOpen } = usePinRevealFlash()

            pageMock.props.flash = { type: 'pin', pin: '' }
            await nextTick()

            expect(pinModalOpen.value).toBe(false)
        })
    })

    describe('immediate: true — matches the CreateFarmer/CreateDealer use case', () => {
        it('catches a pin flash that is already present at setup time', () => {
            pageMock.props.flash = { type: 'pin', pin: '111111' }

            const { pinModalOpen, revealedPin } = usePinRevealFlash({ immediate: true })

            expect(pinModalOpen.value).toBe(true)
            expect(revealedPin.value).toBe('111111')
        })

        it('does not open when there is no flash at setup time', () => {
            pageMock.props.flash = null

            const { pinModalOpen } = usePinRevealFlash({ immediate: true })

            expect(pinModalOpen.value).toBe(false)
        })
    })

    describe('closePinModal', () => {
        it('resets both pinModalOpen and revealedPin', async () => {
            pageMock.props.flash = null
            const { pinModalOpen, revealedPin, closePinModal } = usePinRevealFlash()

            pageMock.props.flash = { type: 'pin', pin: '222222' }
            await nextTick()

            closePinModal()

            expect(pinModalOpen.value).toBe(false)
            expect(revealedPin.value).toBe('')
        })

        it('invokes the onClose callback exactly once per call — the form.reset() hook', () => {
            const onClose = vi.fn()
            const { closePinModal } = usePinRevealFlash({ onClose })

            closePinModal()
            closePinModal()

            expect(onClose).toHaveBeenCalledTimes(2)
        })

        it('does not throw when onClose is omitted', () => {
            const { closePinModal } = usePinRevealFlash()
            expect(() => closePinModal()).not.toThrow()
        })
    })
})