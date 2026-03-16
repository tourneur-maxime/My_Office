<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    templates: Array,
    companyProfile: Object,
});

const deleteForm = useForm({});
const setDefaultForm = useForm({});

const deleteTemplate = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')) {
        deleteForm.delete(route('settings.templates.destroy', id));
    }
};

const setAsDefault = (id) => {
    setDefaultForm.patch(route('settings.templates.setDefault', id));
};
</script>

<template>
    <Head title="Gestion des modèles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Gestion des modèles de documents
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Gérez vos modèles personnalisés pour vos devis et
                        factures.
                    </p>
                    <Link
                        :href="route('settings.branding')"
                        class="btn btn-primary btn-sm"
                    >
                        Créer un nouveau modèle
                    </Link>
                </div>

                <div
                    v-if="templates.length === 0"
                    class="overflow-hidden bg-white p-12 text-center shadow-sm sm:rounded-lg"
                >
                    <p class="italic text-gray-500">
                        Vous n'avez pas encore de modèle enregistré.
                    </p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="tpl in templates"
                        :key="tpl.id"
                        class="overflow-hidden border-t-4 bg-white shadow-sm sm:rounded-lg"
                        :style="{ borderTopColor: tpl.primary_color }"
                    >
                        <div class="p-6">
                            <div class="mb-4 flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">
                                        {{ tpl.name }}
                                    </h3>
                                    <span
                                        v-if="tpl.is_default"
                                        class="badge badge-success badge-sm mt-1"
                                        >Par défaut</span
                                    >
                                </div>
                                <div class="flex space-x-2">
                                    <!-- Mini Preview Box -->
                                    <div
                                        class="h-8 w-8 rounded border"
                                        :style="{
                                            backgroundColor: tpl.primary_color,
                                        }"
                                        title="Couleur principale"
                                    ></div>
                                    <div
                                        class="h-8 w-8 rounded border"
                                        :style="{
                                            backgroundColor:
                                                tpl.secondary_color,
                                        }"
                                        title="Couleur secondaire"
                                    ></div>
                                </div>
                            </div>

                            <div class="mb-6 space-y-2 text-sm text-gray-600">
                                <p>
                                    <span class="font-medium text-gray-700"
                                        >Police:</span
                                    >
                                    {{ tpl.font_family }}
                                </p>
                                <p>
                                    <span class="font-medium text-gray-700"
                                        >Logo:</span
                                    >
                                    {{ tpl.logo_position }} ({{
                                        tpl.logo_size
                                    }}px)
                                </p>
                            </div>

                            <div
                                class="flex items-center justify-between border-t pt-4"
                            >
                                <button
                                    v-if="!tpl.is_default"
                                    @click="setAsDefault(tpl.id)"
                                    class="text-xs font-semibold uppercase tracking-widest text-indigo-600 hover:text-indigo-900"
                                >
                                    Par défaut
                                </button>
                                <div v-else></div>

                                <button
                                    @click="deleteTemplate(tpl.id)"
                                    class="text-xs font-semibold uppercase tracking-widest text-red-600 hover:text-red-900"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
