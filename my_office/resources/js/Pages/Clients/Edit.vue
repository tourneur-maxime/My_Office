<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useSiretValidator } from '@/Composables/useSiretValidator';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    prospect: Object,
});

const form = useForm({
    name: props.prospect.name,
    company: props.prospect.company,
    alias: props.prospect.alias || '',
    email: props.prospect.email,
    phone: props.prospect.phone,
    address: props.prospect.address,
    siret: props.prospect.siret,
    vat_number: props.prospect.vat_number || '',
    vat_status: props.prospect.vat_status,
    status: props.prospect.status,
    is_favorite: props.prospect.is_favorite || false,
});

const noteForm = useForm({
    content: '',
    id: null, // Added for editing
});

const editingNoteId = ref(null);
const editingNoteContent = ref('');

const showConvertModal = ref(false);
const { siretError, siretValid, validateSiret, clearSiretError } =
    useSiretValidator();

const submit = () => {
    // Validate SIRET before submission
    const validationResult = validateSiret(form.siret);
    if (!validationResult.valid) {
        return;
    }

    form.put(route('clients.update', props.prospect.id));
};

const openConvertModal = () => {
    showConvertModal.value = true;
};

const closeConvertModal = () => {
    showConvertModal.value = false;
};

const convertToClient = () => {
    form.put(route('clients.convertToClient', props.prospect.id), {
        onSuccess: () => {
            showConvertModal.value = false;
        },
    });
};

const addNote = () => {
    noteForm.post(route('clients.notes.store', props.prospect.id), {
        onSuccess: () => {
            noteForm.reset('content');
        },
        onError: (errors) => {
            console.error('Error adding note:', errors);
        },
    });
};

const editNote = (note) => {
    editingNoteId.value = note.id;
    editingNoteContent.value = note.content;
};

const cancelEdit = () => {
    editingNoteId.value = null;
    editingNoteContent.value = '';
};

const updateNote = (noteId) => {
    noteForm.id = noteId;
    noteForm.content = editingNoteContent.value;
    noteForm.put(
        route('clients.notes.update', {
            prospect: props.prospect.id,
            note: noteForm.id,
        }),
        {
            onSuccess: () => {
                cancelEdit();
            },
            onError: (errors) => {
                console.error('Error updating note:', errors);
            },
        },
    );
};

const deleteNote = (noteId) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette note ?')) {
        noteForm.delete(
            route('clients.notes.destroy', {
                prospect: props.prospect.id,
                note: noteId,
            }),
            {
                onSuccess: () => {
                    // Note deleted successfully
                },
                onError: (errors) => {
                    console.error('Error deleting note:', errors);
                },
            },
        );
    }
};
</script>

