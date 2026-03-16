<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Mot de passe oublié" />

        <div class="mb-4 text-sm text-[hsl(var(--text-muted))]">
            Vous avez oublié votre mot de passe ? Aucun problème. Indiquez-nous votre adresse e-mail
            et nous vous enverrons un lien de réinitialisation qui vous permettra d'en choisir un nouveau.
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
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

            <div class="mt-6 flex flex-col gap-3">
                <PrimaryButton
                    class="w-full justify-center"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="loading loading-spinner loading-xs mr-2"></span>
                    Envoyer le lien de réinitialisation
                </PrimaryButton>

                <div class="text-center text-sm border-t border-[hsl(var(--border))] pt-3 mt-2">
                    <Link
                        :href="route('login')"
                        class="text-[hsl(var(--text-muted))] hover:text-[hsl(var(--primary))] transition-colors"
                    >
                        Retour à la connexion
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
