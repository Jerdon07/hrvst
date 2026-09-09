<script setup lang="ts" generic="TData">
import {
	type ColumnDef,
	type ExpandedState,
	FlexRender,
	getCoreRowModel,
	getExpandedRowModel,
	getSortedRowModel,
	useVueTable,
} from '@tanstack/vue-table'
import {
	ChevronDown,
	ChevronLeft,
	ChevronRight,
	ChevronsUpDown,
	ChevronUp,
	Search,
} from '@lucide/vue'
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'

interface Paginated<T> {
	data: T[]
		current_page: number
		last_page: number
		per_page: number
		total: number
}

interface Props<TData> {
	data: Paginated<TData>
	columns: ColumnDef<TData>[]
	searchPlaceholder?: string
	emptyMessage?: string
	entityName?: string
	enableSearch?: boolean
	enableExpand?: boolean
	searchQuery?: string
}

const props = withDefaults(defineProps<Props<TData>>(), {
	searchPlaceholder: 'Search...',
	emptyMessage: 'No items found.',
	entityName: 'items',
	enableSearch: true,
	enableExpand: false,
	searchQuery: '',
})

const emit = defineEmits<{
	'page-change': [page: number]
	search: [query: string]
}>()

const localSearchQuery = ref(props.searchQuery)
const expanded = ref<ExpandedState>({})

const table = useVueTable({
	get data() {
		return props.data.data
	},
	columns: props.columns,
	state: {
		get expanded() {
			return expanded.value
		},
		set expanded(value) {
			expanded.value = value
		},
	},
	onExpandedChange: (updaterOrValue) => {
		expanded.value =
			typeof updaterOrValue === 'function' ? updaterOrValue(expanded.value) : updaterOrValue
	},
	getExpandedRowModel: getExpandedRowModel(),
	getCoreRowModel: getCoreRowModel(),
	getSortedRowModel: getSortedRowModel(),
	...(props.enableExpand ? { getExpandedRowModel: getExpandedRowModel() } : {}),
	manualPagination: true,
	manualFiltering: true, // ✅ NEW: Tell TanStack we're handling filtering server-side
})

const hasPrevPage = computed(() => props.data.current_page > 1)
const hasNextPage = computed(() => props.data.current_page < props.data.last_page)

const paginationRange = computed(() => ({
	start: (props.data.current_page - 1) * props.data.per_page + 1,
	end: Math.min(props.data.current_page * props.data.per_page, props.data.total),
}))

function sortIcon(state: string | false) {
	if (state === 'asc') return ChevronUp
	if (state === 'desc') return ChevronDown
	return ChevronsUpDown
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

function handleSearchInput() {
	if (searchTimeout) clearTimeout(searchTimeout)

	searchTimeout = setTimeout(() => {
		emit('search', localSearchQuery.value)
	}, 300)
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- toolbar -->
        <div class="flex items-center justify-between">
            <InputGroup
                v-if="enableSearch"
                class="max-w-xs"
            >
                <InputGroupInput
                    v-model="localSearchQuery"
                    :placeholder="searchPlaceholder"
                    @input="handleSearchInput"
                />
                <InputGroupAddon>
                    <Search />
                </InputGroupAddon>
                <InputGroupAddon align="inline-end">
                    {{ data.total }} results
                </InputGroupAddon>
            </InputGroup>

            <slot name="toolbar-actions" />
        </div>

        <!-- table -->
        <div class="rounded border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            class="px-4 py-3 text-left font-medium text-muted-foreground"
                        >
                            <template v-if="header.column.getCanSort()">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground transition-colors"
                                    @click="header.column.toggleSorting(header.column.getIsSorted() === 'asc')"
                                >
                                    <FlexRender
                                        :render="header.column.columnDef.header"
                                        :props="header.getContext()"
                                    />
                                    <component
                                        :is="sortIcon(header.column.getIsSorted())"
                                        class="size-3.5"
                                    />
                                </button>
                            </template>
                            <template v-else>
                                <template v-if="!header.isPlaceholder">
                                    <FlexRender
                                        :render="header.column.columnDef.header"
                                        :props="header.getContext()"
                                    />
                                </template>
                            </template>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <template
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                    >
                        <!-- main row -->
                        <tr class="border-t hover:bg-primary/10 transition-colors">
                            <td
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                class="px-4 py-3"
                            >
                                <!-- custom cell rendering via slots -->
                                <slot
                                    :name="`cell-${cell.column.id}`"
                                    :row="row.original"
                                    :cell="cell"
                                >
                                    <!-- default cell rendering -->
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </slot>
                            </td>
                        </tr>

                        <!-- expandable row (optional) -->
                        <slot
                            v-if="enableExpand && row.getIsExpanded()"
                            name="expanded-row"
                            :row="row.original"
                            :colspan="columns.length"
                        />
                    </template>

                    <!-- empty state -->
                    <tr v-if="table.getRowModel().rows.length === 0">
                        <td
                            :colspan="columns.length"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            <slot name="empty">
                                {{ emptyMessage }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- pagination -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Showing
                <strong>{{ paginationRange.start }}</strong>–<strong>{{ paginationRange.end }}</strong>
                of <strong>{{ data.total }}</strong> {{ entityName }}
            </span>

            <div class="flex items-center gap-1.5">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasPrevPage"
                    @click="$emit('page-change', data.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <span class="px-2 font-medium text-foreground">
                    {{ data.current_page }} / {{ data.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasNextPage"
                    @click="$emit('page-change', data.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
