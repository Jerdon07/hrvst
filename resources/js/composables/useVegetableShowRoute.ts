import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { show as adminShow } from '@/routes/admin/vegetables'
import { show as sharedShow } from '@/routes/vegetables'

export function useVegetableShowRoute() {
    const isAdmin = computed(() => usePage().props.auth.user.roles.includes('admin'))

    function vegetableShowRoute(vegetableId: number) {
        return isAdmin.value
            ? adminShow({ vegetable: vegetableId })
            : sharedShow({ vegetable: vegetableId })
    }

    return { vegetableShowRoute }
}