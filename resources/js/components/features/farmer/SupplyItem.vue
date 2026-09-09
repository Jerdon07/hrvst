<script setup lang="ts">
import {
    CalendarClock,
    ChevronDown,
    MoreVertical,
    SquarePen,
    Trash,
    TriangleAlert,
} from 'lucide-vue-next'
import { fulfill, expire } from '@/actions/App/Http/Controllers/Farmer/Schedule/PostItemController'
import PostActionButtons from '@/components/shared/PostActionButtons.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Item, ItemActions, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemSeparator, ItemTitle } from '@/components/ui/item'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { ScrollArea } from '@/components/ui/scroll-area'
import type { FarmerSupplyDataFixed } from '@/types'
import { edit } from '@/routes/farmer/supplies'
import { Link } from '@inertiajs/vue3'

defineProps<{ supply: FarmerSupplyDataFixed }>()

const emit = defineEmits<{
    edit: [supply: FarmerSupplyDataFixed]
    delete: [supply: FarmerSupplyDataFixed]
}>()
</script>

<template>
    <Item
        variant="outline"
        :class="[
            'group transition-all hover:bg-background hover:shadow-sm',
            supply.needs_action ? 'bg-destructive/5 hover:border-l-4 hover:border-l-destructive' : 'bg-primary/10 hover:border-l-4 hover:border-l-primary',
        ]"
    >
        <ItemMedia
            variant="icon"
            :class="supply.needs_action ? 'bg-destructive/10' : 'bg-primary/10'"
        >
            <TriangleAlert
                v-if="supply.needs_action"
                class="text-destructive"
            />
            <CalendarClock v-else />
        </ItemMedia>

        <ItemContent>
            <ItemTitle class="flex flex-wrap items-center gap-1.5">
                {{ supply.scheduled_date }}
                <Badge variant="outline">{{ supply.time_slot }}</Badge>
                <Badge
                    v-if="supply.needs_action"
                    variant="destructive"
                >Action needed</Badge>
            </ItemTitle>
            <ItemDescription v-if="supply.post_items?.length">
                {{ supply.post_items.length }} {{ supply.post_items.length === 1 ? 'supply' : 'supplies' }}
            </ItemDescription>
        </ItemContent>

        <ItemActions class="flex items-center gap-1">
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
                    <ScrollArea class="max-h-80 overflow-hidden rounded-t-md">
                        <ItemGroup>
                            <template
                                v-for="(item, index) in supply.post_items"
                                :key="item.id"
                            >
                                <Item size="sm">
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
                                            v-if="supply.needs_action && item.status === 'ongoing'"
                                            :fulfill-url="fulfill(item.id).url"
                                            :expire-url="expire(item.id).url"
                                            :label="item.display_name!"
                                            :only="['needsAction']"
                                        />
                                        <Badge
                                            v-else-if="supply.needs_action"
                                            variant="secondary"
                                            class="shrink-0 capitalize"
                                        >
                                            {{ item.status }}
                                        </Badge>
                                    </ItemActions>
                                </Item>
                                <ItemSeparator v-if="index !== supply.post_items.length - 1" />
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
                        <DropdownMenuItem>
                            <Link :href="edit(supply.id).url">
                                <SquarePen />
                                Edit Supply
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive focus:text-destructive"
                            @click="emit('delete', supply)"
                        >
                            <Trash />
                            Delete Supply
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
        </ItemActions>
    </Item>
</template>
