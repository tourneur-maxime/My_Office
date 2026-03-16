<script setup>
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import { computed, defineProps, ref } from 'vue';

const props = defineProps({
    quote: {
        type: Object,
        required: true,
    },
    quoteStatuses: {
        type: Array,
        default: () => [],
    },
});

const { success, error } = useToast();
const isGeneratingPdf = ref(false);
const pdfMessage = ref('');
const confirmingQuoteDeletion = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = parseISO(dateString);
        return format(date, 'd MMMM yyyy', { locale: fr });
    } catch (e) {
        return 'Date invalide';
    }
};

const formatCurrency = (value) => {
    const val = typeof value !== 'number' ? parseFloat(value) || 0 : value;
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(val);
};

const deleteQuote = () => {
    router.delete(route('quotes.destroy', props.quote.id), {
        onSuccess: () => {
            confirmingQuoteDeletion.value = false;
            success('Devis supprimé avec succès.');
        },
        onError: () => {
            error('Une erreur est survenue lors de la suppression.');
        },
    });
};

const convertToInvoice = () => {
    router.post(route('quotes.convertToInvoice', { quote: props.quote.id }));
};

const duplicateQuote = () => {
    router.post(route('quotes.duplicate', { quote: props.quote.id }));
};

const generatePdf = () => {
    router.post(route('quotes.generatePdf', { quote: props.quote.id }), {}, {
        onSuccess: () => {
            success('La génération du PDF a été lancée. Le fichier sera disponible sous peu.');
        },
        onError: () => {
            error('Une erreur est survenue lors de la génération du PDF.');
        },
    });
};

const isConverted = computed(() => {
    return props.quote.invoices && props.quote.invoices.length > 0;
});

const isEditable = computed(() => {
    return props.quote.status === 'Brouillon' && !isConverted.value;
});

const canConvertToInvoice = computed(() => {
    return (
        (props.quote.status === 'Approuve' ||
            props.quote.status === 'Approuvé') &&
        !isConverted.value
    );
});

const getStatusClass = (status) => {
    const statusClasses = {
        Brouillon: 'text-yellow-600',
        Envoye: 'text-blue-600',
        Envoyé: 'text-blue-600',
        Approuve: 'text-green-600',
        Approuvé: 'text-green-600',
        Rejete: 'text-red-600',
        Rejeté: 'text-red-600',
        Expire: 'text-gray-600',
        Expiré: 'text-gray-600',
    };
    return statusClasses[status] || 'text-gray-600';
};
</script>

<template>
    <Head :title="`Devis #${quote.quote_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Devis #{{ quote.quote_number }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('quotes.index')"
                        class="btn btn-ghost btn-sm"
                        >&larr; Retour</Link
                    >
                    <button
                        v-if="!isConverted"
                        @click="confirmingQuoteDeletion = true"
                        class="btn btn-error btn-sm"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h3
                                    class="mb-2 text-lg font-semibold text-gray-800"
                                >
                                    Client
                                </h3>
                                <p class="text-gray-600">
                                    <Link
                                        :href="
                                            route(
                                                'clients.show',
                                                quote.client.id,
                                            )
                                        "
                                        class="text-blue-500 hover:underline"
                                    >
                                        {{ quote.client.name }}
                                    </Link>
                                </p>
                                <p class="text-gray-600">
                                    {{ quote.client.company }}
                                </p>
                                <p class="text-gray-600">
                                    {{ quote.client.address }}
                                </p>
                                <p
                                    class="text-gray-600"
                                    v-if="quote.client.siret"
                                >
                                    SIRET: {{ quote.client.siret }}
                                </p>
                            </div>
                            <div>
                                <h3
                                    class="mb-2 text-lg font-semibold text-gray-800"
                                >
                                    Détails du devis
                                </h3>
                                <p class="text-gray-600">
                                    <strong>Numéro:</strong> #{{
                                        quote.quote_number
                                    }}
                                </p>
                                <p class="text-gray-600">
                                    <strong>Date:</strong>
                                    {{ formatDate(quote.created_at) }}
                                </p>
                                <p class="text-gray-600">
                                    <strong>Statut:</strong>
                                    <span
                                        :class="getStatusClass(quote.status)"
                                        >{{ quote.status }}</span
                                    >
                                </p>
                                <p
                                    v-if="isConverted"
                                    class="mt-1 text-sm font-medium text-blue-600"
                                >
                                    Converti en facture
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="isConverted"
                            class="mb-6 rounded border border-blue-400 bg-blue-50 p-4 text-blue-700"
                        >
                            Ce devis a déjà été converti en facture. Il ne peut
                            plus être modifié.
                        </div>

                        <div class="mt-6">
                            <h3
                                class="mb-2 text-lg font-semibold text-gray-800"
                            >
                                Postes du devis
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="table w-full">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-right">Quantité</th>
                                            <th class="text-right">
                                                Prix Unitaire
                                            </th>
                                            <th class="text-right">TVA (%)</th>
                                            <th class="text-right">
                                                Total TTC
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in quote.line_items"
                                            :key="item.id"
                                        >
                                            <td>{{ item.description }}</td>
                                            <td class="text-right">
                                                {{ item.quantity }}
                                            </td>
                                            <td class="text-right">
                                                {{
                                                    formatCurrency(
                                                        item.unit_price,
                                                    )
                                                }}
                                            </td>
                                            <td class="text-right">
                                                {{ item.vat_rate }}%
                                            </td>
                                            <td class="text-right">
                                                {{ formatCurrency(item.total) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <div class="w-full max-w-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-600"
                                        >Sous-total HT:</span
                                    >
                                    <span class="font-semibold text-gray-800">{{
                                        formatCurrency(quote.subtotal)
                                    }}</span>
                                </div>
                                <div class="mt-2 flex justify-between">
                                    <span class="text-gray-600"
                                        >Montant TVA:</span
                                    >
                                    <span class="font-semibold text-gray-800">{{
                                        formatCurrency(quote.vat_amount)
                                    }}</span>
                                </div>
                                <div
                                    class="mt-4 flex justify-between border-t pt-2"
                                >
                                    <span
                                        class="text-lg font-bold text-gray-800"
                                        >Total TTC:</span
                                    >
                                    <span
                                        class="text-lg font-bold text-gray-800"
                                        >{{ formatCurrency(quote.total) }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-center space-x-4">
                            <button
                                @click="duplicateQuote"
                                class="btn btn-info"
                            >
                                Dupliquer
                            </button>
                            <button
                                @click="generatePdf"
                                class="btn btn-secondary"
                            >
                                Générer PDF
                            </button>
                            <a
                                :href="route('quotes.download', quote.id)"
                                class="btn btn-primary"
                                >Télécharger PDF</a
                            >
                            <Link
                                v-if="isEditable"
                                :href="route('quotes.edit', quote.id)"
                                class="btn btn-warning"
                                >Modifier</Link
                            >
                            <button
                                v-if="canConvertToInvoice"
                                @click="convertToInvoice"
                                class="btn btn-success"
                            >
                                Convertir en facture
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="confirmingQuoteDeletion"
            title="Supprimer le devis"
            :message="`Êtes-vous sûr de vouloir supprimer le devis ${quote.quote_number} ? Cette action est irréversible.`"
            confirm-text="Supprimer"
            @confirm="deleteQuote"
            @cancel="confirmingQuoteDeletion = false"
        />
    </AuthenticatedLayout>
</template>
