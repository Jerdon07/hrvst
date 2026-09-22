import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import PostActionButtons from '@/components/shared/PostActionButtons.vue'

const postMock = vi.fn()

vi.mock('@inertiajs/vue3', () => ({
    router: { post: (...args: unknown[]) => postMock(...args) },
}))

const baseProps = {
    fulfillUrl: '/items/1/fulfill',
    expireUrl: '/items/1/expire',
    label: 'Pechay',
}

/**
 * reka-ui's AlertDialog teleports its content to document.body — it is NOT
 * a descendant of the mounted wrapper's element. @vue/test-utils'
 * wrapper.find()/findAll() only walk the mounted vnode tree, so anything
 * inside the dialog is invisible to them once teleported. Query the real
 * DOM instead. This is the #1 reason a naive test here would silently pass
 * while asserting nothing about the actual click.
 */
function dialogButtons(): HTMLButtonElement[] {
    return Array.from(document.body.querySelectorAll('button'))
}

function findByText(text: string): HTMLButtonElement | undefined {
    return dialogButtons().find((b) => b.textContent?.trim() === text)
}

afterEach(() => {
    document.body.innerHTML = ''
})

describe('PostActionButtons', () => {
    it('opens the confirmation dialog in "fulfill" mode when the fulfill icon is clicked', async () => {
        const wrapper = mount(PostActionButtons, {
            props: baseProps,
            attachTo: document.body,
        })

        await wrapper.findAll('button')[0].trigger('click')
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).toContain('Mark as Fulfilled')
        wrapper.unmount()
    })

    it('opens the confirmation dialog in "expire" mode when the expire icon is clicked', async () => {
        const wrapper = mount(PostActionButtons, {
            props: baseProps,
            attachTo: document.body,
        })

        await wrapper.findAll('button')[1].trigger('click')
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).toContain('Mark as Expired')
        wrapper.unmount()
    })

    it('posts to fulfillUrl — not expireUrl — when confirming a fulfill action', async () => {
        postMock.mockClear()
        const wrapper = mount(PostActionButtons, {
            props: baseProps,
            attachTo: document.body,
        })

        await wrapper.findAll('button')[0].trigger('click')
        await wrapper.vm.$nextTick()

        findByText('Fulfill')?.click()
        await wrapper.vm.$nextTick()

        expect(postMock).toHaveBeenCalledWith(
            '/items/1/fulfill',
            {},
            expect.objectContaining({ preserveScroll: true }),
        )
        wrapper.unmount()
    })

    it('posts to expireUrl — not fulfillUrl — when confirming an expire action', async () => {
        postMock.mockClear()
        const wrapper = mount(PostActionButtons, {
            props: baseProps,
            attachTo: document.body,
        })

        await wrapper.findAll('button')[1].trigger('click')
        await wrapper.vm.$nextTick()

        findByText('Expire')?.click()
        await wrapper.vm.$nextTick()

        expect(postMock).toHaveBeenCalledWith(
            '/items/1/expire',
            {},
            expect.objectContaining({ preserveScroll: true }),
        )
        wrapper.unmount()
    })

    it('closes the dialog once the request finishes (onFinish clears pendingAction)', async () => {
        postMock.mockClear()
        postMock.mockImplementation((_url, _data, options) => {
            options.onFinish?.()
        })
        const wrapper = mount(PostActionButtons, {
            props: baseProps,
            attachTo: document.body,
        })

        await wrapper.findAll('button')[0].trigger('click')
        await wrapper.vm.$nextTick()
        expect(document.body.textContent).toContain('Mark as Fulfilled')

        findByText('Fulfill')?.click()
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).not.toContain('Mark as Fulfilled')
        wrapper.unmount()
    })

    it('forwards the `only` prop through to router.post for partial reloads', async () => {
        postMock.mockClear()
        postMock.mockImplementation(() => {})
        const wrapper = mount(PostActionButtons, {
            props: { ...baseProps, only: ['needsAction'] },
            attachTo: document.body,
        })

        await wrapper.findAll('button')[0].trigger('click')
        await wrapper.vm.$nextTick()
        findByText('Fulfill')?.click()
        await wrapper.vm.$nextTick()

        expect(postMock).toHaveBeenCalledWith(
            expect.any(String),
            {},
            expect.objectContaining({ only: ['needsAction'] }),
        )
        wrapper.unmount()
    })
})