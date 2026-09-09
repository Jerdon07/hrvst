<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Clock } from '@lucide/vue'
import { ArrowRight, BarChart3, Download, MapPin, Package, ShieldCheck, Share, Sprout, Store, TrendingUp } from '@lucide/vue'
import { ref, watch } from 'vue'
import FlashToaster from '@/components/FlashToaster.vue'
import AppLogoIcon from '@/components/layout/AppLogoIcon.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { usePwaInstall } from '@/composables/usePwaInstall'
import { dashboard, login, logout } from '@/routes'
import register from '@/routes/register'
import type { FlashMessage } from '@/types'

const page = usePage()
const pendingDialogOpen = ref(false)

watch(
    () => page.props.flash as FlashMessage | null,
    (flash) => {
        if (flash?.type === 'success') {
            pendingDialogOpen.value = true
        }
    },
    { immediate: true },
)

const { canInstall, isIos, install } = usePwaInstall()
const iosInstructionsOpen = ref(false)

async function handleInstallClick(): Promise<void> {
    if (isIos) {
        iosInstructionsOpen.value = true
        return
    }

    await install()
}

const farmerBenefits = [
    {
        icon: Sprout,
        title: 'List Your Produce',
        desc: 'Post your harvest with in minutes.',
    },
    {
        icon: TrendingUp,
        title: 'Market Intelligence',
        desc: 'See supply and demand trends to make smarter planting and harvest decisions.',
    },
    {
        icon: MapPin,
        title: 'Connect Locally',
        desc: 'Find verified dealers in your area — no guesswork.',
    },
    {
        icon: ShieldCheck,
        title: 'Verified Buyers',
        desc: 'Trade confidently with admin-approved dealer profiles.',
    },
]

const dealerBenefits = [
    {
        icon: Package,
        title: 'Source Fresh Supply',
        desc: 'Browse farmer listings by crop, location, and availability.',
    },
    {
        icon: BarChart3,
        title: 'Schedule Your Demand',
        desc: 'Schedule a demand so the right farmers come to you.',
    },
    {
        icon: MapPin,
        title: 'Supply Map',
        desc: 'Visualise nearby supply geographically — plan your pickups.',
    },
    {
        icon: ShieldCheck,
        title: 'Verified Farmers',
        desc: 'Work with approved profiles backed by full harvest history.',
    },
]

const adminBenefits = [
    {
        icon: Package,
        title: 'Lorem Ipsum',
        desc: 'Lorem Ipsum',
    },
    {
        icon: BarChart3,
        title: 'Lorem Ipsum',
        desc: 'Lorem Ipsum',
    },
    {
        icon: MapPin,
        title: 'Lorem Ipsum',
        desc: 'Lorem Ipsum.',
    },
    {
        icon: ShieldCheck,
        title: 'Lorem Ipsum',
        desc: 'Lorem Ipsum.',
    },
]

const steps = [
    {
        step: '01',
        title: 'Get registered by an admin',
        desc: 'An administrator verifies your identity and creates your account. You will receive a temporary PIN to sign in.',
    },
    {
        step: '02',
        title: 'Post or browse',
        desc: 'Farmers list their harvest with quantity. Dealers post their demand. Both discover each other in one feed.',
    },
    {
        step: '03',
        title: 'Trade directly',
        desc: 'Connect, negotiate, and close deals — no intermediaries taking a cut, no information gaps.',
    },
]
</script>

<template>
    <FlashToaster />
    <div class="min-h-screen antialiased">
        <Head title="Hrvst — Agricultural Marketplace">
            <link
                rel="preconnect"
                href="https://fonts.bunny.net"
            />
            <link
                href="https://fonts.bunny.net/css?family=aleo:400,600,700,900&display=swap"
                rel="stylesheet"
            />
        </Head>

        <!-- ═══════════════════════════════════════ NAV ═══ -->
        <nav class="fixed top-0 z-50 w-full border-b border-border bg-background/75 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <Link
                    href="/"
                    class="flex items-center gap-2.5 select-none"
                >
                    <AppLogoIcon class="size-8 fill-current"/>
                    <span class="text-lg font-semibold tracking-tight">Hrvst</span>
                </Link>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="canInstall"
                        variant="outline"
                        size="sm"
                        class="gap-2 font-semibold"
                        @click="handleInstallClick"
                    >
                        <Download class="size-4" />
                        Install App
                    </Button>

                    <template v-if="page.props.auth.user">
                        <Link :href="logout()">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="font-bold"
                            >
                                Log out
                            </Button>
                        </Link>
                        <Link :href="dashboard()">
                            <Button class="font-semibold">Dashboard</Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()">
                            <Button
                                class="font-semibold"
                                variant="ghost"
                            >
                                Sign In
                            </Button>
                        </Link>

                        <Link :href="register.create()">
                            <Button class="font-semibold">
                                Register
                            </Button>
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- ═══════════════════════════════════════ HERO ═══ -->
        <section
    class="relative flex min-h-screen items-center overflow-hidden px-6 pt-20"
