import { useAuthRoles } from '@/composables/useAuthRoles'
import { show as adminShow } from '@/routes/admin/vegetables'
import { show as sharedShow } from '@/routes/vegetables'

export function useVegetableShowRoute() {
    const { isAdmin } = useAuthRoles()

    function vegetableShowRoute(vegetableId: number) {
        return isAdmin.value
            ? adminShow({ vegetable: vegetableId })
            : sharedShow({ vegetable: vegetableId })
    }

    return { vegetableShowRoute }
}
