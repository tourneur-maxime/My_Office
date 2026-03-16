<script setup>
import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import SearchClients from '@/Components/SearchClients.vue';
import { useToast } from '@/Composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    prospects: Object,
    filters: Object,
});

const { success, error } = useToast();
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');

const toggleFavorite = (prospect) => {
    router.post(route('clients.toggleFavorite', prospect.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            success(prospect.is_favorite ? 'Retiré des favoris' : 'Ajouté aux favoris');
        },
        onError: () => {
            error('Une erreur est survenue');
        }
    });
};

const applyFilter = () => {
    router.get(
        route('clients.index'),
        { search: searchQuery.value, status: statusFilter.value },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const handleSearchUpdate = (searchData) => {
    searchQuery.value = searchData.q;
    statusFilter.value = searchData.type === 'all' ? 'all' : searchData.type;
    applyFilter();
};

const handleFilterChange = (newFilter) => {
    statusFilter.value = newFilter;
    applyFilter();
};

const showConfirmDeleteModal = ref(false);
const prospectToDelete = ref(null);

const confirmProspectDeletion = (prospect) => {
    prospectToDelete.value = prospect;
    showConfirmDeleteModal.value = true;
};

const deleteProspect = () => {
    if (prospectToDelete.value) {
        router.delete(route('clients.destroy', prospectToDelete.value.id), {
            onSuccess: () => {
                showConfirmDeleteModal.value = false;
                prospectToDelete.value = null;
                success('Client/Prospect supprimé avec succès.');
            },
            onError: (errors) => {
                showConfirmDeleteModal.value = false;
                prospectToDelete.value = null;
                error('Une erreur est survenue lors de la suppression.');
            },
        });
    }
};

const closeModal = () => {
    showConfirmDeleteModal.value = false;
    prospectToDelete.value = null;
};
</script>

<template>
    <Head title="Clients et Prospects" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Clients et Prospects
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('clients.create', { status: 'client' })"
                        class="btn btn-primary btn-sm"
                    >
                        + Ajouter un client
                    </Link>
                    <Link
                        :href="route('clients.create')"
                        class="btn btn-ghost btn-sm"
                    >
                        + Ajouter un prospect
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Search & Filters -->
                <div class="mb-6 liquid-glass p-6">
                    <SearchClients
                        :initial-search="props.filters.search || ''"
                        :initial-filter="props.filters.status || 'all'"
                        @search="handleSearchUpdate"
                        @filter-change="handleFilterChange"
                    />
                </div>

                <!-- Table -->
                <div class="overflow-hidden liquid-glass">
                    <div class="p-6 text-gray-900 dark:text-white">
                        <div v-if="prospects.data.length">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-[hsl(var(--bg-surface))]/50 dark:bg-[hsl(var(--bg-surface))]/30">
                                        <tr>
                                            <th class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                </svg>
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Nom</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Alias</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Entreprise</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Statut</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-[hsl(var(--text-muted))]">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[hsl(var(--border))] bg-transparent">
                                        <tr v-for="prospect in prospects.data" :key="prospect.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                            <td class="whitespace-nowrap px-3 py-4 text-center">
                                                <button
                                                    @click="toggleFavorite(prospect)"
                                                    class="text-yellow-500 hover:text-yellow-600 dark:hover:text-yellow-400 transition-colors"
                                                    :title="prospect.is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :fill="prospect.is_favorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                    </svg>
                                                </button>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-[hsl(var(--text-main))]">{{ prospect.name }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-[hsl(var(--text-muted))] italic">{{ prospect.alias || '-' }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-[hsl(var(--text-muted))]">{{ prospect.company || '-' }}</td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-[hsl(var(--text-muted))]">{{ prospect.email }}</td>
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <span
                                                    :class="[
                                                        'inline-flex rounded-full px-2 text-xs font-semibold leading-5',
                                                        prospect.status === 'client' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800'
                                                    ]"
                                                >
                                                    {{ prospect.status === 'client' ? 'Client' : 'Prospect' }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                <Link :href="route('clients.edit', prospect.id)" class="text-blue-600 hover:text-blue-900 mr-4">Modifier</Link>
                                                <button @click="confirmProspectDeletion(prospect)" class="text-red-600 hover:text-red-900">Supprimer</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination standard -->
                            <div v-if="prospects.links.length > 3" class="mt-6 flex justify-center">
                                <template v-for="(link, key) in prospects.links" :key="key">
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
                            Aucun client ou prospect trouvé.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="showConfirmDeleteModal"
            title="Supprimer le prospect"
            :message="`Êtes-vous sûr de vouloir supprimer ${prospectToDelete?.name} ? Cette action est irréversible et toutes les données associées seront supprimées.`"
            confirm-text="Supprimer"
            @confirm="deleteProspect"
            @cancel="closeModal"
        />
    </AuthenticatedLayout>
</template>