>
    <!-- Garden background -->
    <div
        class="absolute inset-0 bg-cover bg-center"
        style="background-image: url('/images/welcome/background.webp');"
    ></div>

    <!-- Image overlay -->
    <div class="absolute inset-0 bg-background/40"></div>

    <!-- Fade into page background at bottom -->
    <div
        class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-background via-background/70 to-transparent"
    ></div>

    <!-- Hero content -->
    <div
        class="relative z-10 mx-auto grid w-full max-w-7xl items-center gap-12 py-20 lg:grid-cols-2 lg:gap-20"
    >
        <!-- ═══════════════════════════════════ TEXT ═══ -->
        <div class="max-w-2xl text-left">
            <Badge
                variant="outline"
                class="mb-6 border-primary bg-primary/10 px-4 py-1.5 text-xs font-bold tracking-widest text-primary"
            >
                <Sprout class="size-3.5 shrink-0" />
                TRADING POST · TEAM CRESCO
            </Badge>

            <h1
                class="hero-title mb-6 text-5xl font-black leading-[1.05] tracking-tight sm:text-7xl lg:text-[5.5rem]"
            >
                Trading Post<br />
                <span class="text-primary">Synchronizer</span>
            </h1>

            <p
                class="hero-sub mb-10 max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg"
            >
                Hrvst is Trading Post's synchronizer where both sides commit
                forward-looking intentions before going to Trading Post.
                Built to surface the imbalance between supplies & demands
                early enough for either side to adjust — Created and preserved
                by team Cresco.
            </p>

            <div class="hero-cta flex flex-wrap items-center gap-4">
                <template v-if="page.props.auth.user">
                    <Link
                        :href="dashboard()"
                        class="group transition-all duration-300"
                    >
                        <Button class="font-semibold">
                            Go to Dashboard
                            <ArrowRight
                                class="size-4 transition-transform group-hover:translate-x-1"
                            />
                        </Button>
                    </Link>
                </template>

                <template v-else>
                    <Button
                        v-if="canInstall"
                        variant="outline"
                        size="sm"
                        class="gap-2 font-semibold"
                        @click="handleInstallClick"
                    >
                        <Download class="size-4" />
                        Install App
                    </Button>

                    <Link :href="register.create()">
                        <Button class="px-3 font-semibold">
                            Register
                            <ArrowRight
                                class="size-4 transition-transform group-hover:translate-x-1"
                            />
                        </Button>
                    </Link>
                </template>
            </div>

            <p class="hero-hint mt-6 text-xs text-muted-foreground">
                We shape the transparency · You decide for the best.
            </p>
        </div>

        <!-- ═══════════════════════════════════ IMAGES ═══ -->
        <div
            class="flex min-h-[400px] items-center justify-center"
        >
            <div
                class="flex h-[400px] w-full max-w-xl items-center justify-center rounded-3xl border border-white/20 bg-white/10 p-6 shadow-2xl backdrop-blur-sm"
            >
                <!--
                    PUT YOUR IMAGES HERE

                    Example:

                    <img
                        src="/images/dashboard.png"
                        alt="Hrvst Dashboard"
                        class="w-full rounded-2xl shadow-xl"
                    />
                -->

                <span class="text-sm text-muted-foreground">
                    Image / mockup container
                </span>
            </div>
        </div>
    </div>
