<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    documentType: String, // 'quote' or 'invoice'
    show: Boolean,
});

const emit = defineEmits(['close', 'load']);

const templates = ref([]);
const loading = ref(false);
const deletingId = ref(null);

const fetchTemplates = async () => {
    loading.value = true;
    try {
        const response = await fetch(route('document-templates.index', { type: props.documentType }));
        const data = await response.json();
        templates.value = data;
    } catch (error) {
        console.error('Error fetching templates:', error);
    } finally {
        loading.value = false;
    }
};

const loadTemplate = (template) => {
    emit('load', template);
};

const deleteTemplate = async (templateId) => {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce template ?')) return;

    deletingId.value = templateId;
    try {
        await fetch(route('document-templates.destroy', templateId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });
        templates.value = templates.value.filter(t => t.id !== templateId);
    } catch (error) {
        console.error('Error deleting template:', error);
        alert('Erreur lors de la suppression du template');
    } finally {
        deletingId.value = null;
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(() => {
    if (props.show) {
        fetchTemplates();
    }
});

// Watch for show prop changes
const close = () => {
    emit('close');
};

// Fetch templates when modal opens
const handleOpen = () => {
    if (props.show) {
        fetchTemplates();
    }
};

// Watch show prop
import { watch } from 'vue';
watch(() => props.show, (newVal) => {
    if (newVal) {
        fetchTemplates();
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="close"></div>

                    <!-- Modal -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden transform transition-all">
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Mes Templates
                            </h3>
                            <button
                                @click="close"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6 overflow-y-auto max-h-[calc(80vh-140px)]">
                            <div v-if="loading" class="flex items-center justify-center py-12">
                                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>

                            <div v-else-if="templates.length === 0" class="text-center py-12">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">Aucun template sauvegardé</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Créez votre premier template en cliquant sur "Sauvegarder comme template"</p>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="template in templates"
                                    :key="template.id"
                                    class="group relative bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-600 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all hover:shadow-lg"
                                >
                                    <!-- Template Preview/Icon -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                                {{ template.name }}
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(template.created_at) }}
                                            </p>
                                        </div>
                                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Stats -->
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        {{ template.blocks?.length || 0 }} blocs
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex gap-2">
                                        <button
                                            @click="loadTemplate(template)"
                                            class="flex-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                            Charger
                                        </button>
                                        <button
                                            @click="deleteTemplate(template.id)"
                                            :disabled="deletingId === template.id"
                                            class="px-3 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg transition-colors"
                                            :class="{ 'opacity-50 cursor-not-allowed': deletingId === template.id }"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95);
    opacity: 0;
}
</style>
