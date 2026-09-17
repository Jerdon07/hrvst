<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { Bell, Lock, ThumbsUp } from '@lucide/vue'
import axios from 'axios'
import { onMounted, onUnmounted, ref } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { ScrollArea } from '@/components/ui/scroll-area'
import { show as billingShow } from '@/routes/billing'
import notifications from '@/routes/notifications'
import watches from '@/routes/watches'
import { Item, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemSeparator, ItemTitle } from '../ui/item'

interface NotificationItem {
    id: string
    kind: 'outlook_alert' | 'schedule_overlap' | 'unknown'
    vegetable_id?: number
    vegetable_name?: string
    quantity_kg?: number
    message: string
    url?: string
    detail_locked: boolean
    read_at: string | null
    created_at: string
}

const items = ref<NotificationItem[]>([])
const unreadCount = ref(0)

// No websocket/broadcast wiring exists in this app yet (no Echo/Reverb in
// package.json). Polling is the honest choice here, not a stopgap — alert
// cadence is weekly, so 60s is already far more "live" than the underlying
// signal needs, while staying well clear of any rate limiting.
const POLL_INTERVAL_MS = 60_000
let pollHandle: ReturnType<typeof setInterval> | null = null

async function load(): Promise<void> {
    const { data } = await axios.get(notifications.index().url)
    items.value = data.notifications
    unreadCount.value = data.unread_count
}

async function markRead(item: NotificationItem): Promise<void> {
    if (item.read_at) return
    await axios.post(`/notifications/${item.id}/read`)
    item.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
}

function handleItemClick(item: NotificationItem): void {
    markRead(item)

    if (item.kind === 'schedule_overlap' && item.url) {
        router.visit(item.url)
    }
}

onMounted(() => {
    load()
    pollHandle = setInterval(load, POLL_INTERVAL_MS)
})

onUnmounted(() => {
    if (pollHandle) clearInterval(pollHandle)
})
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="group relative h-9 w-9 cursor-pointer"
                title="Alerts"
            >
                <span class="sr-only">Alerts</span>
                <Bell class="size-5 opacity-80 group-hover:opacity-100" />
                <Badge
                    v-if="unreadCount"
                    variant="destructive"
                    class="absolute -top-1 -right-1 size-4 justify-center rounded-full p-0 text-[10px] leading-none"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </Badge>
            </Button>
        </PopoverTrigger>

        <PopoverContent
            align="end"
            class="w-80 p-0"
        >
            <Item v-if="!items.length" >
                <ItemMedia>
                    <ThumbsUp class="size-5 text-primary" />
                </ItemMedia>
                <ItemContent>No Alerts Yet.</ItemContent>
            </Item>

            <ScrollArea
                v-else
                class="h-80 overflow-hidden rounded-t-md"
            >
                <ItemGroup>
                    <template
                        v-for="(item, index) in items"
                        :key="item.id"
                    >
                        <Item
                            :variant="item.read_at ? 'default' : 'muted' "
                            class="cursor-pointer hover:bg-accent rounded-none"
                            @click="handleItemClick(item)"
                        >
                            <ItemContent>
                                <ItemTitle>
                                    {{ item.vegetable_name }}
                                    <div
                                        v-if="!item.read_at"
                                        class="bg-destructive size-2 rounded-full"
                                    />
                                </ItemTitle>
                                <ItemDescription class="text-muted-foreground/60">
                                    {{ item.message }}
                                </ItemDescription>

                                <Link
                                    v-if="item.kind === 'outlook_alert' && item.detail_locked"
                                    :href="billingShow().url"
                                    class="flex items-center gap-1 text-xs text-primary"
                                    @click.stop
                                >
                                    <Lock class="size-3" />
                                    See exact timing
                                </Link>
                                <span class="text-xs text-muted-foreground/70 text-right">{{ item.created_at }}</span>
                            </ItemContent>

                        </Item>
                        <ItemSeparator v-if="index !== items.length - 1" />
                    </template>
                </ItemGroup>
            </ScrollArea>

            <ItemSeparator />

            <Link
                :href="watches.index().url"
                class="flex items-center justify-center rounded-b-md py-2.5 text-sm font-medium text-primary hover:bg-accent"
            >
                Check vegetables watches
            </Link>
        </PopoverContent>
    </Popover>
</template>