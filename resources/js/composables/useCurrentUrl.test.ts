import { describe, expect, it } from 'vitest'
import { vi } from 'vitest'
import { reactive } from 'vue'

const pageMock = reactive({ url: '/farmer/dashboard', props: {} })

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageMock,
}))

const { useCurrentUrl } = await import('@/composables/useCurrentUrl')

describe('isCurrentUrl', () => {
    it('matches an exact relative pathname', () => {
        const { isCurrentUrl } = useCurrentUrl()
        expect(isCurrentUrl('/farmer/dashboard')).toBe(true)
        expect(isCurrentUrl('/farmer/supplies')).toBe(false)
    })

    it('compares by pathname when given an absolute URL', () => {
        const { isCurrentUrl } = useCurrentUrl()
        expect(isCurrentUrl('https://example.test/farmer/dashboard')).toBe(true)
        expect(isCurrentUrl('https://example.test/farmer/supplies')).toBe(false)
    })

    it('accepts an explicit currentUrl override instead of the page url', () => {
        const { isCurrentUrl } = useCurrentUrl()
        expect(isCurrentUrl('/other', '/other')).toBe(true)
        expect(isCurrentUrl('/other', '/farmer/dashboard')).toBe(false)
    })

    it('returns false rather than throwing on a malformed absolute URL', () => {
        const { isCurrentUrl } = useCurrentUrl()
        expect(() => isCurrentUrl('http://')).not.toThrow()
        expect(isCurrentUrl('http://')).toBe(false)
    })
})

describe('whenCurrentUrl', () => {
    it('returns ifTrue when the url matches', () => {
        const { whenCurrentUrl } = useCurrentUrl()
        expect(whenCurrentUrl('/farmer/dashboard', 'active')).toBe('active')
    })

    it('returns ifFalse when the url does not match', () => {
        const { whenCurrentUrl } = useCurrentUrl()
        expect(whenCurrentUrl('/farmer/supplies', 'active', 'inactive')).toBe(
            'inactive',
        )
    })

    it('defaults ifFalse to null when omitted', () => {
        const { whenCurrentUrl } = useCurrentUrl()
        expect(whenCurrentUrl('/farmer/supplies', 'active')).toBeNull()
    })
})
