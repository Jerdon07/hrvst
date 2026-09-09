<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { Bell, BellOff, BellRing, TriangleAlert } from '@lucide/vue'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { usePushNotifications } from '@/composables/usePushNotifications'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Push Notifications' }]

const { state, isSubscribed, loading, subscribing, error, subscribe, unsubscribe } =
    usePushNotifications()

const statusLabel = computed(() => {
    if (state.value === 'unsupported') return 'Not supported on this device'
    if (state.value === 'denied') return 'Blocked in browser settings'
    return isSubscribed.value ? 'Enabled' : 'Not enabled'
})

async function handleToggle(): Promise<void> {
    if (isSubscribed.value) {
        await unsubscribe()
    } else {
        await subscribe()
    }
}
</script>

<template>
    <Head title="Push Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Push Notifications"
                    description="Get notified on this device when a schedule is due today."
                />

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-full"
                                :class="isSubscribed ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'"
                            >
                                <BellRing
                                    v-if="isSubscribed"
                                    class="size-5"
                                />
                                <Bell
                                    v-else
                                    class="size-5"
                                />
                            </div>
                            <div>
                                <CardTitle class="text-sm">{{ statusLabel }}</CardTitle>
                                <CardDescription>
                                    Notifications for supplies and demands due today.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-col gap-4">
                        <!-- Unsupported: nothing to offer, explain why -->
                        <div
                            v-if="state === 'unsupported'"
                            class="flex items-start gap-2 rounded-lg border border-dashed bg-muted/30 p-3 text-sm text-muted-foreground"
                        >
                            <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                            <span>
                                Your browser doesn't support push notifications, or this site
                                isn't served over HTTPS. On iPhone, add Hrvst to your home
                                screen first.
                            </span>
                        </div>

                        <!-- Denied: browser permission can't be re-prompted programmatically -->
                        <div
                            v-else-if="state === 'denied'"
                            class="flex items-start gap-2 rounded-lg border border-dashed bg-muted/30 p-3 text-sm text-muted-foreground"
                        >
                            <BellOff class="mt-0.5 size-4 shrink-0" />
                            <span>
                                You previously blocked notifications for this site. Re-enable
                                them from your browser's site settings, then reload this page.
                            </span>
                        </div>

                        <!-- Default / granted: normal toggle -->
                        <template v-else>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    isSubscribed
                                        ? "You'll get a notification on this device the day your schedule is due."
                                        : 'Turn this on to get notified on this device, even if the app is closed.'
                                }}
                            </p>

                            <Button
                                class="w-fit gap-2"
                                :variant="isSubscribed ? 'outline' : 'default'"
                                :disabled="loading || subscribing"
                                @click="handleToggle"
                            >
                                <Spinner
                                    v-if="loading || subscribing"
                                    class="size-4"
                                />
                                <template v-else>
                                    <BellOff
                                        v-if="isSubscribed"
                                        class="size-4"
                                    />
                                    <Bell
                                        v-else
                                        class="size-4"
                                    />
                                </template>
                                {{ isSubscribed ? 'Turn off notifications' : 'Enable notifications' }}
                            </Button>

                            <p
                                v-if="error"
                                class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive"
                            >
                                {{ error }}
                            </p>
                        </template>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>