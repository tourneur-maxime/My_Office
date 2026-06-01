<script setup>
import DocumentPreview from '@/Components/Branding/DocumentPreview.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    companyProfile: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const logoForm = useForm({
    logo: null,
});

const brandingForm = useForm({
    logo_path: props.companyProfile?.logo_path || null,
    logo_size: props.companyProfile?.logo_size || 100,
    logo_position: props.companyProfile?.logo_position || 'left',
    primary_color: props.companyProfile?.primary_color || '#3B82F6',
    secondary_color: props.companyProfile?.secondary_color || '#1E40AF',
    font_family: props.companyProfile?.font_family || 'sans-serif',
});

const templateForm = useForm({
    name: '',
    logo_path: '',
    logo_size: 100,
    logo_position: 'left',
    primary_color: '',
    secondary_color: '',
    font_family: '',
    is_default: false,
});

const showSaveTemplateModal = ref(false);

const logoPreview = ref(null);
const isDragging = ref(false);

const currentLogoUrl = computed(() => {
    if (props.companyProfile?.logo_path) {
        return `/storage/${props.companyProfile.logo_path}`;
    }
    return null;
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        logoForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const uploadLogo = () => {
    logoForm.post(route('logo.store'), {
        forceFormData: true,
        onSuccess: (page) => {
            // Update the branding form with the new logo path from props
            if (page.props.companyProfile?.logo_path) {
                brandingForm.logo_path = page.props.companyProfile.logo_path;
            }
            logoForm.reset();
            logoPreview.value = null;
        },
    });
};

const deleteLogo = () => {
    if (confirm('Etes-vous sur de vouloir supprimer le logo ?')) {
        logoForm.delete(route('logo.destroy'));
    }
};

const saveBranding = () => {
    brandingForm.patch(route('settings.branding.update'));
};

const openSaveTemplateModal = () => {
    templateForm.logo_path =
        props.companyProfile?.logo_path || brandingForm.logo_path;
    templateForm.logo_size = brandingForm.logo_size;
    templateForm.logo_position = brandingForm.logo_position;
    templateForm.primary_color = brandingForm.primary_color;
    templateForm.secondary_color = brandingForm.secondary_color;
    templateForm.font_family = brandingForm.font_family;
    showSaveTemplateModal.value = true;
};

const closeSaveTemplateModal = () => {
    showSaveTemplateModal.value = false;
    templateForm.reset();
};

const saveTemplate = () => {
    templateForm.post(route('settings.branding.saveTemplate'), {
        onSuccess: () => {
            closeSaveTemplateModal();
        },
    });
};

const resetBranding = () => {
    if (
        confirm(
            'Etes-vous sur de vouloir reinitialiser tous les parametres de marque aux valeurs par defaut ? Cette action supprimera egalement le logo.',
        )
    ) {
        brandingForm.delete(route('settings.branding.reset'), {
            onSuccess: () => {
                logoPreview.value = null;
            },
        });
    }
};

const fontOptions = [
    { value: 'sans-serif', label: 'Sans Serif (par defaut)' },
    { value: 'serif', label: 'Serif' },
    { value: 'monospace', label: 'Monospace' },
    { value: 'Arial, sans-serif', label: 'Arial' },
    { value: 'Georgia, serif', label: 'Georgia' },
    { value: 'Verdana, sans-serif', label: 'Verdana' },
];
</script>

<template>
    <Head title="Personnalisation de la marque" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Personnalisation de la marque
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Logo Section -->
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                >
                    <h3 class="mb-4 text-lg font-medium text-gray-900">
                        Logo de l'entreprise
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Current Logo Display -->
                        <div>
                            <h4 class="mb-2 text-sm font-medium text-gray-700">
                                Logo actuel
                            </h4>
                            <div
                                v-if="currentLogoUrl"
                                class="rounded-lg border bg-gray-50 p-4"
                            >
                                <img
                                    :src="currentLogoUrl"
                                    alt="Logo actuel"
                                    class="mx-auto max-h-48 max-w-full object-contain"
                                />
                                <button
                                    @click="deleteLogo"
                                    type="button"
                                    class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-700 focus:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-900"
                                >
                                    Supprimer le logo
                                </button>
                            </div>
                            <div
                                v-else
                                class="rounded-lg border-2 border-dashed border-gray-300 p-8 text-center text-gray-500"
                            >
                                Aucun logo telecharge
                            </div>
                        </div>

                        <!-- Upload Section -->
                        <div>
                            <h4 class="mb-2 text-sm font-medium text-gray-700">
                                Telecharger un nouveau logo
                            </h4>
                            <div
                                @drop="handleDrop"
                                @dragover="handleDragOver"
                                @dragleave="handleDragLeave"
                                :class="[
                                    'rounded-lg border-2 border-dashed p-6 text-center transition-colors',
                                    isDragging
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-300 hover:border-gray-400',
                                ]"
                            >
                                <div v-if="logoPreview" class="mb-4">
                                    <img
                                        :src="logoPreview"
                                        alt="Apercu"
                                        class="mx-auto max-h-32 max-w-full object-contain"
                                    />
                                </div>
                                <div v-else>
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-400"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 48 48"
                                    >
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Glissez-deposez un fichier ou
                                    </p>
                                </div>
                                <label
                                    class="mt-2 inline-flex cursor-pointer items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25"
                                >
                                    <span>Choisir un fichier</span>
                                    <input
                                        type="file"
                                        class="hidden"
                                        accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                                        @change="handleFileChange"
                                    />
                                </label>
                                <p class="mt-2 text-xs text-gray-500">
                                    PNG, JPG, SVG ou WebP. Max 2Mo.
                                </p>
                            </div>

                            <div
                                v-if="logoForm.errors.logo"
                                class="mt-2 text-sm text-red-600"
                            >
                                {{ logoForm.errors.logo }}
                            </div>

                            <button
                                v-if="logoForm.logo"
                                @click="uploadLogo"
                                :disabled="logoForm.processing"
                                type="button"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-blue-900 disabled:opacity-25"
                            >
                                <span v-if="logoForm.processing"
                                    >Telechargement...</span
                                >
                                <span v-else>Telecharger le logo</span>
                            </button>
                        </div>
                    </div>

                    <!-- Logo Adjustments (Visible only if logo exists) -->
                    <div
                        v-if="logoPreview || companyProfile?.logo_path"
                        dusk="logo-adjustments-div"
                        class="mt-8 grid grid-cols-1 gap-6 border-t pt-6 md:grid-cols-2"
                    >
                        <div>
                            <label
                                for="logo_size"
                                class="block text-sm font-medium text-gray-700"
                                >Taille du logo ({{
                                    brandingForm.logo_size
                                }}px)</label
                            >
                            <input
                                type="range"
                                id="logo_size"
                                v-model.number="brandingForm.logo_size"
                                min="50"
                                max="300"
                                class="mt-1 block h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 accent-indigo-600"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Position du logo</label
                            >
                            <div class="mt-2 flex space-x-2">
                                <button
                                    v-for="pos in ['left', 'center', 'right']"
                                    :key="pos"
                                    type="button"
                                    :dusk="`logo-position-${pos}-button`"
                                    @click="brandingForm.logo_position = pos"
                                    :class="[
                                        'rounded-md border px-4 py-2 text-xs font-semibold uppercase transition-colors',
                                        brandingForm.logo_position === pos
                                            ? 'border-transparent bg-indigo-600 text-white'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    ]"
                                >
                                    {{
                                        pos === 'left'
                                            ? 'Gauche'
                                            : pos === 'center'
                                              ? 'Centre'
                                              : 'Droite'
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colors & Preview Section -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Controls -->
                    <div class="space-y-6 lg:col-span-1">
                        <div
                            class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                        >
                            <h3 class="mb-4 text-lg font-medium text-gray-900">
                                Couleurs et police
                            </h3>

                            <form
                                @submit.prevent="saveBranding"
                                class="space-y-6"
                            >
                                <div>
                                    <label
                                        for="primary_color"
                                        class="block text-sm font-medium text-gray-700"
                                        >Couleur principale</label
                                    >
                                    <div
                                        class="mt-1 flex items-center space-x-3"
                                    >
                                        <input
                                            type="color"
                                            id="primary_color"
                                            v-model="brandingForm.primary_color"
                                            class="h-10 w-20 cursor-pointer rounded border border-gray-300"
                                        />
                                        <input
                                            type="text"
                                            dusk="primary-color-text"
                                            v-model="brandingForm.primary_color"
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="#3B82F6"
                                            pattern="^#[0-9A-Fa-f]{6}$"
                                            title="Format Hexadécimal : #RRGGBB"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="secondary_color"
                                        class="block text-sm font-medium text-gray-700"
                                        >Couleur secondaire</label
                                    >
                                    <div
                                        class="mt-1 flex items-center space-x-3"
                                    >
                                        <input
                                            type="color"
                                            id="secondary_color"
                                            v-model="
                                                brandingForm.secondary_color
                                            "
                                            class="h-10 w-20 cursor-pointer rounded border border-gray-300"
                                        />
                                        <input
                                            type="text"
                                            dusk="secondary-color-text"
                                            v-model="
                                                brandingForm.secondary_color
                                            "
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="#1E40AF"
                                            pattern="^#[0-9A-Fa-f]{6}$"
                                            title="Format Hexadécimal : #RRGGBB"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="font_family"
                                        class="block text-sm font-medium text-gray-700"
                                        >Police</label
                                    >
                                    <select
                                        id="font_family"
                                        dusk="font-family-select"
                                        v-model="brandingForm.font_family"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option
                                            v-for="font in fontOptions"
                                            :key="font.value"
                                            :value="font.value"
                                        >
                                            {{ font.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="space-y-3 border-t pt-4">
                                    <button
                                        type="submit"
                                        dusk="save-branding-button"
                                        :disabled="brandingForm.processing"
                                        class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-900 disabled:opacity-25"
                                    >
                                        Enregistrer les reglages
                                    </button>
                                    <button
                                        type="button"
                                        dusk="open-save-template-modal-button"
                                        @click="openSaveTemplateModal"
                                        :disabled="brandingForm.processing"
                                        class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25"
                                    >
                                        Sauvegarder comme modele
                                    </button>
                                    <button
                                        type="button"
                                        dusk="reset-branding-button"
                                        @click="resetBranding"
                                        :disabled="brandingForm.processing"
                                        class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-700 focus:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-900 disabled:opacity-25"
                                    >
                                        Reinitialiser
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Real-time Document Preview -->
                    <div class="lg:col-span-2">
                        <div class="sticky top-6">
                            <div
                                class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                            >
                                <h3
                                    class="mb-4 text-lg font-medium text-gray-900"
                                >
                                    Aperçu en temps réel
                                </h3>
                                <DocumentPreview
                                    dusk="document-preview"
                                    :logo="logoPreview || currentLogoUrl"
                                    :logoSize="brandingForm.logo_size"
                                    :logoPosition="brandingForm.logo_position"
                                    :primaryColor="brandingForm.primary_color"
                                    :secondaryColor="
                                        brandingForm.secondary_color
                                    "
                                    :fontFamily="brandingForm.font_family"
                                    :companyName="companyProfile?.name"
                                    :companyAddress="companyProfile?.address"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showSaveTemplateModal" @close="closeSaveTemplateModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Sauvegarder comme nouveau modèle
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Donnez un nom à votre configuration actuelle pour pouvoir la
                    réutiliser plus tard.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="template_name"
                        value="Nom du modèle"
                        class="sr-only"
                    />

                    <TextInput
                        id="template_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="templateForm.name"
                        placeholder="Ex: Modèle Créatif, Facture Hiver, etc."
                        @keyup.enter="saveTemplate"
                    />

                    <div
                        v-if="templateForm.errors.name"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ templateForm.errors.name }}
                    </div>
                </div>

                <div class="mt-6 flex items-center">
                    <input
                        type="checkbox"
                        id="is_default"
                        v-model="templateForm.is_default"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <label
                        for="is_default"
                        class="ml-2 block text-sm text-gray-900"
                    >
                        Définir comme modèle par défaut
                    </label>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeSaveTemplateModal">
                        Annuler
                    </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        dusk="save-template-confirm-button"
                        :class="{ 'opacity-25': templateForm.processing }"
                        :disabled="templateForm.processing"
                        @click="saveTemplate"
                    >
                        Sauvegarder le modèle
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
