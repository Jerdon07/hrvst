<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import AppLogoIcon from '@/components/layout/AppLogoIcon.vue'
import { Card, CardContent } from '@/components/ui/card'
import { home } from '@/routes'

withDefaults(
    defineProps<{
        title?: string
        description?: string
        image?: string
    }>(),
    {
        image: '/images/welcome/background.webp',
    },
)

const name = usePage().props.name as string
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-muted p-4 md:p-10">
        <div class="w-full max-w-sm md:max-w-4xl">
            <Card class="overflow-hidden gap-0 p-0">
                <CardContent class="grid p-0 md:grid-cols-2">
                    <div class="flex flex-col gap-6 p-6 md:p-8">
                        <div class="flex flex-col items-center gap-2 text-center">
                            <Link
                                :href="home()"
                                class="sm:hidden mb-1 flex items-center justify-center"
                            >
                                <AppLogoIcon class="size-9 fill-current text-black dark:text-white" />
                                <span class="sr-only">{{ name }}</span>
                            </Link>
                            <h1
                                v-if="title"
                                class="sm:hidden text-xl font-medium tracking-tight"
                            >
                                {{ title }}
                            </h1>
                            <p
                                v-if="description"
                                class="sm:hidden text-sm text-balance text-muted-foreground"
                            >
                                {{ description }}
                            </p>
                        </div>

                        <slot />
                    </div>

                    <div class="relative hidden bg-muted md:block">
                        <img
                            :src="image"
                            alt=""
                            aria-hidden="true"
                            class="absolute inset-0 size-full object-cover dark:brightness-[0.2] dark:grayscale"
                        />

                        <div class="hidden sm:absolute sm:flex flex-col items-center gap-2 text-center">
                            <Link
                                :href="home()" 
                                class="flex gap-4 w-fit pt-10 z-10">
                                <img 
                                    src="favicon.svg" 
                                    alt="Hrvst Logo"
                                    class="hidden sm:block w-20"
                                >

                                <img 
                                    src="/images/trading-post-logo.svg" 
                                    alt="Hrvst Logo"
                                    class="hidden sm:block rounded-full w-20"
                                >
                            </Link>

                            <h1
                                v-if="title"
                                class="text-xl font-medium tracking-tight z-10"
                            >
                                {{ title }}
                            </h1>
                            <p
                                v-if="description"
                                class="text-sm text-balance text-muted-foreground z-10"
                            >
                                {{ description }}
                            </p>
                        </div>

                        <div class="absolute inset-0 bg-background/40"></div>
                        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-teal-500/20 to-transparent"></div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>