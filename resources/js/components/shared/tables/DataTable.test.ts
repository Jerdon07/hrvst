import type { ColumnDef } from '@tanstack/vue-table'
import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DataTable from '@/components/shared/tables/DataTable.vue'

interface Row {
    id: number
    name: string
}

const columns: ColumnDef<Row>[] = [
    { accessorKey: 'id', header: 'ID' },
    { accessorKey: 'name', header: 'Name' },
]

function paginated(
    overrides: Partial<{
        data: Row[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }> = {},
) {
    return {
        data: [{ id: 1, name: 'A' }],
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 1,
        ...overrides,
    }
}

interface DataTableRowProps {
    data: ReturnType<typeof paginated>
    columns: ColumnDef<Row>[]
    searchPlaceholder?: string
    emptyMessage?: string
    entityName?: string
    enableSearch?: boolean
    searchQuery?: string
}

const Table = DataTable as unknown as new () => { $props: DataTableRowProps }

describe('DataTable', () => {
    describe('paginationRange — the "Showing X–Y of Z" math', () => {
        it('computes the first page range correctly', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({
                        current_page: 1,
                        per_page: 10,
                        total: 25,
                    }),
                    columns,
                },
            })
            expect(wrapper.text()).toContain('Showing')
            expect(wrapper.text()).toContain('1')
            expect(wrapper.text()).toContain('10')
            expect(wrapper.text()).toContain('25')
        })

        it('clamps the end of the range to `total` on the final, partial page', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({
                        current_page: 3,
                        per_page: 10,
                        total: 25,
                    }),
                    columns,
                },
            })
            expect(wrapper.text()).toContain('21')
            expect(wrapper.text()).toContain('25')
            expect(wrapper.text()).not.toContain('30')
        })

        it('shows a 1–1 range for a single-item, single-page result', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({
                        current_page: 1,
                        per_page: 10,
                        total: 1,
                    }),
                    columns,
                },
            })
            const text = wrapper.text()
            expect(text).toMatch(/1.*1.*1/s)
        })
    })

    describe('prev/next button disabling', () => {
        const prevButton = (wrapper: ReturnType<typeof mount>) =>
            wrapper.find('[aria-label="Previous page"]')
        const nextButton = (wrapper: ReturnType<typeof mount>) =>
            wrapper.find('[aria-label="Next page"]')

        it('disables "previous" on the first page', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({ current_page: 1, last_page: 3 }),
                    columns,
                },
            })
            expect(prevButton(wrapper).attributes('disabled')).toBeDefined()
        })

        it('enables "previous" once past the first page', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({ current_page: 2, last_page: 3 }),
                    columns,
                },
            })
            expect(prevButton(wrapper).attributes('disabled')).toBeUndefined()
        })

        it('disables "next" on the last page', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({ current_page: 3, last_page: 3 }),
                    columns,
                },
            })
            expect(nextButton(wrapper).attributes('disabled')).toBeDefined()
        })

        it('disables both prev and next for a single-page result', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({ current_page: 1, last_page: 1 }),
                    columns,
                },
            })
            expect(prevButton(wrapper).attributes('disabled')).toBeDefined()
            expect(nextButton(wrapper).attributes('disabled')).toBeDefined()
        })

        it('emits page-change with current_page - 1 / + 1 respectively', async () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({ current_page: 2, last_page: 3 }),
                    columns,
                },
            })

            await prevButton(wrapper).trigger('click')
            expect(wrapper.emitted('page-change')?.[0]).toEqual([1])

            await nextButton(wrapper).trigger('click')
            expect(wrapper.emitted('page-change')?.[1]).toEqual([3])
        })
    })

    describe('empty state', () => {
        it('shows the default empty message when data is empty', () => {
            const wrapper = mount(Table, {
                props: { data: paginated({ data: [], total: 0 }), columns },
            })
            expect(wrapper.text()).toContain('No items found.')
        })

        it('shows a custom empty message when provided', () => {
            const wrapper = mount(Table, {
                props: {
                    data: paginated({ data: [], total: 0 }),
                    columns,
                    emptyMessage: 'No farmers found',
                },
            })
            expect(wrapper.text()).toContain('No farmers found')
        })
    })

    describe('debounced search', () => {
        beforeEach(() => vi.useFakeTimers())
        afterEach(() => vi.useRealTimers())

        it('does not emit search on every keystroke — only after the 300ms debounce settles', async () => {
            const wrapper = mount(Table, {
                props: { data: paginated(), columns },
            })

            const input = wrapper.find('input')
            await input.setValue('a')
            await input.trigger('input')
            expect(wrapper.emitted('search')).toBeUndefined()

            vi.advanceTimersByTime(299)
            expect(wrapper.emitted('search')).toBeUndefined()

            vi.advanceTimersByTime(1)
            expect(wrapper.emitted('search')?.[0]).toEqual(['a'])
        })

        it('collapses rapid typing into a single emit with the final value', async () => {
            const wrapper = mount(Table, {
                props: { data: paginated(), columns },
            })
            const input = wrapper.find('input')

            await input.setValue('a')
            await input.trigger('input')
            vi.advanceTimersByTime(100)

            await input.setValue('ab')
            await input.trigger('input')
            vi.advanceTimersByTime(100)

            await input.setValue('abc')
            await input.trigger('input')
            vi.advanceTimersByTime(300)

            expect(wrapper.emitted('search')).toHaveLength(1)
            expect(wrapper.emitted('search')?.[0]).toEqual(['abc'])
        })
    })

    describe('search visibility', () => {
        it('hides the search input entirely when enableSearch is false', () => {
            const wrapper = mount(Table, {
                props: { data: paginated(), columns, enableSearch: false },
            })
            expect(wrapper.find('input').exists()).toBe(false)
        })
    })
})
