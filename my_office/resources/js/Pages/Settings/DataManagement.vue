<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    lastExport: Object,
});

const exportForm = useForm({});
const backupForm = useForm({});

const isExporting = ref(false);
const isBackingUp = ref(false);

const runExportAll = () => {
    isExporting.value = true;
    exportForm.post(route('settings.data.export-all'), {
        preserveScroll: true,
        onSuccess: () => {
            isExporting.value = false;
        },
        onError: () => {
            isExporting.value = false;
        },
    });
};

const runBackup = () => {
    isBackingUp.value = true;
    backupForm.post(route('settings.data.backup'), {
        preserveScroll: true,
        onSuccess: () => {
            isBackingUp.value = false;
        },
        onError: () => {
            isBackingUp.value = false;
        },
    });
};
</script>

<template>
    <Head title="Gestion des Données" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Gestion des Données et Sauvegardes
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Exportation -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                Exportation des Données
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Exportez l'intégralité de vos clients, devis et
                                factures. Les données sensibles seront
                                déchiffrées pour votre usage personnel.
                            </p>
                        </header>

                        <div class="mt-6 space-y-6">
                            <div class="flex items-center gap-4">
                                <PrimaryButton
                                    @click="runExportAll"
                                    :disabled="isExporting"
                                >
                                    <span
                                        v-if="isExporting"
                                        class="loading loading-spinner loading-xs mr-2"
                                    ></span>
                                    Tout exporter (ZIP)
                                </PrimaryButton>
                                <p class="text-xs text-gray-500">
                                    Génère un ZIP contenant CSV, JSON et tous
                                    vos PDFs de factures.
                                </p>
                            </div>

                            <div class="border-t pt-6">
                                <div
                                    class="mb-4 flex items-center justify-between"
                                >
                                    <h3
                                        class="text-md font-medium text-gray-800"
                                    >
                                        Exports Granulaires
                                    </h3>
                                    <Link
                                        :href="
                                            route(
                                                'settings.data.import-clients.show',
                                            )
                                        "
                                        class="btn btn-primary btn-sm"
                                    >
                                        Importer des clients (CSV)
                                    </Link>
                                </div>
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                >
                                    <div class="flex flex-col gap-2">
                                        <span
                                            class="text-sm font-semibold text-gray-700"
                                            >Clients & Prospects</span
                                        >
                                        <div class="flex gap-2">
                                            <a
                                                :href="
                                                    route(
                                                        'settings.data.export-clients',
                                                    )
                                                "
                                                class="btn btn-outline btn-sm flex-1"
                                            >
                                                CSV
                                            </a>
                                            <a
                                                :href="
                                                    route(
                                                        'settings.data.export-clients-json',
                                                    )
                                                "
                                                class="btn btn-outline btn-sm flex-1"
                                            >
                                                JSON
                                            </a>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <span
                                            class="text-sm font-semibold text-gray-700"
                                            >Factures</span
                                        >
                                        <div class="flex gap-2">
                                            <a
                                                :href="
                                                    route(
                                                        'settings.data.export-invoices',
                                                    )
                                                "
                                                class="btn btn-outline btn-sm flex-1"
                                            >
                                                CSV
                                            </a>
                                            <a
                                                :href="
                                                    route(
                                                        'settings.data.export-invoices-json',
                                                    )
                                                "
                                                class="btn btn-outline btn-sm flex-1"
                                            >
                                                JSON
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Export FEC -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                Fichier des Écritures Comptables (FEC)
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Exportez le FEC conforme à l'article A47 A-1 du Livre des Procédures Fiscales.
                                Ce fichier contient toutes les écritures comptables liées à vos factures.
                            </p>
                        </header>
                        <div class="mt-6">
                            <a
                                :href="route('settings.data.export-fec')"
                                class="btn btn-primary"
                            >
                                Télécharger le FEC
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Sauvegarde Système -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                Sauvegarde du Système
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Créez une sauvegarde complète de la base de
                                données. Ces sauvegardes sont destinées à la
                                restauration du système.
                            </p>
                        </header>

                        <div class="mt-6">
                            <PrimaryButton
                                @click="runBackup"
                                :disabled="isBackingUp"
                                class="bg-orange-600 hover:bg-orange-700"
                            >
                                <span
                                    v-if="isBackingUp"
                                    class="loading loading-spinner loading-xs mr-2"
                                ></span>
                                Sauvegarder la base de données
                            </PrimaryButton>
                        </div>
                    </section>
                </div>

                <!-- RGPD Information -->
                <div
                    class="border-l-4 border-blue-400 bg-blue-50 p-4 sm:rounded-lg"
                >
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Note RGPD :</strong> Conformément au
                                Règlement Général sur la Protection des Données,
                                vous disposez d'un droit à la portabilité de vos
                                données. L'exportation ZIP vous permet de
                                récupérer l'intégralité de vos informations dans
                                un format structuré et réutilisable.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
