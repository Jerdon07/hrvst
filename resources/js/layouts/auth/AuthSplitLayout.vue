<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import AppLogoIcon from '@/components/layout/AppLogoIcon.vue'
import { Card, CardContent } from '@/components/ui/card'
import { home } from '@/routes'

withDefaults(
    defineProps<{
        title?: string
        description?: string
        /** Cover image shown beside the form on md+ screens */
        image?: string
    }>(),
    {
        image: '/images/auth-cover.jpg',
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
                                class="mb-1 flex items-center justify-center"
                            >
                                <AppLogoIcon class="size-9 fill-current text-black dark:text-white" />
                                <span class="sr-only">{{ name }}</span>
                            </Link>
                            <h1
                                v-if="title"
                                class="text-xl font-medium tracking-tight"
                            >
                                {{ title }}
                            </h1>
                            <p
                                v-if="description"
                                class="text-sm text-balance text-muted-foreground"
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
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>