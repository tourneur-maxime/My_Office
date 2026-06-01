<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Adresse e-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Mot de passe" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-[hsl(var(--text-muted))]">Se souvenir de moi</span>
                </label>
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="loading loading-spinner loading-xs mr-2"></span>
                    Se connecter
                </PrimaryButton>

                <div class="text-center text-sm">
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[hsl(var(--text-muted))] underline hover:text-[hsl(var(--primary))] transition-colors"
                    >
                        Mot de passe oublié ?
                    </Link>
                </div>

                <div class="text-center text-sm border-t border-[hsl(var(--border))] pt-3 mt-2">
                    <span class="text-[hsl(var(--text-muted))]">Pas encore de compte ? </span>
                    <Link
                        :href="route('register')"
                        class="font-semibold text-[hsl(var(--primary))] hover:underline"
                    >
                        S'inscrire
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
