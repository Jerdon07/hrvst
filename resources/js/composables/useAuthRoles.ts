import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { AppRole } from '@/types'

export function useAuthRoles() {
    const page = usePage()

    const roles = computed(() => page.props.auth.user?.roles ?? [])

    function hasRole(role: AppRole): boolean {
        return roles.value.includes(role)
    }

    return {
        hasRole,
        isAdmin: computed(() => hasRole('admin')),
        isFarmer: computed(() => hasRole('farmer')),
        isDealer: computed(() => hasRole('dealer')),
    }
}