</section>

        <!-- ═══════════════════════════════════════ DUAL AUDIENCE ═══ -->
        <section class="px-6 py-24 bg-card border-y">
            <div class="mx-auto max-w-7xl">
                <div class="mb-16 text-center">
                    <p class="mb-3 text-xs font-bold tracking-widest uppercase text-primary">
                        Built for Trading Post
                    </p>
                    <h2 class="text-4xl font-black sm:text-5xl">
                        One platform. Three roles.
                    </h2>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Farmer card -->
                    <div
                        class="relative overflow-hidden rounded-2xl p-8 lg:p-12"
                        style="
                            background: oklch(0.792 0.209 151.711 / 0.06);
                            border: 1px solid oklch(0.792 0.209 151.711 / 0.2);
                        "
                    >
                        <div
                            class="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full opacity-20"
                            aria-hidden="true"
                            style="
                                background: radial-gradient(
                                    circle,
                                    var(--primary),
                                    transparent
                                );
                            "
                        />

                        <div class="relative z-10">
                            <div
                                class="mb-6 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-bold tracking-widest uppercase"
                                style="
                                    background: oklch(
                                        0.792 0.209 151.711 / 0.12
                                    );
                                    color: oklch(0.5 0.209 151.711);
                                "
                            >
                                <Sprout class="size-3.5" />
                                For Farmers
                            </div>

                            <h3 class="mb-4 text-xl leading-snug font-black lg:text-3xl">
                                Grow what's in-demand,<br />not what's oversupplied.
                            </h3>

                            <p class="mb-8 text-sm leading-relaxed text-muted-foreground">
                                Turn market transparency into higher profits.
                                See what's in demand, list your harvest, and
                                deal directly with trading post dealers.
                            </p>

                            <ul class="space-y-4">
                                <li
                                    v-for="item in farmerBenefits"
                                    :key="item.title"
                                    class="flex items-start gap-3 text-sm"
                                >
                                    <component
                                        :is="item.icon"
                                        class="mt-0.5 size-4 shrink-0 text-primary"
                                    />
                                    <span>
                                        <strong class="font-semibold">{{ item.title }}. </strong>
                                        {{ item.desc }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Dealer card -->
                    <div
                        class="relative overflow-hidden rounded-2xl p-8 lg:p-12"
                        style="
                            background: oklch(0.792 0.209 151.711 / 0.03);
                            border: 1px solid oklch(0.792 0.209 151.711 / 0.14);
                        "
                    >
                        <div
                            class="pointer-events-none absolute -bottom-16 -left-16 size-64 rounded-full opacity-15"
                            aria-hidden="true"
                            style="
                                background: radial-gradient(
                                    circle,
                                    var(--primary),
                                    transparent
                                );
                            "
                        />

                        <div class="relative z-10">
                            <div
                                class="mb-6 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-bold tracking-widest uppercase"
                                style="
                                    background: oklch(
                                        0.792 0.209 151.711 / 0.1
                                    );
                                    color: oklch(0.5 0.209 151.711);
                                "
                            >
                                <Store class="size-3.5" />
                                For Dealers
                            </div>

                            <h3 class="mb-4 text-2xl leading-snug font-black lg:text-3xl">
                                Source by certainty,<br />not by chance.
                            </h3>

                            <p class="mb-8 text-sm leading-relaxed">
                                Access real-time supply data to find the right
                                produce at the right time.
                            </p>

                            <ul class="space-y-4">
                                <li
                                    v-for="item in dealerBenefits"
                                    :key="item.title"
                                    class="flex items-start gap-3 text-sm"
                                >
                                    <component
                                        :is="item.icon"
                                        class="mt-0.5 size-4 shrink-0 text-primary"
                                    />
                                    <span>
                                        <strong class="font-semibold">{{ item.title }}. </strong>
                                        {{ item.desc }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Admin card -->
                    <div
                        class="relative overflow-hidden rounded-2xl p-8 lg:p-12"
                        style="
                            background: oklch(0.792 0.209 151.711 / 0.06);
                            border: 1px solid oklch(0.792 0.209 151.711 / 0.2);
                        "
                    >
                        <div
                            class="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full opacity-20"
                            aria-hidden="true"
                            style="
                                background: radial-gradient(
                                    circle,
                                    var(--primary),
                                    transparent
                                );
                            "
                        />

                        <div class="relative z-10">
                            <div
                                class="mb-6 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-bold tracking-widest uppercase"
                                style="
                                    background: oklch(
                                        0.792 0.209 151.711 / 0.12
                                    );
                                    color: oklch(0.5 0.209 151.711);
                                "
                            >
                                <Sprout class="size-3.5" />
                                For Trading Post Admin
                            </div>

                            <h3 class="mb-4 text-xl leading-snug font-black lg:text-3xl">
                                View Supply and Demand,<br /> Forecast Market Gluts.
                            </h3>

                            <p class="mb-8 text-sm leading-relaxed text-muted-foreground">
                                Lorem ipsum.
                            </p>

                            <ul class="space-y-4">
                                <li
                                    v-for="item in adminBenefits"
                                    :key="item.title"
                                    class="flex items-start gap-3 text-sm"
                                >
                                    <component
                                        :is="item.icon"
                                        class="mt-0.5 size-4 shrink-0 text-primary"
                                    />
                                    <span>
                                        <strong class="font-semibold">{{ item.title }}. </strong>
                                        {{ item.desc }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════ HOW IT WORKS ═══ -->
        <section
            class="px-6 py-24"
            style="background: var(--background)"
        >
            <div class="mx-auto max-w-3xl">
                <div class="mb-16 text-center">
                    <p
                        class="mb-3 text-xs font-bold tracking-widest uppercase"
                        style="color: var(--primary)"
                    >
                        Simple by design
                    </p>
                    <h2
                        class="text-4xl font-black sm:text-5xl"
                        style="
                            font-family: 'Aleo', ui-serif, serif;
                            color: var(--foreground);
                        "
                    >
                        How Hrvst works
                    </h2>
                </div>

                <div class="relative space-y-0">
                    <div
                        class="absolute top-6 left-6 hidden h-[calc(100%-3rem)] w-px lg:block"
                        aria-hidden="true"
                        style="
                            background: linear-gradient(
                                to bottom,
                                var(--primary),
                                transparent
                            );
                        "
                    />

                    <div
                        v-for="(step, index) in steps"
                        :key="step.step"
                        class="flex gap-8 lg:gap-12"
                        :class="index < steps.length - 1 ? 'pb-12' : ''"
                    >
                        <div class="relative flex shrink-0 flex-col items-center">
                            <div
                                class="flex size-12 items-center justify-center rounded-full text-xs font-bold tracking-wider shadow-md"
                                style="
                                    background: var(--primary);
                                    color: var(--primary-foreground);
                                "
                            >
                                {{ step.step }}
                            </div>
                        </div>

                        <div class="pt-2 pb-1">
                            <h3
                                class="mb-1.5 text-xl font-bold"
                                style="color: var(--foreground)"
                            >
                                {{ step.title }}
                            </h3>
                            <p
                                class="text-sm leading-relaxed"
                                style="color: var(--muted-foreground)"
                            >
                                {{ step.desc }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════ FOOTER ═══ -->
        <footer
            class="px-6 py-8"
            style="background: var(--card); border-top: 1px solid var(--border)"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div class="flex items-center gap-2">
                    <AppLogoIcon
                        class="size-5 fill-current opacity-40"
                        style="color: var(--muted-foreground)"
                    />
                    <span
                        class="text-sm font-semibold opacity-50"
                        style="color: var(--muted-foreground)"
                    >Hrvst</span>
                </div>
                <p
                    class="text-xs opacity-50"
                    style="color: var(--muted-foreground)"
                >
                    Trading Post · Team Cresco
                </p>
            </div>
        </footer>

        <!-- ═══════════════════════════════════════ PWA — iOS INSTALL INSTRUCTIONS ═══ -->
        <Dialog v-model:open="iosInstructionsOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Share class="size-4" />
                        Install on iOS
                    </DialogTitle>
                    <DialogDescription>
                        Tap the Share button in Safari's toolbar, then select
                        "Add to Home Screen."
                    </DialogDescription>
                </DialogHeader>
            </DialogContent>
        </Dialog>
    </div>

    <Dialog v-model:open="pendingDialogOpen">
        <DialogContent
            class="sm:max-w-md"
            @pointer-down-outside.prevent
        >
            <DialogHeader class="items-center text-center">
                <div class="mx-auto mb-2 flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Clock class="size-6" />
                </div>
                <DialogTitle>Request Submitted</DialogTitle>
                <DialogDescription class="text-center">
                    Every account is manually verified by an admin before it's activated —
                    that's what keeps this platform free of fake accounts. This usually
                    takes about a day. We'll reach out once you're approved and give you
                    your login PIN then.
                </DialogDescription>
            </DialogHeader>
            <Button
                class="w-full"
                @click="pendingDialogOpen = false"
            >Got it</Button>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(16px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fade-in {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.hero-badge {
    animation: fade-in 0.6s ease both;
}

.hero-title {
    animation: fade-in-up 0.7s ease both;
    animation-delay: 80ms;
}

.hero-sub {
    animation: fade-in-up 0.7s ease both;
    animation-delay: 180ms;
}

.hero-cta {
    animation: fade-in-up 0.7s ease both;
    animation-delay: 270ms;
}

.hero-hint {
    animation: fade-in 0.7s ease both;
    animation-delay: 400ms;
}
</style>
