import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import WasteRankingCard from '@/components/shared/charts/WasteRankingCard.vue'

let mockRoles = ['farmer']

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            auth: {
                user: {
                    get roles() {
                        return mockRoles
                    },
                },
            },
        },
    }),
    Link: {
        template: '<a :href="href.url ?? href"><slot /></a>',
        props: ['href'],
    },
}))

vi.mock('@/routes/admin/vegetables', () => ({
    show: (args: { vegetable: number }) => ({
        url: `/admin/vegetables/${args.vegetable}`,
    }),
}))

vi.mock('@/routes/vegetables', () => ({
    show: (args: { vegetable: number }) => ({
        url: `/vegetables/${args.vegetable}`,
    }),
}))

function item(
    id: number,
    value_kg: number,
    extra: Record<string, unknown> = {},
) {
    return {
        id,
        display_name: `Veg ${id}`,
        image_url: '',
        value_kg,
        ...extra,
    }
}

const baseProps = {
    title: 'Top Wasted',
    description: 'desc',
    guideQuestion: 'why?',
}

describe('WasteRankingCard', () => {
    it('shows the empty state when items is undefined', () => {
        const wrapper = mount(WasteRankingCard, { props: { ...baseProps } })
        expect(wrapper.text()).toContain('No data available for this period.')
    })

    it('shows the empty state when items is an empty array', () => {
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items: [] },
        })
        expect(wrapper.text()).toContain('No data available for this period.')
    })

    it('renders every item when initialVisible is not set', () => {
        const items = [item(1, 10), item(2, 20), item(3, 30)]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items },
        })

        expect(wrapper.findAll('li')).toHaveLength(3)
        expect(wrapper.find('button').exists()).toBe(false)
    })

    it('truncates to initialVisible and exposes a "Show N more" toggle', async () => {
        const items = [
            item(1, 10),
            item(2, 20),
            item(3, 30),
            item(4, 40),
            item(5, 50),
        ]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items, initialVisible: 3 },
        })

        expect(wrapper.findAll('li')).toHaveLength(3)
        expect(wrapper.text()).toContain('Show 2 more')

        await wrapper.find('button').trigger('click')

        expect(wrapper.findAll('li')).toHaveLength(5)
        expect(wrapper.text()).toContain('Show less')
    })

    it('does not render a toggle when item count is below or equal to initialVisible', () => {
        const items = [item(1, 10), item(2, 20)]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items, initialVisible: 3 },
        })

        expect(wrapper.find('button').exists()).toBe(false)
    })

    it('sizes bars relative to the max value_kg in the list — largest item is 100%', () => {
        const items = [item(1, 25), item(2, 100), item(3, 50)]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items },
        })

        const widths = wrapper
            .findAll('li')
            .map(
                (li) =>
                    (li.find('div.rounded-full[style]').element as HTMLElement)
                        ?.style.width,
            )

        expect(widths).toEqual(['25%', '100%', '50%'])
    })

    it('guards against a divide-by-zero when every value_kg is 0', () => {
        const items = [item(1, 0), item(2, 0)]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items },
        })

        const widths = wrapper
            .findAll('li')
            .map(
                (li) =>
                    (li.find('div.rounded-full[style]').element as HTMLElement)
                        ?.style.width,
            )

        widths.forEach((w) => expect(w).toBe('0%'))
    })

    it('labels "early" confidence items and shows the badge exactly once per matching item', () => {
        const items = [
            item(1, 10, { confidence: 'early', months_observed: 2 }),
            item(2, 20),
        ]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items },
        })

        const occurrences = wrapper.text().match(/Early data/g) ?? []
        expect(occurrences).toHaveLength(1)
    })

    it('labels "developing" confidence as "Building history", and treats any other value as no label', () => {
        const developing = mount(WasteRankingCard, {
            props: {
                ...baseProps,
                items: [item(1, 10, { confidence: 'developing' })],
            },
        })
        const mature = mount(WasteRankingCard, {
            props: {
                ...baseProps,
                items: [item(1, 10, { confidence: 'mature' })],
            },
        })

        expect(developing.text()).toContain('Building history')
        expect(mature.text()).not.toContain('Building history')
        expect(mature.text()).not.toContain('Early data')
    })

    it('routes to the admin vegetable page for an admin viewer', () => {
        mockRoles = ['admin']
        const items = [item(7, 10)]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items },
        })

        expect(wrapper.find('a').attributes('href')).toBe('/admin/vegetables/7')
    })

    it('routes to the shared vegetable page for a non-admin viewer', () => {
        mockRoles = ['farmer']
        const items = [item(7, 10)]
        const wrapper = mount(WasteRankingCard, {
            props: { ...baseProps, items },
        })

        expect(wrapper.find('a').attributes('href')).toBe('/vegetables/7')
    })
})