<template>
    <Head :title="`Modifier ${form.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Modifier un Prospect / Client
                </h2>
                <button
                    @click="form.is_favorite = !form.is_favorite; submit()"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors"
                    :class="form.is_favorite
                        ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-200 dark:hover:bg-yellow-900/50'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :fill="form.is_favorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <span class="text-sm font-medium">{{ form.is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}</span>
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden liquid-glass shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-white">
                        <!-- Convert to Client Button -->
                        <div v-if="prospect.status === 'prospect'" class="mb-6">
                            <PrimaryButton
                                @click="openConvertModal"
                                :disabled="form.processing"
                            >
                                Convertir en client
                            </PrimaryButton>
                        </div>

                        <!-- Confirmation Modal -->
                        <div
                            v-if="showConvertModal"
                            class="fixed inset-0 z-50 overflow-y-auto"
                            aria-labelledby="modal-title"
                            role="dialog"
                            aria-modal="true"
                        >
                            <div
                                class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0"
                            >
                                <!-- Background overlay -->
                                <div
                                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                    @click="closeConvertModal"
                                ></div>

                                <!-- Modal panel -->
                                <div
                                    class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                                >
                                    <div
                                        class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4"
                                    >
                                        <div class="sm:flex sm:items-start">
                                            <div
                                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10"
                                            >
                                                <svg
                                                    class="h-6 w-6 text-blue-600"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="1.5"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </div>
                                            <div
                                                class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left"
                                            >
                                                <h3
                                                    class="text-lg font-medium leading-6 text-gray-900"
                                                    id="modal-title"
                                                >
                                                    Confirmer la conversion
                                                </h3>
                                                <div class="mt-2">
                                                    <p
                                                        class="text-sm text-gray-500"
                                                    >
                                                        Êtes-vous sûr de vouloir
                                                        convertir ce prospect en
                                                        client ?
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                                    >
                                        <button
                                            type="button"
                                            @click="convertToClient"
                                            :disabled="form.processing"
                                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm"
                                        >
                                            Confirmer
                                        </button>
                                        <button
                                            type="button"
                                            @click="closeConvertModal"
                                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm"
                                        >
                                            Annuler
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Form -->
                        <form @submit.prevent="submit">
                            <div>
                                <InputLabel for="name" value="Nom" />
                                <TextInput
                                    id="name"
                                    name="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.name"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="company" value="Entreprise" />
                                <TextInput
                                    id="company"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.company"
                                    required
                                    autocomplete="company"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.company"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel
                                    for="alias"
                                    value="Pseudo / Alias (usage interne)"
                                />
                                <TextInput
                                    id="alias"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.alias"
                                    autocomplete="off"
                                    placeholder="Nom d'affichage personnalisé (optionnel)"
                                />
                                <p class="mt-1 text-xs text-[hsl(var(--text-muted))]">
                                    Ce pseudo n'apparaîtra que dans l'interface, pas sur les documents
                                </p>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.alias"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                    autocomplete="email"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.email"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="phone" value="Téléphone" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                    autocomplete="phone"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.phone"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="address" value="Adresse" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                    autocomplete="address"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.address"
                                />
                            </div>

                            <!-- SIRET Field -->
                            <div class="mt-4">
                                <InputLabel for="siret" value="Numéro SIRET" />
                                <div class="relative">
                                    <TextInput
                                        id="siret"
                                        type="text"
                                        :class="[
                                            'mt-1 block w-full',
                                            siretError ? 'border-red-500' : '',
                                            siretValid
                                                ? 'border-green-500'
                                                : '',
                                        ]"
                                        v-model="form.siret"
                                        @blur="validateSiret(form.siret)"
                                        @focus="clearSiretError"
                                        maxlength="14"
                                        pattern="\d*"
                                        placeholder="14 chiffres"
                                        autocomplete="siret"
                                    />
                                    <span
                                        v-if="siretValid"
                                        class="absolute right-3 top-3 text-green-500"
                                    >
                                        ✓
                                    </span>
                                </div>
                                <InputError
                                    class="mt-2"
                                    :message="siretError || form.errors.siret"
                                />
                            </div>

                            <!-- VAT Number -->
                            <div class="mt-4">
                                <InputLabel for="vat_number" value="N° TVA Intracommunautaire" />
                                <TextInput
                                    id="vat_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.vat_number"
                                    placeholder="FR12345678901"
                                />
                                <InputError class="mt-2" :message="form.errors.vat_number" />
                            </div>

                            <!-- VAT Status Field (Checkbox) -->
                            <div class="mt-4 flex items-center">
                                <Checkbox
                                    id="vat_status"
                                    name="vat_status"
                                    v-model:checked="form.vat_status"
                                />
                                <InputLabel
                                    for="vat_status"
                                    value="Assujetti à la TVA"
                                    class="ml-2"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.vat_status"
                                />
                            </div>

                            <div class="mt-4">
                                <InputLabel for="status" value="Statut" />
                                <select
                                    id="status"
                                    class="glass-select mt-1 block w-full"
                                    v-model="form.status"
                                    required
                                >
                                    <option value="prospect">Prospect</option>
                                    <option value="client">Client</option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.status"
                                />
                            </div>

                            <div class="mt-4 flex items-center justify-end">
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Mettre à jour
                                </PrimaryButton>
                            </div>
                        </form>

                        <!-- Notes Section -->
                        <div class="mt-8 border-t border-gray-200 dark:border-apple-dark-border pt-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Notes
                            </h3>

                            <form @submit.prevent="addNote" class="mt-4">
                                <div>
                                    <InputLabel
                                        for="note_content"
                                        value="Ajouter une note"
                                    />
                                    <textarea
                                        id="note_content"
                                        class="glass-textarea mt-1 block w-full"
                                        v-model="noteForm.content"
                                        rows="3"
                                    ></textarea>
                                    <InputError
                                        class="mt-2"
                                        :message="noteForm.errors.content"
                                    />
                                </div>
                                <div class="mt-4 flex items-center justify-end">
                                    <PrimaryButton
                                        :class="{
                                            'opacity-25': noteForm.processing,
                                        }"
                                        :disabled="noteForm.processing"
                                    >
                                        Ajouter la note
                                    </PrimaryButton>
                                </div>
                            </form>

                            <div
                                v-if="prospect.notes.length"
                                class="mt-8 space-y-4"
                            >
                                <div
                                    v-for="note in prospect.notes"
                                    :key="note.id"
                                    class="rounded-lg bg-gray-50 p-4 shadow"
                                >
                                    <template v-if="editingNoteId === note.id">
                                        <!-- Edit Note Form -->
                                        <div>
                                            <textarea
                                                class="glass-textarea mt-1 block w-full"
                                                v-model="editingNoteContent"
                                                rows="3"
                                            ></textarea>
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    noteForm.errors.content
                                                "
                                            />
                                        </div>
                                        <div
                                            class="mt-4 flex items-center justify-end space-x-2"
                                        >
                                            <SecondaryButton @click="cancelEdit"
                                                >Annuler</SecondaryButton
                                            >
                                            <PrimaryButton
                                                @click="updateNote(note.id)"
                                                :disabled="noteForm.processing"
                                            >
                                                Enregistrer
                                            </PrimaryButton>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <!-- Display Note -->
                                        <p class="text-sm text-gray-800">
                                            {{ note.content }}
                                        </p>
                                        <p class="mt-2 text-xs text-gray-500">
                                            Par {{ note.user.name }} le
                                            {{
                                                new Date(
                                                    note.created_at,
                                                ).toLocaleDateString()
                                            }}
                                            à
                                            {{
                                                new Date(
                                                    note.created_at,
                                                ).toLocaleTimeString()
                                            }}
                                        </p>
                                        <div
                                            class="mt-2 flex justify-end space-x-2"
                                        >
                                            <SecondaryButton
                                                @click="editNote(note)"
                                                >Modifier</SecondaryButton
                                            >
                                            <DangerButton
                                                @click="deleteNote(note.id)"
                                                >Supprimer</DangerButton
                                            >
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div v-else class="mt-8 text-gray-600">
                                Aucune note pour ce contact.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
