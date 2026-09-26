<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { Phone } from '@lucide/vue'
import { computed, ref } from 'vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AuthBase from '@/layouts/AuthLayout.vue'
import { store } from '@/routes/login'

const props = defineProps<{
  status?: string
  systemAdminPhone?: string|null
}>()

const telHref = computed(() =>
  props.systemAdminPhone ? `tel:${props.systemAdminPhone.replace(/[^\d+]/g, '')}` : null,
)

const pin = ref('')
</script>

<template>
    <AuthBase
        title="Sign in to Hrvst"
        description="Enter your phone number and PIN to continue"
    >
        <Head title="Sign in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-slot="{ errors, processing }"
            v-bind="{ action: store.url(), method: 'post' }"
            class="flex flex-col gap-6"
            @error="pin = ''"
        >
            <input
                type="hidden"
                name="password"
                :value="pin"
            />

            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="phone_number">Phone Number</Label>
                    <InputGroup>
                        <InputGroupInput
                            id="phone_number"
                            type="tel"
                            name="phone_number"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="tel"
                            placeholder="09*********"
                        />
                        <InputGroupAddon>
                            <Phone />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="errors.phone_number" />
                </div>

                <Collapsible class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="pin">PIN</Label>

                        <CollapsibleTrigger as-child>
                            <Button
                                type="button"
                                variant="link"
                                class="underline"
                            >
                                Forgot PIN?
                            </Button>
                        </CollapsibleTrigger>
                    </div>

                    <div class="flex justify-center">
                        <InputOTP
                            id="pin"
                            v-model="pin"
                            :maxlength="6"
                            :disabled="processing"
                            :tabindex="2"
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="index in 6"
                                    :key="index"
                                    :index="index - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="errors.password" />

                    <CollapsibleContent>
                        <p class="text-sm text-muted-foreground">
                            Contact System Admin to have it reset<template v-if="telHref"> at
                                <a
                                    :href="telHref"
                                    class="font-medium text-foreground underline decoration-neutral-300 underline-offset-4 hover:decoration-current"
                                >{{ systemAdminPhone }}</a></template>.
                        </p>
                    </CollapsibleContent>
                </Collapsible>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="3"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Sign in
                </Button>

                <p class="text-center text-sm text-muted-foreground">
                    Don't have an account?
                    <a
                        href="/register"
                        class="font-medium text-foreground underline decoration-neutral-300 underline-offset-4 hover:decoration-current"
                    >
                        Request one
                    </a>
                </p>
            </div>
        </Form>
    </AuthBase>
</template>
