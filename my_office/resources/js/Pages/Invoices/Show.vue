<script setup>
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import ComplianceIndicator from '@/Components/Invoices/ComplianceIndicator.vue';
import FacturXValidationReportModal from '@/Components/Invoices/FacturXValidationReportModal.vue';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useComplianceStore } from '@/Stores/complianceStore';
import { Head, Link, router } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import { ref } from 'vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const { success, error } = useToast();
const complianceStore = useComplianceStore();
const showReportModal = ref(false);
const confirmingInvoiceDeletion = ref(false);

const isGeneratingPdf = ref(false);
const pdfGenerationMessage = ref('');

const generateFacturXPdf = async () => {
    isGeneratingPdf.value = true;
    pdfGenerationMessage.value = '';
    complianceStore.reset();

    try {
        const data = await complianceStore.generateFacturX(props.invoice.id);

        if (data.success) {
            pdfGenerationMessage.value = data.message;
            success('Factur-X généré avec succès.');
        } else {
            pdfGenerationMessage.value =
                data.message || 'Erreur lors de la génération du PDF.';
            error(pdfGenerationMessage.value);
        }
    } catch (err) {
        pdfGenerationMessage.value = 'Erreur lors de la génération du PDF.';
        error(pdfGenerationMessage.value);
    } finally {
        isGeneratingPdf.value = false;
    }
};

const deleteInvoice = () => {
    router.delete(route('invoices.destroy', props.invoice.id), {
        onSuccess: () => {
            confirmingInvoiceDeletion.value = false;
            success('Facture supprimée avec succès.');
        },
        onError: () => {
            error('Une erreur est survenue lors de la suppression.');
        },
    });
};

const closeReportModal = () => {
    showReportModal.value = false;
};

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
</script>

<template>
    <Head :title="`Facture #${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Facture #{{ invoice.invoice_number }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('invoices.index')"
                        class="btn btn-ghost btn-sm"
                        >&larr; Retour</Link
                    >
                    <button
                        @click="confirmingInvoiceDeletion = true"
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
                                                invoice.client.id,
                                            )
                                        "
                                        class="text-blue-500 hover:underline"
                                    >
                                        {{ invoice.client.name }}
                                    </Link>
                                </p>
                                <p class="text-gray-600">
                                    {{ invoice.client.address }}
                                </p>
                                <p class="text-gray-600">
                                    {{ invoice.client.zip_code }}
                                    {{ invoice.client.city }}
                                </p>
                                <p
                                    class="text-gray-600"
                                    v-if="invoice.client.siret"
                                >
                                    SIRET: {{ invoice.client.siret }}
                                </p>
                            </div>
                            <div>
                                <h3
                                    class="mb-2 text-lg font-semibold text-gray-800"
                                >
                                    Détails de la facture
                                </h3>
                                <p class="text-gray-600">
                                    <strong>Numéro:</strong> #{{
                                        invoice.invoice_number
                                    }}
                                </p>
                                <p class="text-gray-600">
                                    <strong>Date:</strong>
                                    {{
                                        formatDate(
                                            invoice.issue_date ||
                                                invoice.created_at,
                                        )
                                    }}
                                </p>
                                <p class="text-gray-600">
                                    <strong>Statut:</strong>
                                    <span
                                        :class="{
                                            'text-green-600':
                                                invoice.status === 'Payé',
                                            'text-yellow-600':
                                                invoice.status === 'Brouillon',
                                            'text-blue-600':
                                                invoice.status === 'Envoyé',
                                        }"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h3
                                class="mb-2 text-lg font-semibold text-gray-800"
                            >
                                Postes de la facture
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
                                            <th class="text-right">Total HT</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in invoice.line_items"
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
                                                {{
                                                    formatCurrency(
                                                        item.quantity *
                                                            item.unit_price,
                                                    )
                                                }}
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
                                        formatCurrency(invoice.subtotal)
                                    }}</span>
                                </div>
                                <div class="mt-2 flex justify-between">
                                    <span class="text-gray-600"
                                        >Montant TVA:</span
                                    >
                                    <span class="font-semibold text-gray-800">{{
                                        formatCurrency(invoice.vat_amount)
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
                                        >{{
                                            formatCurrency(invoice.total)
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Compliance Indicator Section -->
                        <div class="mt-8 border-t pt-6">
                            <ComplianceIndicator
                                :invoice="invoice"
                            />
                        </div>

                        <div class="mt-8 flex justify-center space-x-4">
                            <a
                                :href="route('invoices.generate', invoice.id)"
                                class="btn btn-primary"
                                >Télécharger PDF</a
                            >
                            <button
                                @click="generateFacturXPdf"
                                :disabled="
                                    isGeneratingPdf || complianceStore.isLoading
                                "
                                class="btn btn-secondary"
                            >
                                <span v-if="isGeneratingPdf"
                                    >Génération...</span
                                >
                                <span v-else>Valider Factur-X</span>
                            </button>
                            <Link
                                v-if="invoice.status !== 'Brouillon'"
                                :href="route('invoices.creditNote', invoice.id)"
                                method="post"
                                as="button"
                                class="btn btn-outline"
                            >
                                Créer un avoir
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="confirmingInvoiceDeletion"
            title="Supprimer la facture"
            :message="`Êtes-vous sûr de vouloir supprimer la facture ${invoice.invoice_number} ? Cette action est irréversible et pourrait impacter la séquence de numérotation.`"
            confirm-text="Supprimer"
            @confirm="deleteInvoice"
            @cancel="confirmingInvoiceDeletion = false"
        />

        <FacturXValidationReportModal
            :show="showReportModal"
            :report="complianceStore.report"
            @close="closeReportModal"
        />
    </AuthenticatedLayout>
</template>
