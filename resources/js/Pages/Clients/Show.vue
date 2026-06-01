<script setup>
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    prospect: Object,
});

const { success, error } = useToast();
const prospectHistory = ref({
    quotes: [],
    invoices: [],
});

const confirmingProspectDeletion = ref(false);

// Filter and Sort states for Quotes
const quoteFilterStatus = ref('');
const quoteSortBy = ref('created_at');
const quoteSortDirection = ref('desc');

// Filter and Sort states for Invoices
const invoiceFilterStatus = ref('');
const invoiceSortBy = ref('created_at');
const invoiceSortDirection = ref('desc');

const quoteStatuses = ['Brouillon', 'Envoyé', 'Approuvé', 'Rejeté', 'Expiré'];
const invoiceStatuses = [
    'Brouillon',
    'Envoyé',
    'Payé',
    'Partiellement Payé',
    'En Retard',
    'Annulé',
];

const fetchProspectHistory = async () => {
    try {
        const queryParams = {
            quote_status: quoteFilterStatus.value,
            quote_sort_by: quoteSortBy.value,
            quote_sort_direction: quoteSortDirection.value,
            invoice_status: invoiceFilterStatus.value,
            invoice_sort_by: invoiceSortBy.value,
            invoice_sort_direction: invoiceSortDirection.value,
        };
        const url = route('api.prospects.history', {
            prospect: props.prospect.id,
        });
        const response = await axios.get(url, { params: queryParams });
        prospectHistory.value = response.data.data;
    } catch (err) {
        console.error('Error fetching prospect history:', err);
    }
};

watch(
    [
        quoteFilterStatus,
        quoteSortBy,
        quoteSortDirection,
        invoiceFilterStatus,
        invoiceSortBy,
        invoiceSortDirection,
    ],
    () => {
        fetchProspectHistory();
    },
);

const toggleSort = (type, field) => {
    if (type === 'quote') {
        if (quoteSortBy.value === field) {
            quoteSortDirection.value =
                quoteSortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            quoteSortBy.value = field;
            quoteSortDirection.value = 'desc';
        }
    } else if (type === 'invoice') {
        if (invoiceSortBy.value === field) {
            invoiceSortDirection.value =
                invoiceSortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            invoiceSortBy.value = field;
            invoiceSortDirection.value = 'desc';
        }
    }
};

const deleteProspect = () => {
    router.delete(route('clients.destroy', props.prospect.id), {
        onSuccess: () => {
            confirmingProspectDeletion.value = false;
            success('Prospect supprimé avec succès.');
        },
        onError: () => {
            error('Une erreur est survenue lors de la suppression.');
        },
    });
};

onMounted(() => {
    fetchProspectHistory();
});
</script>

