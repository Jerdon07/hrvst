<script setup lang="ts">
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import InputError from '@/components/InputError.vue'
import { useMapResizeSync } from '@/composables/useMapResizeSync'

L.Marker.prototype.options.icon = L.icon({
	iconUrl: markerIconUrl,
	shadowUrl: markerShadowUrl,
	iconSize: [25, 41],
	iconAnchor: [12, 41],
	popupAnchor: [1, -34],
	shadowSize: [41, 41],
})

interface Coordinates {
	lat: number | null
	lng: number | null
}

interface MunicipalityCoords {
	lat: number
	lng: number
}

const props = defineProps<{
	modelValue: Coordinates
	municipalityCoords: MunicipalityCoords | null
	latError?: string
	lngError?: string
	size?: 'sm'
}>()

const emit = defineEmits<{
	'update:modelValue': [value: Coordinates]
}>()

const mapContainer = ref<HTMLDivElement | null>(null)

let map: L.Map | null = null
let marker: L.Marker | null = null

const DEFAULT_CENTER: L.LatLngExpression = [16.4023, 120.596]
const DEFAULT_ZOOM = 13

function initMap(): void {
	if (!mapContainer.value) return

	if (map) {
		map.remove()
		map = null
	}
	const el = mapContainer.value as HTMLElement & { _leaflet_id?: number }
	if (el._leaflet_id) {
		delete el._leaflet_id
	}

	const center = props.municipalityCoords
		? ([props.municipalityCoords.lat, props.municipalityCoords.lng] as L.LatLngExpression)
		: DEFAULT_CENTER

	map = L.map(mapContainer.value, { zoomControl: true }).setView(center, DEFAULT_ZOOM)

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors',
		maxZoom: 19,
	}).addTo(map)

	if (props.modelValue.lat !== null && props.modelValue.lng !== null) {
		placeMarker(props.modelValue.lat, props.modelValue.lng)
	}

	map.on('click', (e: L.LeafletMouseEvent) => {
		placeMarker(e.latlng.lat, e.latlng.lng)
		emit('update:modelValue', { lat: e.latlng.lat, lng: e.latlng.lng })
	})
}

function placeMarker(lat: number, lng: number): void {
	if (!map) return

	if (marker) {
		marker.setLatLng([lat, lng])
		return
	}

	marker = L.marker([lat, lng], { draggable: true }).addTo(map)
	marker.on('dragend', () => {
		const pos = marker!.getLatLng()
		emit('update:modelValue', { lat: pos.lat, lng: pos.lng })
	})
}

watch(
	() => props.municipalityCoords,
	(coords) => {
		if (!map || !coords) return
		map.setView([coords.lat, coords.lng], DEFAULT_ZOOM)
		if (marker) {
			marker.remove()
			marker = null
			emit('update:modelValue', { lat: null, lng: null })
		}
	},
)

useMapResizeSync(mapContainer, () => map)

onMounted(() => {
	initMap()
})
onUnmounted(() => {
	map?.remove()
	map = null
	marker = null
})
</script>

<template>
    <div class="grid gap-2">
        <div style="isolation: isolate;">
            <div
                class="h-40 w-full overflow-hidden rounded-md border transition-colors"
                :class="[
					{ 'border-destructive': latError || lngError },
					size === 'sm' ? 'h-40' : (size || 'h-64')]"
            >
                <div
                    ref="mapContainer"
                    class="h-full w-full"
                />
            </div>
        </div>
        <InputError
            v-if="latError"
            :message="latError"
        />
        <InputError
            v-if="lngError"
            :message="lngError"
        />
    </div>
</template>
