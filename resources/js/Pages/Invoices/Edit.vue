<script setup>
import DocumentPreview from '@/Components/Branding/DocumentPreview.vue';
import TemplateSelector from '@/Components/Branding/TemplateSelector.vue';
import HelpTooltip from '@/Components/Common/HelpTooltip.vue';
import ComplianceIndicator from '@/Components/Invoices/ComplianceIndicator.vue';
import LineItemsEditor from '@/Components/Invoices/LineItemsEditor.vue';
import { useInvoiceForm } from '@/Composables/useInvoiceForm';
import { useSiretValidator } from '@/Composables/useSiretValidator';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useComplianceStore } from '@/Stores/complianceStore';
import { useInvoiceStore } from '@/Stores/invoiceStore';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    templates: Array,
    companyDefault: Object,
});

const invoiceStore = useInvoiceStore();
const complianceStore = useComplianceStore();
const { siretError, validateSiret } = useSiretValidator();

const {
    form,
    lineItems,
    addLineItem,
    removeLineItem,
    moveLineItem,
    updateLineItem,
    submit,
    calculateSubtotal,
    calculateVatAmount,
    calculateGrandTotal,
} = useInvoiceForm(props.invoice.id);

// Initialize store with invoice data and pre-fill template_id
onMounted(() => {
    invoiceStore.loadInvoice(props.invoice);
    validateSiret(props.invoice.client.siret);
    validateCompliance();
    form.template_id = props.invoice.template_id;
});

const selectedBranding = ref(null); // Will be set by TemplateSelector on change

const onTemplateChange = (template) => {
    selectedBranding.value = template || props.companyDefault;
};

// Real-time compliance validation
const validateCompliance = () => {
    const invoiceData = {
        ...form.data(),
        company: props.companyDefault,
        client: props.invoice.client,
    };
    complianceStore.validateDataDebounced(invoiceData);
};

// Watch for form changes to trigger validation
watch(
    () => form.data(),
    () => {
        validateCompliance();
    },
    { deep: true },
);
</script>

<template>
    <Head :title="`Modifier la facture ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Modifier la facture {{ invoice.invoice_number }}
                </h2>
                <Link
                    :href="route('invoices.show', invoice.id)"
                    class="btn btn-ghost btn-sm"
                >
                    Annuler
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Branding Selection -->
                        <div
                            class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3
                                    class="mb-4 text-lg font-medium text-gray-900"
                                >
                                    Modèle et Branding
                                </h3>
                                <TemplateSelector
                                    v-model="form.template_id"
                                    :templates="templates"
                                    :company-default="companyDefault"
                                    @change="onTemplateChange"
                                />
                            </div>
                        </div>

                        <div
                            class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                        >
                            <div
                                class="mb-8 rounded-lg border border-blue-100 bg-blue-50/50 p-4 shadow-sm"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3
                                            class="mb-1 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-blue-800"
                                        >
                                            Client :
                                            <HelpTooltip
                                                text="Les informations du client sont verrouillées pour cette facture."
                                                position="tooltip-right"
                                            />
                                        </h3>
                                        <p
                                            class="text-lg font-medium text-blue-900"
                                        >
                                            {{ invoice.client.name }}
                                            <span
                                                v-if="invoice.client.company"
                                                class="text-sm font-normal text-blue-700 opacity-70"
                                            >
                                                ({{ invoice.client.company }})
                                            </span>
                                        </p>
                                    </div>
                                    <div
                                        v-if="siretError"
                                        class="badge badge-warning gap-1 p-3"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-3 w-3"
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
                                        {{ siretError }}
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="submit">
                                <!-- Line Items Section -->
                                <div class="mb-8">
                                    <h3
                                        class="mb-6 border-b pb-2 text-lg font-medium text-gray-900"
                                    >
                                        Postes de la facture
                                    </h3>
                                    <LineItemsEditor
                                        :items="lineItems"
                                        :errors="form.errors"
                                        @add="addLineItem"
                                        @remove="removeLineItem"
                                        @move="moveLineItem"
                                        @update="updateLineItem"
                                    />
                                </div>

                                <!-- Totals Section -->
                                <div
                                    class="mt-8 flex flex-col items-end border-t border-gray-200 pt-4"
                                >
                                    <div class="w-full max-w-xs space-y-2">
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-600"
                                                >Sous-total HT:</span
                                            >
                                            <span class="font-medium"
                                                >{{
                                                    calculateSubtotal.toFixed(2)
                                                }}
                                                €</span
                                            >
                                        </div>
                                        <div
                                            class="flex justify-between text-sm"
                                        >
                                            <span class="text-gray-600"
                                                >TVA:</span
                                            >
                                            <span class="font-medium"
                                                >{{
                                                    calculateVatAmount.toFixed(
                                                        2,
                                                    )
                                                }}
                                                €</span
                                            >
                                        </div>
                                        <div
                                            class="flex justify-between border-t pt-2 text-xl font-black"
                                        >
                                            <span>Total TTC</span>
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

                                <div
                                    class="mt-10 flex items-center justify-end gap-4 border-t border-gray-100 pt-6"
                                >
                                    <Link
                                        :href="
                                            route('invoices.show', invoice.id)
                                        "
                                        class="btn btn-ghost btn-sm font-normal normal-case text-gray-500"
                                    >
                                        Annuler
                                    </Link>
                                    <div class="flex flex-col items-end">
                                        <button
                                            type="submit"
                                            class="shadow-primary/20 btn btn-primary px-8 shadow-lg"
                                            :disabled="
                                                form.processing ||
                                                !complianceStore.isReadyForGeneration
                                            "
                                        >
                                            <span
                                                v-if="form.processing"
                                                class="loading loading-spinner loading-xs"
                                            ></span>
                                            Enregistrer les modifications
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
                    <div class="space-y-6 lg:col-span-1">
                        <ComplianceIndicator :invoice="invoice" />

                        <div
                            class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                        >
                            <div class="p-6">
                                <h3
                                    class="mb-4 text-sm font-bold uppercase tracking-widest text-gray-400"
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
                                        :company-name="companyDefault?.name"
                                        :company-address="
                                            companyDefault?.address
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
