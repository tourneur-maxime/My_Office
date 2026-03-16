<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    settings: Object,
    preview: String,
});

const form = useForm({
    prefix: props.settings.prefix || '',
    suffix: props.settings.suffix || '',
    digit_count: props.settings.digit_count || 4,
    reset_frequency: props.settings.reset_frequency || 'yearly',
    separator: props.settings.separator ?? '-', // Default to - if null
    include_year:
        props.settings.include_year !== undefined
            ? !!props.settings.include_year
            : true,
});

const nextPreview = computed(() => {
    const parts = [];
    if (form.prefix) parts.push(form.prefix);

    if (form.include_year) {
        parts.push(String(new Date().getFullYear()));
    }

    // Simple preview logic: current + 1
    const nextNum = (props.settings.current_number || 0) + 1;
    parts.push(String(nextNum).padStart(form.digit_count, '0'));

    if (form.suffix) parts.push(form.suffix);

    // Use separator or empty string if cleared
    const sep = form.separator || '';
    return parts.join(sep);
});

const submit = () => {
    form.patch(route('settings.invoices.numbering.update'), {
        preserveScroll: true,
    });
};

const resetSettings = () => {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser les paramètres de numérotation ?')) {
        router.delete(route('settings.invoices.numbering.reset'), {
            preserveScroll: true,
            onSuccess: () => {
                form.prefix = props.settings.prefix;
                form.suffix = props.settings.suffix;
                form.digit_count = props.settings.digit_count;
                form.reset_frequency = props.settings.reset_frequency;
                form.separator = props.settings.separator;
                form.include_year = !!props.settings.include_year;
            }
        });
    }
};
</script>

<template>
    <Head title="Paramètres Factures" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Paramètres de Numérotation des Factures
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <form @submit.prevent="submit" class="max-w-xl space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Préfixe</label
                                >
                                <input
                                    v-model="form.prefix"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Séparateur</label
                                >
                                <input
                                    v-model="form.separator"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="(vide)"
                                />
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                v-model="form.include_year"
                                type="checkbox"
                                id="include_year"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            />
                            <label
                                for="include_year"
                                class="text-sm font-medium text-gray-700"
                                >Inclure l'année dans le numéro</label
                            >
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Nombre de chiffres</label
                            >
                            <input
                                v-model="form.digit_count"
                                type="number"
                                min="3"
                                max="10"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Suffixe</label
                            >
                            <input
                                v-model="form.suffix"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Réinitialisation</label
                            >
                            <select
                                v-model="form.reset_frequency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="yearly">
                                    Annuelle (recommence à 1 chaque année)
                                </option>
                                <option value="never">
                                    Jamais (continue indéfiniment)
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Dernier numéro utilisé</label
                            >
                            <p
                                class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-gray-700 shadow-sm"
                            >
                                {{ settings.current_number }}
                            </p>
                        </div>

                        <div
                            class="rounded-md border border-gray-200 bg-gray-50 p-4"
                        >
                            <label
                                class="block text-sm font-medium uppercase tracking-wider text-gray-600"
                                >Aperçu du prochain numéro :</label
                            >
                            <div
                                class="mt-2 font-mono text-2xl font-bold text-indigo-600"
                            >
                                {{ nextPreview }}
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white ring-gray-300 transition duration-150 ease-in-out hover:bg-gray-700 focus:border-gray-900 focus:outline-none focus:ring active:bg-gray-900 disabled:opacity-25"
                                :disabled="form.processing"
                            >
                                Enregistrer
                            </button>

                            <SecondaryButton 
                                type="button"
                                @click="resetSettings"
                                :disabled="form.processing"
                            >
                                Réinitialiser
                            </SecondaryButton>

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
                                    Enregistré.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
