<script setup lang="ts">
import { nextTick, watch } from 'vue'
import { type MapMarker, useLeafletMap } from '@/composables/useLeafletMap'

type Props = {
	lat: number
	lng: number
	zoom?: number
	markers?: MapMarker[]
	class?: string | null
}

const props = withDefaults(defineProps<Props>(), {
	zoom: 14,
	markers: () => [],
	class: null,
})

const { container, init, destroy } = useLeafletMap({ zoom: props.zoom })

watch(
	() => [props.lat, props.lng] as const,
	async ([lat, lng]) => {
		await nextTick()
		await init(lat, lng, props.markers)
	},
	{ immediate: true },
)

watch(
	() => props.markers,
	async () => {
		await nextTick()
		await init(props.lat, props.lng, props.markers)
	},
	{ deep: true },
)

defineExpose({ destroy })
</script>

<template>
    <div style="isolation: isolate;">
        <div
            class="h-48 w-full overflow-hidden"
            :class="props.class"
        >
            <div
                ref="container"
                class="h-full w-full"
            />
        </div>
    </div>
</template>
