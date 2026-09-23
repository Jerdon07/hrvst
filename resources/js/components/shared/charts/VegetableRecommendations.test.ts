import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import VegetableRecommendations from '@/components/shared/charts/VegetableRecommendations.vue'
import type { VarietyRecommendation } from '@/types/resources/product'

function rec(
    overrides: Partial<VarietyRecommendation> = {},
): VarietyRecommendation {
    return {
        severity: 'info',
        type: 'supply_opportunity',
        title: 'Title',
        body: 'Body',
        ...overrides,
    } as VarietyRecommendation
}

describe('VegetableRecommendations', () => {
    it('colors a "critical" recommendation red', () => {
        const wrapper = mount(VegetableRecommendations, {
            props: {
                recommendations: [rec({ severity: 'critical', type: 'a' })],
            },
        })
        expect(wrapper.html()).toContain('border-red-200')
    })

    it('colors a "warning" recommendation amber', () => {
        const wrapper = mount(VegetableRecommendations, {
            props: {
                recommendations: [rec({ severity: 'warning', type: 'a' })],
            },
        })
        expect(wrapper.html()).toContain('border-amber-200')
    })

    it('defaults unrecognized/info severities to the blue "info" styling', () => {
        const wrapper = mount(VegetableRecommendations, {
            props: { recommendations: [rec({ severity: 'info', type: 'a' })] },
        })
        expect(wrapper.html()).toContain('border-blue-200')
    })

    it('pluralizes the signal count correctly at 0, 1, and N', () => {
        const zero = mount(VegetableRecommendations, {
            props: { recommendations: [] },
        })
        const one = mount(VegetableRecommendations, {
            props: { recommendations: [rec({ type: 'a' })] },
        })
        const many = mount(VegetableRecommendations, {
            props: {
                recommendations: [rec({ type: 'a' }), rec({ type: 'b' })],
            },
        })

        expect(zero.text()).toContain('0 signals')
        expect(one.text()).toContain('1 signal')
        expect(one.text()).not.toContain('1 signals')
        expect(many.text()).toContain('2 signals')
    })

    it('truncates to 3 visible recommendations by default, delegating the rest to useExpandableList', () => {
        const recommendations = Array.from({ length: 5 }, (_, i) =>
            rec({ type: `t${i}`, title: `Rec ${i}` }),
        )
        const wrapper = mount(VegetableRecommendations, {
            props: { recommendations },
        })

        expect(wrapper.text()).toContain('Rec 0')
        expect(wrapper.text()).toContain('Rec 2')
        expect(wrapper.text()).not.toContain('Rec 3')
        expect(wrapper.text()).toContain('Show 2 more')
    })
})
