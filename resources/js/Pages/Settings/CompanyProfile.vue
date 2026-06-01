<script setup>
import HelpTooltip from '@/Components/Common/HelpTooltip.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    companyProfile: Object,
});

const page = usePage();
const logoForm = useForm({
    logo: null,
});
const logoPreview = ref(null);
const isDragging = ref(false);

const form = useForm({
    name: props.companyProfile?.name || '',
    email: props.companyProfile?.email || '',
    phone: props.companyProfile?.phone || '',
    address: props.companyProfile?.address || '',
    zip_code: props.companyProfile?.zip_code || '',
    city: props.companyProfile?.city || '',
    siret: props.companyProfile?.siret || '',
    vat_number: props.companyProfile?.vat_number || '',
    rcs_number: props.companyProfile?.rcs_number || '',
    legal_form: props.companyProfile?.legal_form || '',
    share_capital: props.companyProfile?.share_capital ? String(props.companyProfile.share_capital) : '',
    payment_terms: props.companyProfile?.payment_terms || '',
    late_payment_penalty_rate:
        props.companyProfile?.late_payment_penalty_rate ||
        'Taux des pénalités de retard : 10%',
    is_vat_exempt: props.companyProfile?.is_vat_exempt ? true : false,
    custom_legal_mentions: props.companyProfile?.custom_legal_mentions || '',
    iban: props.companyProfile?.iban || '',
    // New fields
    bank_name: props.companyProfile?.bank_name || '',
    bic: props.companyProfile?.bic || '',
    bank_account_holder: props.companyProfile?.bank_account_holder || '',
    default_payment_terms: props.companyProfile?.default_payment_terms || '',
    default_payment_delay_days:
        props.companyProfile?.default_payment_delay_days ? String(props.companyProfile.default_payment_delay_days) : '',
});

// Real-time SIRET validation
const siretError = ref('');

watch(
    () => form.siret,
    (newSiret) => {
        const cleanSiret = newSiret.replace(/\s/g, '');
        if (cleanSiret.length > 0 && cleanSiret.length !== 14) {
            siretError.value =
                'Le numéro SIRET doit contenir exactement 14 chiffres.';
        } else if (cleanSiret.length > 0 && !/^\d+$/.test(cleanSiret)) {
            siretError.value =
                'Le numéro SIRET ne doit contenir que des chiffres.';
        } else {
            siretError.value = '';
        }
    },
);

const submit = () => {
    if (siretError.value) return;
    form.patch(route('settings.company.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Toast or notification handled by layout via flash messages usually
        },
    });
};

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
        onSuccess: () => {
            logoForm.reset();
            logoPreview.value = null;
        },
    });
};

