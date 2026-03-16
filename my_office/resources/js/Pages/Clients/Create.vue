<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useSiretValidator } from '@/Composables/useSiretValidator';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const initialStatus = new URLSearchParams(window.location.search).get('status') === 'client' ? 'client' : 'prospect';

const form = useForm({
    name: '',
    company: '',
    alias: '',
    email: '',
    phone: '',
    address: '',
    zip_code: '',
    city: '',
    siret: '',
    vat_number: '',
    status: initialStatus,
    notes: '',
});

const { siretError, siretValid, validateSiret, clearSiretError } =
    useSiretValidator();

const submit = () => {
    // Validate SIRET before submission
    const validationResult = validateSiret(form.siret);
    if (!validationResult.valid) {
        return;
    }

    form.post(route('prospects.store'));
};
</script>

<template>
    <Head :title="initialStatus === 'client' ? 'Ajouter un Client' : 'Ajouter un Prospect'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    {{ initialStatus === 'client' ? 'Ajouter un Client' : 'Ajouter un Prospect' }}
                </h2>
                <Link
                    :href="route('clients.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Retour à la liste
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden liquid-glass">
                    <div class="p-6 text-gray-900 dark:text-white">
                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="name" value="Nom *" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.name"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel
                                    for="company"
                                    value="Entreprise *"
                                />
                                <TextInput
                                    id="company"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.company"
                                    required
                                    autocomplete="organization"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.company"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel
                                    for="alias"
                                    value="Pseudo / Alias (usage interne)"
                                />
                                <TextInput
                                    id="alias"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.alias"
                                    autocomplete="off"
                                    placeholder="Nom d'affichage personnalisé (optionnel)"
                                />
                                <p class="mt-1 text-xs text-[hsl(var(--text-muted))]">
                                    Ce pseudo n'apparaîtra que dans l'interface, pas sur les documents
                                </p>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.alias"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="email" value="Email *" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                    autocomplete="email"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="phone" value="Téléphone" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                    autocomplete="tel"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.phone"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="address" value="Adresse" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                    autocomplete="street-address"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.address"
                                />
                            </div>

                            <div
                                class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2"
                            >
                                <div>
                                    <InputLabel
                                        for="zip_code"
                                        value="Code postal"
                                    />
                                    <TextInput
                                        id="zip_code"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.zip_code"
                                        autocomplete="postal-code"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.zip_code"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="city" value="Ville" />
                                    <TextInput
                                        id="city"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.city"
                                        autocomplete="address-level2"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.city"
                                    />
                                </div>
                            </div>

                            <div class="mt-4">
                                <InputLabel for="siret" value="SIRET" />
                                <div class="relative">
                                    <TextInput
                                        id="siret"
                                        type="text"
                                        :class="[
                                            'mt-1 block w-full',
                                            siretError ? 'border-red-500' : '',
                                            siretValid
                                                ? 'border-green-500'
                                                : '',
                                        ]"
                                        v-model="form.siret"
                                        @blur="validateSiret(form.siret)"
                                        @focus="clearSiretError"
                                        maxlength="14"
                                        pattern="\d*"
                                        placeholder="14 chiffres"
                                    />
                                    <span
                                        v-if="siretValid"
                                        class="absolute right-3 top-3 text-green-500"
                                    >
                                        ✓
                                    </span>
                                </div>
                                <InputError
                                    class="mt-2"
                                    :message="siretError || form.errors.siret"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="vat_number" value="N° TVA Intracommunautaire" />
                                <TextInput
                                    id="vat_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.vat_number"
                                    placeholder="FR12345678901"
                                />
                                <InputError class="mt-2" :message="form.errors.vat_number" />
                            </div>

                            <!-- Notes Section -->
                            <div class="mt-8 border-t border-gray-200 dark:border-apple-dark-border pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    Notes
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-apple-dark-secondary mb-4">
                                    Ajoutez une note initiale pour ce prospect (optionnel)
                                </p>
                                <div>
                                    <InputLabel
                                        for="notes"
                                        value="Note initiale"
                                    />
                                    <textarea
                                        id="notes"
                                        class="glass-textarea mt-1 block w-full"
                                        v-model="form.notes"
                                        rows="4"
                                        placeholder="Ex: Contact via LinkedIn, intéressé par nos services..."
                                    ></textarea>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.notes"
                                    />
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-end">
                                <Link
                                    :href="route('clients.index')"
                                    class="mr-4 text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Annuler
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    {{ initialStatus === 'client' ? 'Créer le client' : 'Créer le prospect' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
