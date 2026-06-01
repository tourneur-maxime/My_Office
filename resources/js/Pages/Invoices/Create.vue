<script setup>
import DocumentPreview from '@/Components/Branding/DocumentPreview.vue';
import TemplateSelector from '@/Components/Branding/TemplateSelector.vue';
import HelpTooltip from '@/Components/Common/HelpTooltip.vue';
import ComplianceIndicator from '@/Components/Invoices/ComplianceIndicator.vue';
import LineItemsEditor from '@/Components/Invoices/LineItemsEditor.vue';
import TextInput from '@/Components/TextInput.vue';
import { useInvoiceForm } from '@/Composables/useInvoiceForm';
import { useSiretValidator } from '@/Composables/useSiretValidator';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useComplianceStore } from '@/Stores/complianceStore';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    client: Object,
    clients: Array,
    templates: Array,
    companyProfile: Object,
});

const complianceStore = useComplianceStore();
const {
    form,
    addLineItem,
    removeLineItem,
    moveLineItem,
    updateLineItem,
    calculateSubtotal,
    calculateVatAmount,
    calculateGrandTotal,
} = useInvoiceForm();

const { siretError, validateSiret } = useSiretValidator();

const selectedBranding = ref(props.companyProfile);

const onTemplateChange = (template) => {
    selectedBranding.value = template || props.companyProfile;
};

// Initialize form with client if provided
onMounted(() => {
    if (props.client) {
        form.client_id = props.client.id;
    }
    form.template_id = props.companyProfile?.default_template_id || null;

    // Initial validation
    validateCompliance();
});

// Watch for client changes to validate SIRET
watch(
    () => form.client_id,
    (newClientId) => {
        const selectedClient =
            props.clients?.find((c) => c.id === newClientId) || props.client;
        if (selectedClient) {
            validateSiret(selectedClient.siret);
        }
    },
);

// Real-time compliance validation
const validateCompliance = () => {
    const invoiceData = {
        ...form.data(),
        company: props.companyProfile,
        client:
            props.clients?.find((c) => c.id === form.client_id) || props.client,
    };
    complianceStore.validateDataDebounced(invoiceData);
};

// Watch all form changes for compliance
watch(
    () => form.data(),
    () => {
        validateCompliance();
    },
    { deep: true },
);