const deleteLogo = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer le logo ?')) {
        logoForm.delete(route('logo.destroy'), {
            onSuccess: () => {
                logoPreview.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Profil Entreprise" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                Profil de l'Entreprise
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="liquid-glass p-4 shadow sm:rounded-lg sm:p-8">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                Informations Générales
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-apple-dark-secondary">
                                Mettez à jour les informations légales de votre
                                entreprise qui apparaîtront sur vos factures.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <!-- Logo Section -->
                            <div class="border-b border-gray-200 dark:border-apple-dark-border pb-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                    Logo de l'entreprise
                                </h3>
                                <p class="mt-1 mb-4 text-sm text-gray-600 dark:text-apple-dark-secondary">
                                    Le logo apparaîtra sur vos factures et devis. Formats acceptés : PNG, JPG, SVG (max 2MB).
                                </p>
                                
                                <div class="flex flex-col sm:flex-row gap-4 items-start">
                                    <!-- Logo Preview -->
                                    <div class="flex-shrink-0">
                                        <div class="h-32 w-32 rounded-lg border-2 border-gray-200 dark:border-apple-dark-border bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden p-2">
                                            <img
                                                v-if="logoPreview || currentLogoUrl"
                                                :src="logoPreview || currentLogoUrl"
                                                alt="Logo preview"
                                                class="max-h-full max-w-full object-contain"
                                            />
                                            <span v-else class="text-gray-400 dark:text-apple-dark-secondary text-sm">Aucun logo</span>
                                        </div>
                                    </div>

                                    <!-- Upload Area -->
                                    <div class="flex-1 space-y-3">
                                        <div
                                            @drop="handleDrop"
                                            @dragover="handleDragOver"
                                            @dragleave="handleDragLeave"
                                            :class="[
                                                'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
                                                isDragging 
                                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' 
                                                    : 'border-gray-300 dark:border-apple-dark-border bg-gray-50 dark:bg-white/5'
                                            ]"
                                        >
                                            <input
                                                type="file"
                                                id="logo-upload"
                                                accept="image/*"
                                                @change="handleFileChange"
                                                class="hidden"
                                            />
                                            <label for="logo-upload" class="cursor-pointer">
                                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-apple-dark-secondary mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <p class="text-sm text-gray-600 dark:text-apple-dark-secondary">
                                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">Cliquez pour télécharger</span>
                                                    ou glissez-déposez
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-apple-dark-secondary mt-1">PNG, JPG, SVG jusqu'à 2MB</p>
                                            </label>
                                        </div>

                                        <div class="flex gap-2">
                                            <button
                                                v-if="logoForm.logo"
                                                type="button"
                                                @click="uploadLogo"
                                                :disabled="logoForm.processing"
                                                class="btn btn-primary btn-sm"
                                            >
                                                <span v-if="logoForm.processing" class="loading loading-spinner loading-xs"></span>
                                                Télécharger le logo
                                            </button>
                                            <button
                                                v-if="currentLogoUrl || logoPreview"
                                                type="button"
                                                @click="deleteLogo"
                                                :disabled="logoForm.processing"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    for="name"
                                    value="Nom de l'entreprise / Raison Sociale"
                                />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="organization"
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel
                                        for="email"
                                        value="E-mail de l'entreprise"
                                    />
                                    <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="form.email"
                                        autocomplete="email"
                                    />
                                    <div
                                        v-if="form.errors.email"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="phone" value="Téléphone" />
                                    <TextInput
                                        id="phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.phone"
                                        autocomplete="tel"
                                    />
                                    <div
                                        v-if="form.errors.phone"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.phone }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    for="address"
                                    value="Adresse Complète"
                                />
                                <textarea
                                    id="address"
                                    class="glass-textarea mt-1 block w-full"
                                    v-model="form.address"
                                    required
                                    rows="3"
                                ></textarea>
                                <div
                                    v-if="form.errors.address"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel for="zip_code" value="Code postal" />
                                    <TextInput
                                        id="zip_code"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.zip_code"
                                        maxlength="10"
                                        placeholder="75001"
                                    />
                                    <div v-if="form.errors.zip_code" class="mt-1 text-sm text-red-500">{{ form.errors.zip_code }}</div>
                                </div>
                                <div>
                                    <InputLabel for="city" value="Ville" />
                                    <TextInput
                                        id="city"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.city"
                                        placeholder="Paris"
                                    />
                                    <div v-if="form.errors.city" class="mt-1 text-sm text-red-500">{{ form.errors.city }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <InputLabel for="siret" value="SIRET" />
                                        <HelpTooltip
                                            text="Identifiant unique de 14 chiffres de votre entreprise en France."
                                            position="tooltip-right"
                                        />
                                    </div>
                                    <TextInput
                                        id="siret"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.siret"
                                        required
                                    />
                                    <div
                                        v-if="siretError"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ siretError }}
                                    </div>
                                    <div
                                        v-if="form.errors.siret"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.siret }}
                                    </div>
                                </div>

                                <div>
                                    <InputLabel
                                        for="vat_number"
                                        value="Numéro de TVA Intracommunautaire"
                                    />
                                    <TextInput
                                        id="vat_number"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.vat_number"
                                    />
                                    <div
                                        v-if="form.errors.vat_number"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.vat_number }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <InputLabel
                                        for="rcs_number"
                                        value="Numéro RCS / RM"
                                    />
                                    <TextInput
                                        id="rcs_number"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.rcs_number"
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        for="legal_form"
                                        value="Forme Juridique (ex: SASU, EI)"
                                    />
                                    <TextInput
                                        id="legal_form"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.legal_form"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    for="share_capital"
                                    value="Capital Social (€)"
                                />
                                <TextInput
                                    id="share_capital"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                    v-model="form.share_capital"
                                />
                            </div>

                            <div class="border-t border-gray-200 dark:border-apple-dark-border pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Mentions Légales & Régimes
                                </h3>

                                <div class="mt-4 flex items-center gap-2">
                                    <input
                                        id="is_vat_exempt"
                                        type="checkbox"
                                        class="rounded border-gray-300 dark:border-apple-dark-border text-indigo-600 dark:text-apple-blue shadow-sm focus:ring-indigo-500 dark:focus:ring-apple-blue bg-white dark:bg-white/10"
                                        v-model="form.is_vat_exempt"
                                    />
                                    <label
                                        for="is_vat_exempt"
                                        class="block flex items-center gap-2 text-sm text-gray-900 dark:text-white"
                                    >
                                        Franchise en base de TVA (Exonération de
                                        TVA)
                                        <HelpTooltip
                                            text="Régime fiscal où vous ne collectez pas de TVA mais ne pouvez pas la déduire."
                                            position="tooltip-right"
                                        />
                                    </label>
                                </div>
                                <p class="ml-6 mt-1 text-xs text-gray-500 dark:text-apple-dark-secondary">
                                    Cochez cette case si vous êtes
                                    auto-entrepreneur ou micro-entreprise non
                                    assujetti à la TVA. La mention "TVA non
                                    applicable, art. 293 B du CGI" sera ajoutée
                                    automatiquement.
                                </p>

                                <div class="mt-4">
                                    <InputLabel
                                        for="late_payment_penalty_rate"
                                        value="Taux de pénalités de retard"
                                    />
                                    <TextInput
                                        id="late_payment_penalty_rate"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.late_payment_penalty_rate"
                                        placeholder="Ex: Taux des pénalités de retard : 10%"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-apple-dark-secondary">
                                        Mention obligatoire. Par défaut :
                                        "Indemnité forfaitaire pour frais de
                                        recouvrement : 40€. Taux des pénalités
                                        de retard : 10%." si laissé vide (via
                                        config).
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <InputLabel
                                        for="custom_legal_mentions"
                                        value="Mentions Légales Personnalisées (Optionnel)"
                                    />
                                    <textarea
                                        id="custom_legal_mentions"
                                        class="glass-textarea mt-1 block w-full"
                                        v-model="form.custom_legal_mentions"
                                        rows="2"
                                        placeholder="Autres mentions spécifiques à votre activité..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Coordonnées Bancaires -->
                            <div class="border-t border-gray-200 dark:border-apple-dark-border pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Coordonnées Bancaires
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-apple-dark-secondary">
                                    Ces informations apparaîtront sur vos
                                    factures pour permettre à vos clients de
                                    vous régler par virement.
                                </p>

                                <div
                                    class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <div>
                                        <InputLabel
                                            for="bank_name"
                                            value="Nom de la Banque"
                                        />
                                        <TextInput
                                            id="bank_name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.bank_name"
                                        />
                                        <div
                                            v-if="form.errors.bank_name"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ form.errors.bank_name }}
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel
                                            for="bank_account_holder"
                                            value="Titulaire du Compte"
                                        />
                                        <TextInput
                                            id="bank_account_holder"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.bank_account_holder"
                                            placeholder="Ex: Votre Nom ou Raison Sociale"
                                        />
                                        <div
                                            v-if="
                                                form.errors.bank_account_holder
                                            "
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{
                                                form.errors.bank_account_holder
                                            }}
                                        </div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel for="iban" value="IBAN" />
                                        <TextInput
                                            id="iban"
                                            type="text"
                                            class="mt-1 block w-full font-mono"
                                            v-model="form.iban"
                                            placeholder="FR76 ..."
                                        />
                                        <div
                                            v-if="form.errors.iban"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ form.errors.iban }}
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel
                                            for="bic"
                                            value="BIC (SWIFT)"
                                        />
                                        <TextInput
                                            id="bic"
                                            type="text"
                                            class="mt-1 block w-full font-mono"
                                            v-model="form.bic"
                                        />
                                        <div
                                            v-if="form.errors.bic"
                                            class="mt-1 text-sm text-red-500"
                                        >
                                            {{ form.errors.bic }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditions de Paiement -->
                            <div class="border-t border-gray-200 dark:border-apple-dark-border pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Conditions de Paiement par Défaut
                                </h3>

                                <div class="mt-4">
                                    <InputLabel
                                        for="default_payment_delay_days"
                                        value="Délai de paiement (en jours)"
                                    />
                                    <TextInput
                                        id="default_payment_delay_days"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full md:w-1/3"
                                        v-model="
                                            form.default_payment_delay_days
                                        "
                                    />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-apple-dark-secondary">
                                        Utilisé pour calculer la date d'échéance
                                        automatiquement (Date facture + X
                                        jours). Laissez vide pour aucun calcul
                                        automatique.
                                    </p>
                                    <div
                                        v-if="
                                            form.errors
                                                .default_payment_delay_days
                                        "
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{
                                            form.errors
                                                .default_payment_delay_days
                                        }}
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel
                                        for="default_payment_terms"
                                        value="Texte des conditions de paiement"
                                    />
                                    <textarea
                                        id="default_payment_terms"
                                        class="glass-textarea mt-1 block w-full"
                                        v-model="form.default_payment_terms"
                                        rows="2"
                                        placeholder="Ex: Paiement à réception de facture, net sans escompte."
                                    ></textarea>
                                    <div
                                        v-if="form.errors.default_payment_terms"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ form.errors.default_payment_terms }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-4 border-t border-gray-200 dark:border-apple-dark-border pt-6"
                            >
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="form.processing"
                                >
                                    Enregistrer
                                </button>

                                <transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p
                                        v-if="form.recentlySuccessful"
                                        class="text-sm text-gray-600 dark:text-apple-dark-secondary"
                                    >
                                        Enregistré.
                                    </p>
                                </transition>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
