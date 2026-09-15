import { dashboard as farmerDashboard } from '@/routes/farmer'
import { dashboard as dealerDashboard } from '@/routes/dealer'
import * as farmerSupplyRoutes from '@/routes/farmer/supplies'
import * as dealerDemandRoutes from '@/routes/dealer/demands'
import {
    fulfill as farmerFulfill,
    expire as farmerExpire,
} from '@/actions/App/Http/Controllers/Farmer/Schedule/PostItemController'
import {
    fulfill as dealerFulfill,
    expire as dealerExpire,
} from '@/actions/App/Http/Controllers/Dealer/Schedule/PostItemController'

export type ScheduleType = 'supply' | 'demand'

type RouteFn = (...args: any[]) => { url: string; method?: string }

interface ScheduleRoutes {
    index: RouteFn
    create: RouteFn
    store: RouteFn
    show: RouteFn
    edit: RouteFn
    update: RouteFn
    destroy: RouteFn
    archived: RouteFn
    fulfill: RouteFn
    expire: RouteFn
}

interface ScheduleConfig {
    routes: ScheduleRoutes
    dashboard: RouteFn
    roleLabel: string
    noun: { singular: string; plural: string }
    entityLabel: string
    copy: {
        createTitle: string
        createDescription: string
        editTitle: string
        editDescription: (date: string) => string
    }
}

export const scheduleRegistry: Record<ScheduleType, ScheduleConfig> = {
    supply: {
        routes: {
            ...(farmerSupplyRoutes as unknown as ScheduleRoutes),
            fulfill: farmerFulfill,
            expire: farmerExpire,
        },
        dashboard: farmerDashboard,
        roleLabel: 'Farmer',
        noun: { singular: 'supply', plural: 'supplies' },
        entityLabel: 'Supply',
        copy: {
            createTitle: 'New Supply Schedule',
            createDescription: 'Post the vegetables you plan to bring, and when.',
            editTitle: 'Edit Supply Schedule',
            editDescription: (date) => `Originally posted for ${date}`,
        },
    },
    demand: {
        routes: {
            ...(dealerDemandRoutes as unknown as ScheduleRoutes),
            fulfill: dealerFulfill,
            expire: dealerExpire,
        },
        dashboard: dealerDashboard,
        roleLabel: 'Dealer',
        noun: { singular: 'demand', plural: 'demands' },
        entityLabel: 'Schedule',
        copy: {
            createTitle: 'New Demand Schedule',
            createDescription: "Post the vegetables you need, and when you'll be there.",
            editTitle: 'Edit Demand Schedule',
            editDescription: (date) => `Originally posted for ${date}`,
        },
    },
}