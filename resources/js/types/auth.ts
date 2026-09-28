export type AppRole = 'admin' | 'farmer' | 'dealer'

export type User = {
    id: number
    name: string
    phone_number: number
    email: string
    roles: string[]
    avatar?: string
    email_verified_at: string | null
    created_at: string
    updated_at: string
    [key: string]: unknown
}

export type Auth = {
    user: User
}

export type TwoFactorConfigContent = {
    title: string
    description: string
    buttonText: string
}
