<script setup>
import TemplateSelector from '@/Components/Branding/TemplateSelector.vue';
import LineItemsEditor from '@/Components/Quotes/LineItemsEditor.vue';
import QuotePreview from '@/Components/Quotes/QuotePreview.vue';
import { useQuoteForm } from '@/Composables/useQuoteForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useQuoteStore } from '@/Stores/quoteStore';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import Datepicker from 'vue3-datepicker';

const props = defineProps({
    quote: Object,
    quoteStatuses: Array,
    templates: {
        type: Array,
        required: true,
    },
    company: {
        type: Object,
        required: true,
    },
});

const quoteStore = useQuoteStore();

// Initialize the store with quote data from props
onMounted(() => {
    quoteStore.setLineItems(
        props.quote.line_items.map((item) => ({
            id: item.id,
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unit_price,
            vat_rate: item.vat_rate,
        })),
    );
    quoteStore.setExpiresAt(
        props.quote.expires_at ? new Date(props.quote.expires_at) : null,
    );
    quoteStore.setTemplates(props.templates);
    if (props.quote.template_id) {
        quoteStore.setTemplateId(props.quote.template_id);
    }
});

const isEditable = computed(() => {
    return (
        props.quote.status === 'Brouillon' && props.quote.invoices.length === 0
    );
});

const isConverted = computed(() => {
    return props.quote.invoices.length > 0;
});

const {
    form,
    lineItems,
    expiresAt,
    templateId,
    addLineItem,
    removeLineItem,
    moveLineItem,
    updateLineItem,
    setExpiresAt,
    setTemplateId,
    setSelectedClient,
    submit: baseSubmit,
    calculateSubtotal,
    calculateVatAmount,
    calculateGrandTotal,
} = useQuoteForm(
    props.quote.client_id,
    props.quote.template_id,
    props.quote.expires_at,
    props.quote.line_items,
);

const selectedBranding = ref(null);

const onTemplateChange = (template) => {
    selectedBranding.value = template || props.company;
};

// Override the submit function from useQuoteForm for updating an existing quote
const submit = () => {
    // Manually ensure form data reflects the latest state from the store before submission
    form.expires_at = quoteStore.expires_at;
    form.line_items = quoteStore.line_items;
    form.template_id = quoteStore.template_id;
    form.status = props.quote.status; // Current status, not updated via form.

    form.put(route('quotes.update', props.quote.id), {
        onSuccess: () => {
            // Optionally, refresh store data from server if needed, or just rely on Inertia's prop update
        },
        onError: (errors) => {
            // Inertia handles validation errors automatically via form.errors
            // We use the useErrorHandler composable for other errors if needed, but for now
            // we'll suppress the alert to avoid blocking tests and UI flow.
            console.error('Submission errors:', errors);
        },
        preserveScroll: true,
    });
};

const duplicateQuote = () => {
    router.post(route('quotes.duplicate', props.quote.id));
};

const updateQuoteStatus = (newStatus) => {
    if (!isEditable.value) return; // Only allow if editable

    const statusForm = useForm({ status: newStatus });
    statusForm.patch(route('quotes.updateStatus', props.quote.id), {
        onSuccess: () => {
            // Optionally, refresh props to reflect new status
        },
        onError: (errors) => {
            console.error('Status update errors:', errors);
        },
    });
};
</script>

