import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

export type PushSupportState = 'unsupported' | 'default' | 'granted' | 'denied'

export function usePushNotifications() {
    const isSupported = 'serviceWorker' in navigator && 'PushManager' in window
    const permission = ref<NotificationPermission>(
        isSupported ? Notification.permission : 'denied',
    )
    const isSubscribed = ref(false)
    const loading = ref(true)
    const subscribing = ref(false)
    const error = ref<string | null>(null)

    const state = computed<PushSupportState>(() => {
        if (!isSupported) return 'unsupported'
        return permission.value as PushSupportState
    })

    function urlBase64ToUint8Array(
        base64String: string,
    ): Uint8Array<ArrayBuffer> {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/')
        const raw = window.atob(base64)
        const bytes = new Uint8Array(raw.length)

        for (let i = 0; i < raw.length; i++) {
            bytes[i] = raw.charCodeAt(i)
        }

        return bytes
    }

    async function refreshSubscriptionState(): Promise<void> {
        if (!isSupported) {
            loading.value = false
            return
        }

        try {
            const registration = await navigator.serviceWorker.ready
            const subscription =
                await registration.pushManager.getSubscription()
            isSubscribed.value = subscription !== null
        } finally {
            loading.value = false
        }
    }

    async function subscribe(): Promise<boolean> {
        if (!isSupported) return false

        subscribing.value = true
        error.value = null
        try {
            const result = await Notification.requestPermission()
            permission.value = result
            if (result !== 'granted') {
                error.value = `Permission not granted (browser returned "${result}").`
                return false
            }

            const registration = await navigator.serviceWorker.ready

            const vapidKey = import.meta.env.VITE_VAPID_PUBLIC_KEY as string
            if (!vapidKey) {
                error.value =
                    'VITE_VAPID_PUBLIC_KEY is missing — check .env and restart `npm run dev`.'
                return false
            }

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            })

            await axios.post('/push-subscriptions', subscription.toJSON())
            isSubscribed.value = true
            return true
        } catch (e) {
            error.value =
                e instanceof Error
                    ? e.message
                    : 'Unknown error while subscribing.'
            console.error('[push] subscribe failed:', e)
            return false
        } finally {
            subscribing.value = false
        }
    }

    async function unsubscribe(): Promise<void> {
        if (!isSupported) return

        subscribing.value = true
        try {
            const registration = await navigator.serviceWorker.ready
            const subscription =
                await registration.pushManager.getSubscription()
            if (!subscription) {
                isSubscribed.value = false
                return
            }

            await axios.delete('/push-subscriptions', {
                data: { endpoint: subscription.endpoint },
            })
            await subscription.unsubscribe()
            isSubscribed.value = false
        } finally {
            subscribing.value = false
        }
    }

    onMounted(refreshSubscriptionState)

    return {
        state,
        permission,
        isSubscribed,
        loading,
        subscribing,
        error,
        subscribe,
        unsubscribe,
    }
}
