import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth'

vi.mock('@/routes/two-factor', () => ({
    qrCode: { url: () => '/user/two-factor-qr-code' },
    secretKey: { url: () => '/user/two-factor-secret-key' },
    recoveryCodes: { url: () => '/user/two-factor-recovery-codes' },
}))

const fetchMock = vi.fn()

beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock)
    fetchMock.mockReset()
    const { clearTwoFactorAuthData } = useTwoFactorAuth()
    clearTwoFactorAuthData()
})

function okResponse(body: unknown) {
    return { ok: true, json: async () => body }
}

function failResponse(status = 500) {
    return { ok: false, status, json: async () => ({}) }
}

describe('fetchQrCode', () => {
    it('sets qrCodeSvg on success', async () => {
        fetchMock.mockResolvedValueOnce(
            okResponse({ svg: '<svg></svg>', url: 'otpauth://x' }),
        )

        const { fetchQrCode, qrCodeSvg } = useTwoFactorAuth()
        await fetchQrCode()

        expect(qrCodeSvg.value).toBe('<svg></svg>')
    })

    it('resets qrCodeSvg to null and records an error on failure', async () => {
        fetchMock.mockResolvedValueOnce(failResponse())

        const { fetchQrCode, qrCodeSvg, errors } = useTwoFactorAuth()
        await fetchQrCode()

        expect(qrCodeSvg.value).toBeNull()
        expect(errors.value).toContain('Failed to fetch QR code')
    })
})

describe('fetchSetupKey', () => {
    it('sets manualSetupKey on success', async () => {
        fetchMock.mockResolvedValueOnce(okResponse({ secretKey: 'ABC123' }))

        const { fetchSetupKey, manualSetupKey } = useTwoFactorAuth()
        await fetchSetupKey()

        expect(manualSetupKey.value).toBe('ABC123')
    })

    it('resets manualSetupKey to null and records an error on failure', async () => {
        fetchMock.mockResolvedValueOnce(failResponse())

        const { fetchSetupKey, manualSetupKey, errors } = useTwoFactorAuth()
        await fetchSetupKey()

        expect(manualSetupKey.value).toBeNull()
        expect(errors.value).toContain('Failed to fetch a setup key')
    })
})

describe('fetchSetupData', () => {
    it('populates both qrCodeSvg and manualSetupKey when both succeed', async () => {
        fetchMock
            .mockResolvedValueOnce(
                okResponse({ svg: '<svg></svg>', url: 'otpauth://x' }),
            )
            .mockResolvedValueOnce(okResponse({ secretKey: 'ABC123' }))

        const { fetchSetupData, qrCodeSvg, manualSetupKey, hasSetupData } =
            useTwoFactorAuth()
        await fetchSetupData()

        expect(qrCodeSvg.value).toBe('<svg></svg>')
        expect(manualSetupKey.value).toBe('ABC123')
        expect(hasSetupData.value).toBe(true)
    })

    // Regression guard: fetchQrCode/fetchSetupKey each catch their own
    // errors internally, so a failure inside them never actually reaches
    // fetchSetupData's own try/catch. The outer catch (which nulls both
    // values) is effectively dead code on the current implementation — this
    // test documents the ACTUAL behavior: a partial failure leaves the
    // succeeding call's data intact rather than wiping everything.
    it('leaves the successful call intact when the other fetch fails (partial failure)', async () => {
        fetchMock
            .mockResolvedValueOnce(failResponse())
            .mockResolvedValueOnce(okResponse({ secretKey: 'ABC123' }))

        const { fetchSetupData, qrCodeSvg, manualSetupKey, hasSetupData } =
            useTwoFactorAuth()
        await fetchSetupData()

        expect(qrCodeSvg.value).toBeNull()
        expect(manualSetupKey.value).toBe('ABC123')
        expect(hasSetupData.value).toBe(false)
    })

    it('clears prior errors before running', async () => {
        fetchMock.mockResolvedValueOnce(failResponse())
        const { fetchQrCode, errors } = useTwoFactorAuth()
        await fetchQrCode()
        expect(errors.value).toHaveLength(1)

        fetchMock
            .mockResolvedValueOnce(okResponse({ svg: '<svg></svg>', url: 'x' }))
            .mockResolvedValueOnce(okResponse({ secretKey: 'ABC123' }))

        const { fetchSetupData } = useTwoFactorAuth()
        await fetchSetupData()

        expect(errors.value).toHaveLength(0)
    })
})

describe('fetchRecoveryCodes', () => {
    it('sets recoveryCodesList on success', async () => {
        fetchMock.mockResolvedValueOnce(okResponse(['code-1', 'code-2']))

        const { fetchRecoveryCodes, recoveryCodesList } = useTwoFactorAuth()
        await fetchRecoveryCodes()

        expect(recoveryCodesList.value).toEqual(['code-1', 'code-2'])
    })

    it('resets recoveryCodesList to empty and records an error on failure', async () => {
        fetchMock.mockResolvedValueOnce(failResponse())

        const { fetchRecoveryCodes, recoveryCodesList, errors } =
            useTwoFactorAuth()
        await fetchRecoveryCodes()

        expect(recoveryCodesList.value).toEqual([])
        expect(errors.value).toContain('Failed to fetch recovery codes')
    })
})

describe('clearSetupData / clearTwoFactorAuthData', () => {
    it('clearSetupData resets qr/key and errors but keeps recovery codes', async () => {
        fetchMock
            .mockResolvedValueOnce(okResponse({ svg: '<svg></svg>', url: 'x' }))
            .mockResolvedValueOnce(okResponse(['code-1']))

        const {
            fetchQrCode,
            fetchRecoveryCodes,
            clearSetupData,
            qrCodeSvg,
            recoveryCodesList,
        } = useTwoFactorAuth()

        await fetchQrCode()
        await fetchRecoveryCodes()
        clearSetupData()

        expect(qrCodeSvg.value).toBeNull()
        expect(recoveryCodesList.value).toEqual(['code-1'])
    })

    it('clearTwoFactorAuthData wipes everything, including recovery codes', async () => {
        fetchMock.mockResolvedValueOnce(okResponse(['code-1']))

        const {
            fetchRecoveryCodes,
            clearTwoFactorAuthData,
            recoveryCodesList,
        } = useTwoFactorAuth()

        await fetchRecoveryCodes()
        clearTwoFactorAuthData()

        expect(recoveryCodesList.value).toEqual([])
    })
})
