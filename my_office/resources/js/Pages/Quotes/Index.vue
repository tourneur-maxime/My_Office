<script setup>
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { ref, watch } from 'vue';

const props = defineProps({
    quotes: Object,
    filters: Object,
    quoteStatuses: Array,
    prospects: Array,
});

const { success, error } = useToast();
const search = ref(props.filters.search || '');
const selectedClientId = ref(props.filters.client_id || '');
const selectedStatus = ref(props.filters.status || '');

const confirmingQuoteDeletion = ref(false);
const quoteToDelete = ref(null);

const filter = () => {
    router.get(
        route('quotes.index'),
        {
            search: search.value,
            client_id: selectedClientId.value,
            status: selectedStatus.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch(
    [search, selectedClientId, selectedStatus],
    debounce(() => {
        filter();
    }, 300),
);

const changeStatus = (quoteId, newStatus) => {
    router.patch(
        route('quotes.updateStatus', quoteId),
        { status: newStatus },
        {
            onSuccess: () => {
                success('Statut mis à jour avec succès.');
            },
            onError: () => {
                error('Erreur lors de la mise à jour du statut.');
            },
        },
    );
};

const confirmQuoteDeletion = (quote) => {
    quoteToDelete.value = quote;
    confirmingQuoteDeletion.value = true;
};

const deleteQuote = () => {
    router.delete(route('quotes.destroy', quoteToDelete.value.id), {
        onSuccess: () => {
            confirmingQuoteDeletion.value = false;
            quoteToDelete.value = null;
            success('Devis supprimé avec succès.');
        },
        onError: () => {
            error('Une erreur est survenue lors de la suppression.');
        },
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<template>
    <Head title="Gestion des Devis" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Gestion des Devis
                </h2>
                <Link
                    :href="route('quotes.create')"
                    class="btn btn-primary btn-sm"
                >
                    + Nouveau Devis
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="mb-6 liquid-glass p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <TextInput
                                v-model="search"
                                type="text"
                                placeholder="Numéro de devis..."
                                class="w-full"
                            />
                        </div>
                        <div>
                            <select
                                v-model="selectedClientId"
                                class="glass-select w-full"
                            >
                                <option value="">Tous les clients</option>
                                <option
                                    v-for="prospect in prospects"
                                    :key="prospect.id"
                                    :value="prospect.id"
                                >
                                    {{ prospect.company || prospect.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <select
                                v-model="selectedStatus"
                                class="glass-select w-full"
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
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden liquid-glass">
                    <div class="p-6 text-[hsl(var(--text-main))]">
                        <div v-if="quotes.data.length">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-[hsl(var(--bg-surface))]/50 dark:bg-[hsl(var(--bg-surface))]/30">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Numéro</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Client</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Statut</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Total TTC</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Expiration</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-apple-dark-secondary">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[hsl(var(--border))] bg-transparent">
                                        <tr v-for="quote in quotes.data" :key="quote.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">{{ quote.quote_number }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-apple-dark-secondary">
                                                {{ quote.client.company || quote.client.name }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <select
                                                    :value="quote.status"
                                                    @change="(e) => changeStatus(quote.id, e.target.value)"
                                                    class="glass-select w-full max-w-xs text-xs"
                                                    :disabled="quote.invoices.length > 0"
                                                >
                                                    <option v-for="status in quoteStatuses" :key="status" :value="status">{{ status }}</option>
                                                </select>
                                                <div v-if="quote.invoices.length > 0" class="mt-1 text-[10px] text-blue-600 dark:text-blue-400 font-semibold uppercase">Converti en facture</div>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ Number(quote.total).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' }) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-apple-dark-secondary">
                                                {{ quote.expires_at ? formatDate(quote.expires_at) : '-' }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-3">
                                                    <Link :href="route('quotes.show', quote.id)" class="text-blue-600 hover:text-blue-900">Voir</Link>
                                                    <Link 
                                                        v-if="quote.status === 'Brouillon' && quote.invoices.length === 0" 
                                                        :href="route('quotes.edit', quote.id)" 
                                                        class="text-gray-600 hover:text-gray-900"
                                                    >Modifier</Link>
                                                    <button 
                                                        @click="router.post(route('quotes.duplicate', quote.id))" 
                                                        class="text-green-600 hover:text-green-900"
                                                    >Dupliquer</button>
                                                    <button 
                                                        v-if="quote.status === 'Approuvé' && quote.invoices.length === 0" 
                                                        @click="router.post(route('quotes.convertToInvoice', quote.id))" 
                                                        class="text-blue-700 font-bold hover:underline"
                                                    >Facturer</button>
                                                    <button 
                                                        v-if="quote.invoices.length === 0" 
                                                        @click="confirmQuoteDeletion(quote)" 
                                                        class="text-red-600 hover:text-red-900"
                                                    >Supprimer</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div v-if="quotes.links.length > 3" class="mt-6 flex justify-center">
                                <template v-for="(link, key) in quotes.links" :key="key">
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
                            Aucun devis trouvé.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="confirmingQuoteDeletion"
            title="Supprimer le devis"
            :message="`Êtes-vous sûr de vouloir supprimer le devis ${quoteToDelete?.quote_number} ? Cette action est irréversible.`"
            confirm-text="Supprimer"
            @confirm="deleteQuote"
            @cancel="confirmingQuoteDeletion = false"
        />
    </AuthenticatedLayout>
</template>