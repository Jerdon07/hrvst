import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import FileUpload from '@/components/forms/FileUpload.vue'

function selectFile(input: HTMLInputElement, file: File) {
    Object.defineProperty(input, 'files', {
        value: [file],
        configurable: true,
    })
    input.dispatchEvent(new Event('change'))
}

describe('FileUpload', () => {
    it('emits update:modelValue for a file matching the accepted mime list', async () => {
        const wrapper = mount(FileUpload, {
            props: {
                modelValue: null,
                accept: 'image/jpeg,image/png',
                maxSizeMb: 5,
            },
        })

        const file = new File(['x'], 'photo.jpg', { type: 'image/jpeg' })
        selectFile(
            wrapper.find('input[type="file"]').element as HTMLInputElement,
            file,
        )
        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([file])
    })

    it('rejects a file whose mime type is not in the accept list, without emitting', async () => {
        const wrapper = mount(FileUpload, {
            props: {
                modelValue: null,
                accept: 'image/jpeg,image/png',
                maxSizeMb: 5,
            },
        })

        const file = new File(['x'], 'doc.pdf', { type: 'application/pdf' })
        selectFile(
            wrapper.find('input[type="file"]').element as HTMLInputElement,
            file,
        )
        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
        expect(wrapper.text()).toContain('File must be one of')
    })

    it('rejects a file larger than maxSizeMb, without emitting', async () => {
        const wrapper = mount(FileUpload, {
            props: {
                modelValue: null,
                accept: 'image/jpeg',
                maxSizeMb: 1,
            },
        })

        const oversized = new File(
            [new Uint8Array(2 * 1024 * 1024)],
            'big.jpg',
            {
                type: 'image/jpeg',
            },
        )
        selectFile(
            wrapper.find('input[type="file"]').element as HTMLInputElement,
            oversized,
        )
        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
        expect(wrapper.text()).toContain('File must be less than 1MB')
    })

    it('clears the client-side mime error once a valid file is chosen afterwards', async () => {
        const wrapper = mount(FileUpload, {
            props: { modelValue: null, accept: 'image/jpeg', maxSizeMb: 5 },
        })
        const input = wrapper.find('input[type="file"]')
            .element as HTMLInputElement

        selectFile(
            input,
            new File(['x'], 'doc.pdf', { type: 'application/pdf' }),
        )
        await wrapper.vm.$nextTick()
        expect(wrapper.text()).toContain('File must be one of')

        selectFile(input, new File(['x'], 'ok.jpg', { type: 'image/jpeg' }))
        await wrapper.vm.$nextTick()
        expect(wrapper.text()).not.toContain('File must be one of')
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([
            wrapper.emitted('update:modelValue')?.at(-1)?.[0],
        ])
    })

    it('required badge only renders when the required prop is set', () => {
        const withRequired = mount(FileUpload, {
            props: { modelValue: null, required: true },
        })
        const withoutRequired = mount(FileUpload, {
            props: { modelValue: null, required: false },
        })

        expect(withRequired.text()).toContain('Required')
        expect(withoutRequired.text()).not.toContain('Required')
    })
})