<template>
    <Head :title="`Détails - ${prospect.business_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Détails du Client: {{ prospect.business_name }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('clients.edit', prospect.id)"
                        class="btn btn-outline btn-sm"
                        >Modifier</Link
                    >
                    <button
                        @click="confirmingProspectDeletion = true"
                        class="btn btn-error btn-sm"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="mb-4 text-lg font-semibold">
                            Informations Générales
                        </h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <p>
                                <strong>Nom de l'entreprise:</strong>
                                {{ prospect.business_name }}
                            </p>
                            <p v-if="prospect.contact_person_name">
                                <strong>Contact:</strong>
                                {{ prospect.contact_person_name }}
                            </p>
                            <p v-if="prospect.email">
                                <strong>Email:</strong> {{ prospect.email }}
                            </p>
                            <p v-if="prospect.phone">
                                <strong>Téléphone:</strong> {{ prospect.phone }}
                            </p>
                            <p v-if="prospect.address">
                                <strong>Adresse:</strong> {{ prospect.address }}
                            </p>
                            <p v-if="prospect.siret">
                                <strong>SIRET:</strong> {{ prospect.siret }}
                            </p>
                            <p v-if="prospect.vat_status">
                                <strong>Statut TVA:</strong>
                                {{ prospect.vat_status }}
                            </p>
                        </div>
                        <div v-if="prospect.notes" class="mt-4">
                            <strong>Notes:</strong>
                            <p class="mt-1 text-gray-600">
                                {{ prospect.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <h3 class="mb-4 text-lg font-semibold">
                            Historique Commercial
                        </h3>

                        <!-- Quotes Section -->
                        <div class="mb-8">
                            <h4 class="text-md mb-2 font-semibold">Devis</h4>
                            <div class="mb-4 flex space-x-4">
                                <select
                                    v-model="quoteFilterStatus"
                                    class="select-bordered select select-sm w-full max-w-xs"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option
                                        v-for="status in quoteStatuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                                <button
                                    @click="toggleSort('quote', 'created_at')"
                                    class="btn btn-ghost btn-sm"
                                >
                                    Date
                                    <span v-if="quoteSortBy === 'created_at'">{{
                                        quoteSortDirection === 'asc' ? '▲' : '▼'
                                    }}</span>
                                </button>
                                <button
                                    @click="toggleSort('quote', 'total')"
                                    class="btn btn-ghost btn-sm"
                                >
                                    Total
                                    <span v-if="quoteSortBy === 'total'">{{
                                        quoteSortDirection === 'asc' ? '▲' : '▼'
                                    }}</span>
                                </button>
                            </div>
                            <div
                                v-if="prospectHistory.quotes.length"
                                class="overflow-x-auto"
                            >
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Statut</th>
                                            <th>Total</th>
                                            <th>Expire le</th>
                                            <th>Créé le</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="quote in prospectHistory.quotes"
                                            :key="quote.id"
                                        >
                                            <td>
                                                <Link
                                                    :href="
                                                        route(
                                                            'quotes.show',
                                                            quote.id,
                                                        )
                                                    "
                                                    class="link link-primary"
                                                    >{{
                                                        quote.quote_number
                                                    }}</Link
                                                >
                                            </td>
                                            <td>{{ quote.status }}</td>
                                            <td>{{ quote.total }} €</td>
                                            <td>{{ quote.expires_at }}</td>
                                            <td>{{ quote.created_at }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-gray-500">
                                Aucun devis pour ce client.
                            </p>
                        </div>

                        <!-- Invoices Section -->
                        <div>
                            <h4 class="text-md mb-2 font-semibold">Factures</h4>
                            <div class="mb-4 flex space-x-4">
                                <select
                                    v-model="invoiceFilterStatus"
                                    class="select-bordered select select-sm w-full max-w-xs"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option
                                        v-for="status in invoiceStatuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ status }}
                                    </option>
                                </select>
                                <button
                                    @click="toggleSort('invoice', 'created_at')"
                                    class="btn btn-ghost btn-sm"
                                >
                                    Date
                                    <span
                                        v-if="invoiceSortBy === 'created_at'"
                                        >{{
                                            invoiceSortDirection === 'asc'
                                                ? '▲'
                                                : '▼'
                                        }}</span
                                    >
                                </button>
                                <button
                                    @click="toggleSort('invoice', 'total')"
                                    class="btn btn-ghost btn-sm"
                                >
                                    Total
                                    <span v-if="invoiceSortBy === 'total'">{{
                                        invoiceSortDirection === 'asc'
                                            ? '▲'
                                            : '▼'
                                    }}</span>
                                </button>
                            </div>
                            <div
                                v-if="prospectHistory.invoices.length"
                                class="overflow-x-auto"
                            >
                                <table class="table table-zebra w-full">
                                    <thead>
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Statut</th>
                                            <th>Total</th>
                                            <th>Date d'échéance</th>
                                            <th>Créée le</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="invoice in prospectHistory.invoices"
                                            :key="invoice.id"
                                        >
                                            <td>
                                                <Link
                                                    :href="
                                                        route(
                                                            'invoices.show',
                                                            invoice.id,
                                                        )
                                                    "
                                                    class="link link-primary"
                                                    >{{
                                                        invoice.invoice_number
                                                    }}</Link
                                                >
                                            </td>
                                            <td>{{ invoice.status }}</td>
                                            <td>{{ invoice.total }} €</td>
                                            <td>{{ invoice.due_date }}</td>
                                            <td>{{ invoice.created_at }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-else class="text-gray-500">
                                Aucune facture pour ce client.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="confirmingProspectDeletion"
            title="Supprimer le client"
            :message="`Êtes-vous sûr de vouloir supprimer ${prospect.business_name} ? Cette action est irréversible et supprimera tout l'historique associé.`"
            confirm-text="Supprimer"
            @confirm="deleteProspect"
            @cancel="confirmingProspectDeletion = false"
        />
    </AuthenticatedLayout>
</template>
