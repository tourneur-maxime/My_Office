<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    file: null,
});

const isProcessing = ref(false);

const submit = () => {
    isProcessing.value = true;
    form.post(route('settings.data.import-clients'), {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        },
    });
};

const handleFileChange = (e) => {
    form.file = e.target.files[0];
};
</script>

<template>
    <Head title="Importer des Clients" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Importer des Clients & Prospects
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section class="max-w-xl">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                Importation CSV
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Importez vos données clients à partir d'un
                                fichier CSV. Les champs sensibles seront
                                automatiquement chiffrés.
                            </p>
                        </header>

                        <div class="mt-6 space-y-6">
                            <div class="flex items-center gap-4">
                                <a
                                    :href="
                                        route(
                                            'settings.data.import-clients.template',
                                        )
                                    "
                                    class="btn btn-outline btn-sm"
                                >
                                    Télécharger le modèle CSV
                                </a>
                            </div>

                            <form
                                @submit.prevent="submit"
                                class="mt-6 space-y-6"
                            >
                                <div>
                                    <InputLabel
                                        for="file"
                                        value="Sélectionnez le fichier CSV"
                                    />
                                    <input
                                        id="file"
                                        type="file"
                                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                        @change="handleFileChange"
                                        accept=".csv"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.file"
                                    />
                                </div>

                                <div class="flex items-center gap-4">
                                    <PrimaryButton
                                        :disabled="
                                            form.processing || isProcessing
                                        "
                                    >
                                        <span
                                            v-if="isProcessing"
                                            class="loading loading-spinner loading-xs mr-2"
                                        ></span>
                                        Lancer l'importation
                                    </PrimaryButton>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <p
                                            v-if="form.recentlySuccessful"
                                            class="text-sm text-gray-600"
                                        >
                                            Importation lancée.
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>

                <div
                    class="border-l-4 border-blue-400 bg-blue-50 p-4 sm:rounded-lg"
                >
                    <div class="flex">
                        <div class="ml-3 text-sm text-blue-700">
                            <h3 class="mb-2 font-bold">
                                Instructions pour l'importation :
                            </h3>
                            <ul class="ml-5 list-disc space-y-1">
                                <li>
                                    <strong>Colonnes obligatoires :</strong>
                                    name, email
                                </li>
                                <li>
                                    <strong>Format SIRET :</strong> Doit
                                    contenir exactement 14 chiffres.
                                </li>
                                <li>
                                    <strong>Encodage :</strong> Utilisez UTF-8
                                    pour éviter les problèmes d'accents.
                                </li>
                                <li>
                                    <strong>Traitement :</strong> L'importation
                                    se fait en arrière-plan. Vous recevrez une
                                    notification une fois terminée.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
