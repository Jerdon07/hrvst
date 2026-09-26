<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import {
    Archive,
    Menu,
    Package,
    PackageSearch,
    ShoppingBag,
    Vegan,
    CircleHelp,
    UserCheck,
} from '@lucide/vue'
import { computed, ref } from 'vue'
import AppLogo from '@/components/layout/AppLogo.vue'
import AppLogoIcon from '@/components/layout/AppLogoIcon.vue'
import Breadcrumbs from '@/components/layout/Breadcrumbs.vue'
import NotificationBell from '@/components/layout/NotificationBell.vue'
import UserMenuContent from '@/components/layout/UserMenuContent.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet'
import { useCurrentUrl } from '@/composables/useCurrentUrl'
import { getInitials } from '@/composables/useInitials'
import { useOnboardingGuide } from '@/composables/useOnboardingGuide'
import { dashboard } from '@/routes'
import admin from '@/routes/admin'
import dealer from '@/routes/dealer'
import { archived as dealerDemandsArchived } from '@/routes/dealer/demands'
import farmer from '@/routes/farmer'
import { archived as farmerSuppliesArchived } from '@/routes/farmer/supplies'
import vegetables from '@/routes/vegetables'
import type { BreadcrumbItem, NavItem } from '@/types'
import AppTooltip from '../templates/AppTooltip.vue'

type Props = {
    breadcrumbs?: BreadcrumbItem[]
}

interface AppNavItem extends NavItem {
    activeComponentMatch?: string
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

const page = usePage()
const auth = computed(() => page.props.auth)
const { isCurrentUrl } = useCurrentUrl()
const mobileMenuOpen = ref(false)
const { open: openOnboarding } = useOnboardingGuide()

const showOnboardingTrigger = computed(
    () =>
        page.props.auth.user.roles.includes('farmer') ||
        page.props.auth.user.roles.includes('dealer'),
)

const showWatchAlerts = showOnboardingTrigger

function openOnboardingFromMobile(): void {
    mobileMenuOpen.value = false
    openOnboarding()
}

function isNavItemActive(item: AppNavItem): boolean {
    if (
        item.activeComponentMatch &&
        (page.component as string).includes(item.activeComponentMatch)
    ) {
        return true
    }

    return isCurrentUrl(item.href)
}

function navItemClass(item: AppNavItem): string {
    return isNavItemActive(item) ? 'text-foreground' : ''
}

const mainNavItems = computed<AppNavItem[]>(() => {
    const items: AppNavItem[] = []

    if (page.props.auth.user.roles.includes('admin')) {
        items.push(
            {
                title: 'Vegetables',
                href: admin.vegetables.index(),
                icon: Package,
                activeComponentMatch: '/vegetables/',
            },
            { 
                title: 'Farmers', 
                href: admin.farmers.index(),
                icon: Vegan, 
                activeComponentMatch: 'admin/farmers/'
            },
            {
                title: 'Dealers',
                href: admin.dealers.index(),
                icon: ShoppingBag,
                activeComponentMatch: 'admin/dealers/'
            },
        )
    }

    if (page.props.auth.user.roles.includes('dealer')) {
        items.push(
            {
                title: 'Vegetables',
                href: vegetables.index(),
                icon: Vegan,
                activeComponentMatch: '/vegetables/',
            },
            {
                title: 'My Schedules',
                href: dealer.demands.index(),
                icon: PackageSearch,
            },
        )
    }

    if (page.props.auth.user.roles.includes('farmer')) {
        items.push(
            {
                title: 'Vegetables',
                href: vegetables.index(),
                icon: Vegan,
                activeComponentMatch: '/vegetables/',
            },
            {
                title: 'My Schedules',
                href: farmer.supplies.index(),
                icon: Package,
            },
        )
    }

    return items
})

const rightNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = []

    if (page.props.auth.user.roles.includes('dealer')) {
        items.push(
            {
                title: 'Archives',
                href: dealerDemandsArchived(),
                icon: Archive,
            }
        )
    }

    if (page.props.auth.user.roles.includes('farmer')) {
        items.push(
            {
                title: 'Archives',
                href: farmerSuppliesArchived(),
                icon: Archive,
            }
        )
    }

    if (page.props.auth.user.roles.includes('admin')) {
        items.push(
            {
                title: 'Sign-up Requests',
                href: admin.registrationRequests.index(),
                icon: UserCheck,
            }
        )
    }

    return items
})
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile menu -->
                <div class="lg:hidden">
                    <Sheet v-model:open="mobileMenuOpen">
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            class="w-[300px] p-6"
                        >
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetDescription class="sr-only">Main navigation links</SheetDescription>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon class="size-6 fill-current text-black dark:text-white" />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="navItemClass(item)"
                                        @click="mobileMenuOpen = false"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>

                                    <div
                                        v-if="rightNavItems.length"
                                        class="my-2 border-t border-sidebar-border/70"
                                    />

                                    <Link
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="navItemClass(item)"
                                        @click="mobileMenuOpen = false"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>

                                    <button
                                        v-if="showOnboardingTrigger"
                                        type="button"
                                        class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-left text-sm font-medium hover:bg-accent"
                                        @click="openOnboardingFromMobile"
                                    >
                                        <CircleHelp class="h-5 w-5" />
                                        How it works
                                    </button>
                                </nav>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link
                    :href="dashboard()"
                    class="flex items-center gap-x-2"
                >
                    <AppLogo />
                </Link>

                <!-- Desktop nav -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-2">
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        navItemClass(item),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        :is="item.icon"
                                        v-if="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isNavItemActive(item)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-primary"
                                />
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <NotificationBell v-if="showWatchAlerts" />

                    <div class="relative flex items-center space-x-1">
                        <div class="hidden space-x-1 lg:flex">
                            <AppTooltip 
                                v-if="showOnboardingTrigger" 
                                content="How it works"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="group h-9 w-9 cursor-pointer"
                                    @click="openOnboarding"
                                >
                                    <span class="sr-only">How it works</span>
                                    <CircleHelp class="size-5 opacity-80 group-hover:opacity-100" />
                                </Button>
                            </AppTooltip>
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <AppTooltip :content="item.title">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                        class="group h-9 w-9 cursor-pointer"
                                    >
                                        <Link
                                            :href="item.href"
                                            rel="noopener noreferrer"
                                        >
                                            <span class="sr-only">{{
                                                item.title
                                            }}</span>
                                            <component
                                                :is="item.icon"
                                                class="size-5 opacity-80 group-hover:opacity-100"
                                            />
                                        </Link>
                                    </Button>
                                </AppTooltip>
                            </template>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-56"
                        >
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>