import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AlertError from '@/components/AlertError.vue'

describe('AlertError', () => {
    it('renders every distinct error message', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Network error', 'Timeout'] },
        })

        const items = wrapper.findAll('li')
        expect(items).toHaveLength(2)
        expect(wrapper.text()).toContain('Network error')
        expect(wrapper.text()).toContain('Timeout')
    })

    it('dedupes exact-duplicate error strings — this is the whole reason the component exists', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Failed to fetch QR code', 'Failed to fetch QR code'] },
        })

        expect(wrapper.findAll('li')).toHaveLength(1)
    })

    it('does NOT dedupe messages that merely look similar (no fuzzy matching)', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['Failed to fetch QR code', 'Failed to fetch QR code.'] },
        })

        expect(wrapper.findAll('li')).toHaveLength(2)
    })

    it('preserves first-seen order after dedup (Set insertion order)', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['B', 'A', 'B', 'C'] },
        })

        const texts = wrapper.findAll('li').map((li) => li.text())
        expect(texts).toEqual(['B', 'A', 'C'])
    })

    it('falls back to the default title when none is provided', () => {
        const wrapper = mount(AlertError, { props: { errors: ['x'] } })
        expect(wrapper.text()).toContain('Something went wrong.')
    })

    it('uses a custom title when provided, overriding the default', () => {
        const wrapper = mount(AlertError, {
            props: { errors: ['x'], title: 'Setup failed' },
        })
        expect(wrapper.text()).toContain('Setup failed')
        expect(wrapper.text()).not.toContain('Something went wrong.')
    })

    it('renders no list items for an empty errors array', () => {
        const wrapper = mount(AlertError, { props: { errors: [] } })
        expect(wrapper.findAll('li')).toHaveLength(0)
    })
})