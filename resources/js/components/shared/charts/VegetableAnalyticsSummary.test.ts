import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import VegetableAnalyticsSummary from '@/components/shared/charts/VegetableAnalyticsSummary.vue'
import type { VarietyAnalytics } from '@/types/resources/product'

function analytics(overrides: Partial<VarietyAnalytics> = {}): VarietyAnalytics {
    return {
        supply_demand_ratio: 1,
        imbalance_band: 'balanced',
        supply_fulfillment_rate: 0.6,
        demand_fulfillment_rate: 0.6,
        supply_volume_mom_pct: 0,
        demand_volume_mom_pct: 0,
        recommendations: [],
        expected_balance: {
            band: 'balanced',
            explanation: 'Roughly matched.',
            computation: {
                source_label: 'last 3 months',
                supply_kg: 100,
                demand_kg: 100,
                diff_pct: 0,
            },
        },
        ...overrides,
    } as VarietyAnalytics
}

describe('VegetableAnalyticsSummary', () => {
    describe('expected-balance band labeling', () => {
        it.each([
            ['oversupply', 'Oversupply'],
            ['undersupply', 'Undersupply'],
            ['balanced', 'Balanced'],
        ] as const)('maps band "%s" to label "%s"', (band, label) => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: { analytics: analytics({ expected_balance: { ...analytics().expected_balance, band } }) },
            })
            expect(wrapper.text()).toContain(label)
        })

        it('falls back to "Balanced" for an unrecognized band value (defensive default)', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: {
                    analytics: analytics({
                        expected_balance: { ...analytics().expected_balance, band: 'something-new' as never },
                    }),
                },
            })
            expect(wrapper.text()).toContain('Balanced')
        })
    })

    describe('fulfillment rate coloring — three-tier thresholds at 0.7 and 0.5', () => {
        it('is green at and above 0.7', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: { analytics: analytics({ supply_fulfillment_rate: 0.7 }) },
            })
            expect(wrapper.html()).toContain('text-green-600')
        })

        it('is amber in the [0.5, 0.7) band', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: { analytics: analytics({ supply_fulfillment_rate: 0.5 }) },
            })
            expect(wrapper.html()).toContain('text-amber-600')
        })

        it('is red below 0.5', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: { analytics: analytics({ supply_fulfillment_rate: 0.49 }) },
            })
            expect(wrapper.html()).toContain('text-red-600')
        })

        it('renders a muted em-dash instead of a percentage when the rate is null', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: { analytics: analytics({ demand_fulfillment_rate: null }) },
            })
            expect(wrapper.text()).toContain('—')
        })

        it('rounds the percentage rather than truncating', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: { analytics: analytics({ supply_fulfillment_rate: 0.666 }) },
            })
            expect(wrapper.text()).toContain('67%')
        })
    })

    describe('expected-balance tooltip computation text', () => {
        it('states demand had zero volume rather than dividing by zero', () => {
            const wrapper = mount(VegetableAnalyticsSummary, {
                props: {
                    analytics: analytics({
                        expected_balance: {
                            band: 'oversupply',
                            explanation: 'x',
                            computation: {
                                source_label: 'last 3 months',
                                supply_kg: 50,
                                demand_kg: 0,
                                diff_pct: null,
                            },
                        },
                    }),
                },
            })
            
            expect(wrapper.html()).not.toContain('NaN')
            expect(wrapper.html()).not.toContain('Infinity')
        })
    })
})