<script setup>
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import TextInput from '@/Components/TextInput.vue';
import ComplianceIndicator from '@/Components/UI/ComplianceIndicator.vue';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import pickBy from 'lodash/pickBy';
import { ref, watch } from 'vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
    clients: Array,
});

const { success, error } = useToast();

const search = ref(props.filters.search || '');
const clientId = ref(props.filters.client_id || '');
const status = ref(props.filters.status || '');
const isCompliant = ref(props.filters.is_compliant || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const confirmingInvoiceDeletion = ref(false);
const invoiceToDelete = ref(null);

const filter = () => {
    router.get(
        route('invoices.index'),
        pickBy({
            search: search.value,
            client_id: clientId.value,
            status: status.value,
            is_compliant: isCompliant.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        }),
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch(
    [search, clientId, status, isCompliant, dateFrom, dateTo],
    debounce(() => {
        filter();
    }, 300),
);

const clearFilters = () => {
    search.value = '';
    clientId.value = '';
    status.value = '';
    isCompliant.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Payé':
            return 'bg-green-100 text-green-800';
        case 'Envoyé':
            return 'bg-blue-100 text-blue-800';
        case 'Brouillon':
            return 'bg-gray-100 text-gray-800';
        case 'En retard':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const confirmInvoiceDeletion = (invoice) => {
    invoiceToDelete.value = invoice;
    confirmingInvoiceDeletion.value = true;
};

const deleteInvoice = () => {
    router.delete(route('invoices.destroy', invoiceToDelete.value.id), {
        onSuccess: () => {
            confirmingInvoiceDeletion.value = false;
            invoiceToDelete.value = null;
            success('Facture supprimée avec succès.');
        },
        onError: () => {
            error('Une erreur est survenue lors de la suppression.');
        },
    });
};

const checkSequence = async () => {
    try {
        const response = await axios.get(route('invoices.check-gaps'));
        const { has_gaps, gaps } = response.data;

        if (has_gaps) {
            error('Anomalies détectées dans la séquence globale : Numéros manquants : ' + gaps.join(', '));
        } else {
            success('La séquence de numérotation est parfaitement intègre.');
        }
    } catch (err) {
        error('Impossible de vérifier la séquence pour le moment.');
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Historique des Factures" />

        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Historique des Factures
                </h2>
                <div class="flex gap-2">
                    <button @click="checkSequence" class="btn btn-secondary btn-sm">
                        Vérifier Séquence
                    </button>
                    <Link :href="route('invoices.create')" class="btn btn-primary btn-sm">
                        + Nouvelle Facture
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 liquid-glass p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un numéro de facture..."
                                class="w-full"
                            />
                        </div>
                        <div>
                            <select v-model="clientId" class="glass-select w-full">
                                <option value="">Tous les clients</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.company || client.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select v-model="status" class="glass-select w-full">
                                <option value="">Tous les statuts</option>
                                <option value="Brouillon">Brouillon</option>
                                <option value="Envoyé">Envoyé</option>
                                <option value="Payé">Payé</option>
                                <option value="Annulé">Annulé</option>
                                <option value="En retard">En retard</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow grid grid-cols-2 gap-2">
                            <div class="flex flex-col">
                                <span class="glass-label text-xs">Du</span>
                                <input type="date" v-model="dateFrom" class="glass-input" />
                            </div>
                            <div class="flex flex-col">
                                <span class="glass-label text-xs">Au</span>
                                <input type="date" v-model="dateTo" class="glass-input" />
                            </div>
                        </div>
                        <div>
                            <select v-model="isCompliant" class="glass-select w-full md:w-48">
                                <option value="">Toute conformité</option>
                                <option value="true">Conforme Factur-X</option>
                                <option value="false">Non validé</option>
                            </select>
                        </div>
                        <button @click="clearFilters" class="btn btn-ghost btn-sm">
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden liquid-glass">
                    <div class="p-6 text-[hsl(var(--text-main))]">
                        <div v-if="invoices.data.length">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-[hsl(var(--bg-surface))]/50 dark:bg-[hsl(var(--bg-surface))]/30">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Facture</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Client</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Émission</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Total TTC</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Statut</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Conformité</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[hsl(var(--border))] bg-transparent">
                                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-apple-dark-secondary">{{ invoice.client.company || invoice.client.name }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-apple-dark-secondary">{{ formatDate(invoice.issue_date || invoice.created_at) }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ Number(invoice.total).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <span :class="['inline-flex rounded-full px-2 text-xs font-semibold leading-5', getStatusClass(invoice.status)]">
                                                    {{ invoice.status }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <ComplianceIndicator :is-compliant="invoice.is_compliant" :metadata="invoice.facturx_metadata" />
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-3">
                                                    <Link :href="route('invoices.show', invoice.id)" class="text-blue-600 hover:text-blue-900">Détails</Link>
                                                    <a :href="route('invoices.generate', invoice.id)" class="text-green-600 hover:text-green-900 font-medium">PDF</a>
                                                    <button @click="confirmInvoiceDeletion(invoice)" class="text-red-600 hover:text-red-900">Supprimer</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div v-if="invoices.links.length > 3" class="mt-6 flex justify-center">
                                <template v-for="(link, key) in invoices.links" :key="key">
                                    <div v-if="link.url === null" class="mb-1 mr-1 rounded border px-4 py-2 text-sm leading-4 text-gray-400" v-html="link.label" />
                                    <Link
                                        v-else
                                        class="mb-1 mr-1 rounded border px-4 py-2 text-sm leading-4 hover:bg-white focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'bg-blue-600 text-white': link.active }"
                                        :href="link.url"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-[hsl(var(--text-muted))]">
                            Aucune facture trouvée.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="confirmingInvoiceDeletion"
            title="Supprimer la facture"
            :message="`Êtes-vous sûr de vouloir supprimer la facture ${invoiceToDelete?.invoice_number} ? Cette action est irréversible.`"
            confirm-text="Supprimer"
            @confirm="deleteInvoice"
            @cancel="confirmingInvoiceDeletion = false"
        />
    </AuthenticatedLayout>
</template>