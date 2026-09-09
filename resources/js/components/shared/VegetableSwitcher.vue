<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { Check, ChevronsUpDown, Search } from '@lucide/vue'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
} from '@/components/ui/combobox'
import adminRoutes from '@/routes/admin'
import vegetables, { options } from '@/routes/vegetables'

interface Props {
    currentVegetableId: number
    currentLabel: string
}

const props = defineProps<Props>()

const isAdmin = computed(() => usePage().props.auth.user.roles.includes('admin'))

const groups = ref<Record<string, { id: number; name: string }[]>>({})
const loaded = ref(false)
const loading = ref(false)

async function ensureLoaded(): Promise<void> {
    if (loaded.value || loading.value) return

    loading.value = true
    try {
        const res = await fetch(options().url, {
            headers: { Accept: 'application/json' },
        })
        groups.value = await res.json()
        loaded.value = true
    } finally {
        loading.value = false
    }
}

function goTo(rawId: unknown): void {
    const id = Number(rawId)
    if (!id || id === props.currentVegetableId) return

    const route = isAdmin.value
        ? adminRoutes.vegetables.show(id)
        : vegetables.show(id)

    router.visit(route.url, { preserveScroll: true })
}
</script>

<template>
    <Combobox
        :model-value="String(currentVegetableId)"
        @update:open="(open) => open && ensureLoaded()"
        @update:model-value="goTo"
    >
        <ComboboxAnchor
            as-child
            class="w-full sm:w-72"
        >
            <ComboboxTrigger as-child>
                <Button
                    variant="outline"
                    role="combobox"
                    class="w-full justify-between font-normal"
                >
                    <span class="truncate">{{ currentLabel }}</span>
                    <ChevronsUpDown class="ml-2 size-4 shrink-0 text-muted-foreground" />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList class="w-(--reka-combobox-anchor-width)">
            <div class="relative">
                <ComboboxInput
                    class="pl-9"
                    placeholder="Search vegetable or variety..."
                />
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <Search class="size-4 text-muted-foreground" />
                </span>
            </div>

            <ComboboxEmpty>{{ loading ? 'Loading…' : 'No vegetable found.' }}</ComboboxEmpty>

            <ComboboxViewport>
                <ComboboxGroup
                    v-for="(items, categoryName) in groups"
                    :key="categoryName"
                    :heading="categoryName"
                >
                    <ComboboxItem
                        v-for="v in items"
                        :key="v.id"
                        :value="String(v.id)"
                    >
                        {{ v.name }}
                        <ComboboxItemIndicator>
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxViewport>
        </ComboboxList>
    </Combobox>
</template>