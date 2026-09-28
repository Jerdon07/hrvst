<script setup lang="ts">
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemGroup,
    ItemMedia,
    ItemSeparator,
    ItemTitle,
} from '@/components/ui/item'

defineProps<{
    items: App.Data.PostItem.PostItemLightData[]
}>()
</script>

<template>
    <ItemGroup>
        <template
            v-for="(item, index) in items"
            :key="item.id"
        >
            <ItemSeparator v-if="index !== items.length - 1" />
            <Item size="sm">
                <ItemMedia variant="image">
                    <Avatar>
                        <AvatarImage
                            v-if="item.vegetable_image_url"
                            :src="item.vegetable_image_url"
                            :alt="item.display_name ?? ''"
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
</template>