const submit = () => {
    if (props.client) {
        form.post(route('invoices.store', props.client.id));
    } else {
        form.post(route('invoices.store'));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head
            :title="
                client
                    ? `Nouvelle facture - ${client.name}`
                    : 'Nouvelle facture'
            "
        />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Branding Selection -->
                        <div
                            class="overflow-hidden liquid-glass shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3
                                    class="mb-4 text-lg font-medium text-gray-900 dark:text-white"
                                >
                                    Modèle et Branding
                                </h3>
                                <TemplateSelector
                                    v-model="form.template_id"
                                    :templates="templates"
                                    :company-default="companyProfile"
                                    @change="onTemplateChange"
                                />
                            </div>
                        </div>

                        <div
                            class="overflow-hidden liquid-glass shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h2 class="mb-4 text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{
                                        client
                                            ? `Créer une facture pour ${client.name}`
                                            : 'Créer une facture'
                                    }}
                                </h2>

                                <form @submit.prevent="submit">
                                    <!-- Client Selection -->
                                    <div v-if="!client" class="mb-6">
                                        <label
                                            for="client_id"
                                            class="glass-label block flex items-center gap-2"
                                            >Sélectionner un client
                                            <HelpTooltip
                                                text="Sélectionnez un client existant pour pré-remplir les informations de facturation."
                                                position="tooltip-right"
                                            />
                                        </label>
                                        <select
                                            v-model="form.client_id"
                                            id="client_id"
                                            class="glass-select mt-1 block w-full"
                                            required
                                        >
                                            <option value="" disabled>
                                                -- Choisir un client --
                                            </option>
                                            <option
                                                v-for="c in clients"
                                                :key="c.id"
                                                :value="c.id"
                                            >
                                                {{ c.name }}
                                                {{
                                                    c.company
                                                        ? `(${c.company})`
                                                        : ''
                                                }}
                                            </option>
                                        </select>
                                        <div
                                            v-if="siretError"
                                            class="mt-1 flex items-center gap-1 text-sm text-amber-600"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                />
                                            </svg>
                                            {{ siretError }} (Impacte la
                                            conformité Factur-X)
                                        </div>
                                        <div
                                            v-if="form.errors.client_id"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.client_id }}
                                        </div>
                                    </div>

                                    <!-- Line Items -->
                                    <div class="mb-6">
                                        <h3
                                            class="mb-3 flex items-center gap-2 text-lg font-medium"
                                        >
                                            Postes de la facture
                                            <HelpTooltip
                                                text="Détaillez ici les produits ou services facturés. Chaque ligne doit inclure une description, une quantité et un prix unitaire."
                                                position="tooltip-right"
                                            />
                                        </h3>
                                        <LineItemsEditor
                                            :items="form.line_items"
                                            :errors="form.errors"
                                            @add="addLineItem"
                                            @remove="removeLineItem"
                                            @move="moveLineItem"
                                            @update="updateLineItem"
                                        />
                                    </div>

                                    <!-- Dates -->
                                    <div
                                        class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2"
                                    >
                                        <div>
                                            <label
                                                class="glass-label block flex items-center gap-2"
                                            >
                                                Date de facturation
                                                <HelpTooltip
                                                    text="La date légale d'émission de la facture. Elle déclenche le délai de paiement."
                                                    position="tooltip-right"
                                                />
                                            </label>
                                            <TextInput
                                                type="date"
                                                v-model="form.date"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="glass-label block flex items-center gap-2"
                                            >
                                                Date d'échéance
                                                <HelpTooltip
                                                    text="Date limite à laquelle le paiement doit être reçu. Pour les micro-entrepreneurs, le délai maximum est généralement de 30 ou 60 jours."
                                                    position="tooltip-left"
                                                />
                                            </label>
                                            <TextInput
                                                type="date"
                                                v-model="form.due_date"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                    </div>

                                    <!-- Totals -->
                                    <div
                                        class="mt-6 border-t border-gray-100 pt-6"
                                    >
                                        <div
                                            class="flex flex-col items-end space-y-3"
                                        >
                                            <div
                                                class="flex w-64 justify-between text-sm text-gray-600"
                                            >
                                                <span>Sous-total HT</span>
                                                <span
                                                    >{{
                                                        calculateSubtotal.toFixed(
                                                            2,
                                                        )
                                                    }}
                                                    €</span
                                                >
                                            </div>
                                            <div
                                                class="flex w-64 justify-between text-sm text-gray-600"
                                            >
                                                <span>Total TVA</span>
                                                <span
                                                    >{{
                                                        calculateVatAmount.toFixed(
                                                            2,
                                                        )
                                                    }}
                                                    €</span
                                                >
                                            </div>
                                            <div
                                                class="flex w-64 items-center justify-between border-t border-gray-200 dark:border-apple-dark-border pt-3 text-xl font-black text-gray-900 dark:text-white"
                                            >
                                                <span
                                                    class="flex items-center gap-2"
                                                    >TOTAL TTC
                                                    <HelpTooltip
                                                        text="Le montant total toutes taxes comprises à régler par votre client."
                                                        position="tooltip-left"
                                                    />
                                                </span>
                                                <span class="text-blue-600"
                                                    >{{
                                                        calculateGrandTotal.toFixed(
                                                            2,
                                                        )
                                                    }}
                                                    €</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit -->
                                    <div
                                        class="mt-10 flex items-center justify-end gap-4 border-t border-gray-100 pt-6"
                                    >
                                        <Link
                                            :href="
                                                client
                                                    ? route(
                                                          'clients.show',
                                                          client.id,
                                                      )
                                                    : route('invoices.index')
                                            "
                                            class="btn btn-ghost btn-sm font-normal normal-case text-gray-500"
                                        >
                                            Annuler
                                        </Link>

                                        <div class="flex flex-col items-end">
                                            <button
                                                type="submit"
                                                :disabled="
                                                    form.processing ||
                                                    !complianceStore.isReadyForGeneration
                                                "
                                                class="shadow-primary/20 btn btn-primary px-8 shadow-lg"
                                            >
                                                <span
                                                    v-if="form.processing"
                                                    class="loading loading-spinner loading-xs"
                                                ></span>
                                                Créer la facture
                                            </button>
                                            <p
                                                v-if="
                                                    !complianceStore.isReadyForGeneration &&
                                                    !complianceStore.isValidating
                                                "
                                                class="mt-2 text-[10px] font-bold uppercase tracking-widest text-error"
                                            >
                                                Conformité Factur-X requise
                                            </p>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6 lg:col-span-1">
                        <ComplianceIndicator :invoice="null" />

                        <div
                            class="overflow-hidden liquid-glass shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3
                                    class="mb-4 text-sm font-bold uppercase tracking-widest text-gray-400 dark:text-apple-dark-secondary"
                                >
                                    Aperçu du Branding
                                </h3>
                                <div
                                    class="origin-top scale-75 rounded border border-gray-100 bg-gray-50/50 p-2 shadow-inner"
                                >
                                    <DocumentPreview
                                        :logo="
                                            selectedBranding?.logo_path
                                                ? `/storage/${selectedBranding.logo_path}`
                                                : null
                                        "
                                        :logo-size="
                                            selectedBranding?.logo_size || 100
                                        "
                                        :logo-position="
                                            selectedBranding?.logo_position ||
                                            'left'
                                        "
                                        :primary-color="
                                            selectedBranding?.primary_color ||
                                            '#3B82F6'
                                        "
                                        :secondary-color="
                                            selectedBranding?.secondary_color ||
                                            '#1E40AF'
                                        "
                                        :font-family="
                                            selectedBranding?.font_family ||
                                            'sans-serif'
                                        "
                                        :company-name="companyProfile?.name"
                                        :company-address="
                                            companyProfile?.address
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
