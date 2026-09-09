<script setup lang="ts">
import { MapPin, Sprout } from '@lucide/vue'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import { useInitials } from '@/composables/useInitials'
import type { FarmerMarker } from '@/types/resources/marketplace'

defineProps<{
    open: boolean
    farmers: FarmerMarker[]
    barangayName: string
}>()

const emit = defineEmits<{
    close: []
    'view-farmer': [farmerId: number]
}>()

const { getInitials } = useInitials()

function totalSupplies(farmers: FarmerMarker[]): number {
    return farmers.reduce((s, f) => s + f.ongoing_supplies_count, 0)
}
</script>

<template>
    <DetailSheet
        :open="open"
        :title="`Brgy. ${barangayName}`"
        :description="`${farmers.length} farmer${farmers.length !== 1 ? 's' : ''} · ${totalSupplies(farmers)} active supply`"
        @update:open="!$event && $emit('close')"
    >
        <div class="flex flex-col gap-2">
            <Item
                v-for="farmer in farmers"
                :key="farmer.id"
                variant="outline"
                class="cursor-pointer transition-colors hover:bg-accent"
                @click="emit('view-farmer', farmer.id)"
            >
                <ItemMedia>
                    <Avatar>
                        <AvatarFallback class="bg-primary/10 text-sm font-semibold text-primary">
                            {{ getInitials(farmer.farmer_name) }}
                        </AvatarFallback>
                    </Avatar>
                </ItemMedia>
                <ItemContent>
                    <ItemTitle class="text-sm font-medium">
                        {{ farmer.farmer_name }}
                    </ItemTitle>
                    <ItemDescription class="flex items-center gap-1.5 text-xs">
                        <MapPin class="size-3 shrink-0" />
                        {{ farmer.municipality }}
                        <span class="flex items-center gap-1">
                            ·
                            <Sprout class="size-3 shrink-0" />
                            <Badge
                                variant="secondary"
                                class="h-4 px-1 text-xs"
                            >
                                {{ farmer.ongoing_supplies_count }}
                            </Badge>
                        </span>
                    </ItemDescription>
                </ItemContent>
            </Item>
        </div>
    </DetailSheet>
</template>