<template>
    <Head :title="`Modifier le devis ${quote.quote_number}`" />hjy

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-900 dark:text-white"
                    >
                        Modifier le devis {{ quote.quote_number }} pour
                        {{ quote.client.name }}
                    </h2>
                    <div v-if="isConverted" class="mt-2 text-sm text-blue-600">
                        Ce devis a été converti en facture et ne peut plus être
                        modifié.
                    </div>
                    <div
                        v-else-if="!isEditable"
                        class="mt-2 text-sm text-red-600"
                    >
                        Ce devis ne peut pas être modifié car son statut n'est
                        pas "Brouillon".
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="duplicateQuote" class="btn btn-info btn-sm">
                        Dupliquer
                    </button>
                    <template v-if="isEditable">
                        <button
                            @click="updateQuoteStatus('Approuvé')"
                            class="btn btn-success btn-sm"
                        >
                            Approuver
                        </button>
                        <button
                            @click="updateQuoteStatus('Rejeté')"
                            class="btn btn-error btn-sm"
                        >
                            Rejeter
                        </button>
                    </template>
                    <button
                        v-if="quote.status === 'Approuvé' && !isConverted"
                        @click="
                            router.post(
                                route('quotes.convertToInvoice', quote.id),
                            )
                        "
                        class="btn btn-primary btn-sm"
                    >
                        Convertir en facture
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col gap-8 lg:flex-row">
                    <!-- Left Column: Form -->
                    <div class="space-y-8 lg:w-1/2">
                        <div
                            class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg"
                        >
                            <form @submit.prevent="submit">
                                <!-- Status and Expiration Date -->
                                <div class="mb-6 grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            for="status"
                                            class="block text-sm font-medium text-gray-700"
                                            >Statut</label
                                        >
                                        <select
                                            id="status"
                                            v-model="form.status"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :disabled="true"
                                        >
                                            <option
                                                v-for="statusOption in quoteStatuses"
                                                :key="statusOption"
                                                :value="statusOption"
                                            >
                                                {{ statusOption }}
                                            </option>
                                        </select>
                                        <div
                                            v-if="form.errors.status"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.status }}
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            for="expires_at"
                                            class="block text-sm font-medium text-gray-700"
                                            >Date de validité (optionnel)</label
                                        >
                                        <Datepicker
                                            id="expires_at"
                                            :modelValue="expiresAt"
                                            @update:modelValue="setExpiresAt"
                                            :upperLimit="new Date(2050, 11, 31)"
                                            :lowerLimit="new Date()"
                                            :clearable="true"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            :disabled="!isEditable"
                                        />
                                        <div
                                            v-if="form.errors.expires_at"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.expires_at }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Selection -->
                                <div
                                    class="mb-8 rounded-lg border bg-gray-50 p-4"
                                    :class="{
                                        'cursor-not-allowed opacity-50':
                                            !isEditable,
                                    }"
                                >
                                    <h3
                                        class="mb-4 text-lg font-medium text-gray-900"
                                    >
                                        Personnalisation
                                    </h3>
                                    <TemplateSelector
                                        :modelValue="form.template_id"
                                        @update:modelValue="setTemplateId"
                                        :templates="templates"
                                        :company-default="company"
                                        :disabled="!isEditable"
                                        @change="onTemplateChange"
                                    />
                                    <div
                                        v-if="form.errors.template_id"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.template_id }}
                                    </div>
                                </div>

                                <!-- Line Items Section -->
                                <div
                                    class="mb-8"
                                    :class="{
                                        'cursor-not-allowed opacity-50':
                                            !isEditable,
                                    }"
                                >
                                    <h3
                                        class="mb-6 border-b pb-2 text-lg font-medium text-gray-900"
                                    >
                                        Postes du devis
                                    </h3>
                                    <LineItemsEditor
                                        :items="lineItems"
                                        :errors="form.errors"
                                        @add="addLineItem"
                                        @remove="removeLineItem"
                                        @move="moveLineItem"
                                        @update="updateLineItem"
                                        :disabled="!isEditable"
                                    />
                                </div>

                                <!-- Totals Section (Mobile only, hidden on desktop as it's in the preview) -->
                                <div
                                    class="mt-8 border-t border-gray-200 pt-4 text-right lg:hidden"
                                >
                                    <p
                                        class="text-sm font-medium text-gray-700"
                                    >
                                        Sous-total:
                                        {{ calculateSubtotal.toFixed(2) }} €
                                    </p>
                                    <p
                                        class="text-sm font-medium text-gray-700"
                                    >
                                        TVA:
                                        {{ calculateVatAmount.toFixed(2) }} €
                                    </p>
                                    <p
                                        class="mt-2 text-lg font-bold text-gray-900"
                                    >
                                        Total:
                                        {{ calculateGrandTotal.toFixed(2) }} €
                                    </p>
                                </div>

                                <div class="mt-4 flex items-center justify-end">
                                    <button
                                        id="submit-quote-btn"
                                        class="ml-4 inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:border-gray-900 focus:outline-none focus:ring focus:ring-gray-300 active:bg-gray-900 disabled:opacity-25"
                                        :class="{
                                            'opacity-25':
                                                form.processing || !isEditable,
                                        }"
                                        :disabled="
                                            form.processing || !isEditable
                                        "
                                    >
                                        Mettre à jour le devis
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Preview -->
                    <div class="lg:w-1/2">
                        <div class="sticky top-8">
                            <QuotePreview
                                :company="company"
                                :client="quote.client"
                                :brandingOverride="selectedBranding"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
