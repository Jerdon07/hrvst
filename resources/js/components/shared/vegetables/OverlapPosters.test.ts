import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import OverlapPosters from '@/components/shared/vegetables/OverlapPosters.vue'
import SchedulePosters from '@/components/shared/vegetables/SchedulePosters.vue'
import type { VegetableOverlapData } from '@/types'

function overlap(
    overrides: Partial<VegetableOverlapData> = {},
): VegetableOverlapData {
    return {
        post_item_id: 1,
        vegetable_id: 1,
        total_supplies_kg: 0,
        total_demands_kg: 0,
        posters: [],
        supply_posters: [],
        demand_posters: [],
        ...overrides,
    }
}

const poster = (id: number, name: string, kg: number) => ({
    post_item_id: id,
    poster_id: id,
    poster_name: name,
    poster_phone: '09171234567',
    quantity_kg: kg,
})

describe('OverlapPosters', () => {
    it('shows the empty state when overlap is undefined', () => {
        const wrapper = mount(OverlapPosters, { props: {} })
        expect(wrapper.text()).toContain("You're alone...")
    })

    it('shows the empty state when overlap has zero posters on both sides', () => {
        const wrapper = mount(OverlapPosters, { props: { overlap: overlap() } })
        expect(wrapper.text()).toContain("You're alone...")
    })

    it('renders only the supply group when only supply posters exist', () => {
        const wrapper = mount(OverlapPosters, {
            props: {
                overlap: overlap({
                    supply_posters: [poster(1, 'Farmer A', 100)],
                    total_supplies_kg: 100,
                }),
            },
        })

        expect(wrapper.text()).toContain('Farmers supplying')
        expect(wrapper.text()).toContain('Farmer A')
        expect(wrapper.text()).not.toContain('Dealers demanding')
        expect(wrapper.text()).not.toContain("You're alone...")
    })

    it('renders only the demand group when only demand posters exist', () => {
        const wrapper = mount(OverlapPosters, {
            props: {
                overlap: overlap({
                    demand_posters: [poster(2, 'Dealer B', 50)],
                    total_demands_kg: 50,
                }),
            },
        })

        expect(wrapper.text()).toContain('Dealers demanding')
        expect(wrapper.text()).toContain('Dealer B')
        expect(wrapper.text()).not.toContain('Farmers supplying')
    })

    it('renders both groups when both exist, each with its own total', () => {
        const wrapper = mount(OverlapPosters, {
            props: {
                overlap: overlap({
                    supply_posters: [poster(1, 'Farmer A', 100)],
                    demand_posters: [poster(2, 'Dealer B', 50)],
                    total_supplies_kg: 100,
                    total_demands_kg: 50,
                }),
            },
        })

        expect(wrapper.text()).toContain('Farmers supplying')
        expect(wrapper.text()).toContain('Dealers demanding')
        expect(wrapper.text()).toContain('100')
        expect(wrapper.text()).toContain('50')
    })

    it('renders one poster row per poster, keyed distinctly by post_item_id', () => {
        const wrapper = mount(OverlapPosters, {
            props: {
                overlap: overlap({
                    supply_posters: [
                        poster(10, 'Farmer A', 40),
                        poster(11, 'Farmer A', 60),
                    ],
                    total_supplies_kg: 100,
                }),
            },
        })

        const posterRows = wrapper.findAllComponents(SchedulePosters)
        expect(posterRows).toHaveLength(2)
    })
})
