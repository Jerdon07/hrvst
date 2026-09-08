<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { CalendarClock, ChevronDown, MoreVertical, SquarePen, Trash, TriangleAlert, Eye } from '@lucide/vue'
import PostActionButtons from '@/components/shared/PostActionButtons.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Item, ItemActions, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemSeparator, ItemTitle } from '@/components/ui/item'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { ScrollArea } from '@/components/ui/scroll-area'
import type { PostDataFixed } from '@/types'

const props = defineProps<{
    post: PostDataFixed
    itemNounSingular: string
    itemNounPlural: string
    entityLabel: string
    showUrl: string
    editUrl: string
    fulfillUrl: (itemId: number) => string
    expireUrl: (itemId: number) => string
}>()

const emit = defineEmits<{ delete: [post: PostDataFixed] }>()
</script>

<template>
    <Item
        variant="outline"
        :class="[
            'group relative overflow-hidden transition-[box-shadow,border] duration-300 hover:shadow-sm',
            post.needs_action
                ? 'hover:border-l-4 hover:border-l-destructive'
                : 'hover:border-l-4 hover:border-l-primary',
        ]"
    >
        <!-- Gradient overlay -->
        <div
            :class="[
                'absolute inset-0 z-0 transition-opacity duration-300 group-hover:opacity-0',
                post.needs_action
                    ? 'bg-linear-to-l from-transparent to-destructive/20'
                    : 'bg-linear-to-l from-transparent to-primary/20',
            ]"
        />
        
        <ItemMedia
            variant="icon"
            :class="post.needs_action ? 'bg-destructive/10' : 'bg-primary/10'"
        >
            <TriangleAlert
                v-if="post.needs_action"
                class="text-destructive"
            />
            <CalendarClock v-else />
        </ItemMedia>

        <ItemContent>
            <ItemTitle class="flex flex-wrap items-center gap-1.5">
                {{ post.scheduled_date }}
                <Badge variant="outline">{{ post.time_slot }}</Badge>
                <Badge
                    v-if="post.needs_action"
                    variant="destructive"
                >Action needed</Badge>
            </ItemTitle>
            <ItemDescription v-if="post.post_items?.length">
                {{ post.post_items.length }} {{ post.post_items.length === 1 ? itemNounSingular : itemNounPlural }}
            </ItemDescription>
        </ItemContent>

        <ItemActions class="z-10 flex items-center gap-1">
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                    >
                        <ChevronDown class="size-4" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    align="end"
                    class="w-80 p-0"
                >
                    <ScrollArea class="max-h-80 overflow-y-scroll rounded-t-md">
                        <ItemGroup>
                            <template
                                v-for="(item, index) in post.post_items"
                                :key="item.id"
                            >
                                <Item>
                                    <ItemMedia variant="image">
                                        <img
                                            :src="item.vegetable_image_url!"
                                            :alt="item.display_name!"
                                        >
                                    </ItemMedia>

                                    <ItemContent>
                                        <ItemTitle>{{ item.display_name }}</ItemTitle>
                                        <ItemDescription>{{ item.quantity_kg.toLocaleString() }} kg</ItemDescription>
                                    </ItemContent>

                                    <ItemActions>
                                        <PostActionButtons
                                            v-if="post.needs_action && item.status === 'ongoing'"
                                            :fulfill-url="fulfillUrl(item.id)"
                                            :expire-url="expireUrl(item.id)"
                                            :label="item.display_name!"
                                            :only="['needsAction']"
                                        />
                                        <Badge
                                            v-else-if="post.needs_action"
                                            variant="secondary"
                                            class="shrink-0 capitalize"
                                        >
                                            {{ item.status }}
                                        </Badge>
                                    </ItemActions>
                                </Item>
                                <ItemSeparator v-if="index !== post.post_items!.length - 1" />
                            </template>
                        </ItemGroup>
                    </ScrollArea>
                </PopoverContent>
            </Popover>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                    >
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                    <DropdownMenuGroup>
                        <DropdownMenuItem as-child>
                            <Link :href="showUrl">
                                <Eye />
                                View {{ entityLabel }}
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <Link :href="editUrl">
                                <SquarePen />
                                Edit {{ entityLabel }}
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive focus:text-destructive"
                            @click="emit('delete', post)"
                        >
                            <Trash />
                            Delete {{ entityLabel }}
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
        </ItemActions>
    </Item>
</template>