<script setup>
import TemplateSelector from '@/Components/Branding/TemplateSelector.vue';
import HelpTooltip from '@/Components/Common/HelpTooltip.vue';
import LineItemsEditor from '@/Components/Quotes/LineItemsEditor.vue';
import QuotePreview from '@/Components/Quotes/QuotePreview.vue';
import SearchClients from '@/Components/SearchClients.vue';
import { useQuoteForm } from '@/Composables/useQuoteForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Datepicker from 'vue3-datepicker';

const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
    companyProfile: {
        type: Object,
        required: true,
    },
});

const selectedClient = ref(null);

const {
    form,
    lineItems,
    expiresAt,
    addLineItem,
    removeLineItem,
    moveLineItem,
    updateLineItem,
    setExpiresAt,
    setTemplateId,
    setSelectedClient: setQuoteFormClient,
    submit,
    calculateSubtotal,
    calculateVatAmount,
    calculateGrandTotal,
} = useQuoteForm();

const selectedBranding = ref(props.companyProfile);

const onTemplateChange = (template) => {
    selectedBranding.value = template || props.companyProfile;
};

const handleClientSelection = (event) => {
    if (event.results && event.results.length > 0) {
        selectedClient.value = event.results[0];
        setQuoteFormClient(selectedClient.value.id);
    } else {
        selectedClient.value = null;
        setQuoteFormClient(null);
    }
};

const clearSelectedClient = () => {
    selectedClient.value = null;
    setQuoteFormClient(null);
};
</script>

<template>
    <Head title="Créer un devis" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                Créer un devis
                <span v-if="selectedClient">
                    pour
                    {{ selectedClient.company || selectedClient.name }}</span
                >
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col gap-8 lg:flex-row">
                    <!-- Left Column: Form -->
                    <div class="space-y-8 lg:w-1/2">
                        <div
                            class="overflow-hidden liquid-glass p-6 shadow-sm sm:rounded-lg"
                        >
                            <!-- Client Selection -->
                            <div v-if="!selectedClient" class="mb-8">
                                <h3
                                    class="mb-4 text-lg font-medium text-gray-900 dark:text-white"
                                >
                                    Sélectionner un client/prospect
                                </h3>
                                <SearchClients
                                    @search="handleClientSelection"
                                    mode="select"
                                />
                                <div
                                    v-if="form.errors.client_id"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.client_id }}
                                </div>
                            </div>

                            <!-- Selected Client Display -->
                            <div
                                v-if="selectedClient"
                                class="relative mb-8 rounded-lg border bg-blue-50 p-4"
                            >
                                <h3 class="font-bold text-blue-800">
                                    Client sélectionné:
                                </h3>
                                <p class="text-blue-700">
                                    {{ selectedClient.name }} ({{
                                        selectedClient.company
                                    }})
                                </p>
                                <p class="text-sm text-blue-700">
                                    {{
                                        selectedClient.status === 'client'
                                            ? 'Client'
                                            : 'Prospect'
                                    }}
                                </p>
                                <button
                                    @click="clearSelectedClient"
                                    type="button"
                                    class="absolute right-2 top-2 font-bold text-blue-600 hover:text-blue-800"
                                >
                                    &times;
                                </button>
                            </div>

                            <form
                                v-if="selectedClient"
                                @submit.prevent="submit"
                            >
                                <!-- Expiration Date -->
                                <div class="mb-6">
                                    <label
                                        for="expires_at"
                                        class="glass-label block flex items-center gap-2"
                                    >
                                        Date de validité (optionnel)
                                        <HelpTooltip
                                            text="Délai pendant lequel les prix proposés restent valables pour le client."
                                            position="tooltip-right"
                                        />
                                    </label>
                                    <Datepicker
                                        id="expires_at"
                                        :modelValue="expiresAt"
                                        @update:modelValue="setExpiresAt"
                                        :upperLimit="new Date(2050, 11, 31)"
                                        :lowerLimit="new Date()"
                                        :clearable="true"
                                        class="glass-input mt-1 block w-full"
                                    />
                                    <div
                                        v-if="form.errors.expires_at"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.expires_at }}
                                    </div>
                                </div>

                                <!-- Template Selection -->
                                <div
                                    class="mb-8 rounded-lg border border-white/30 dark:border-white/10 bg-white/30 dark:bg-white/5 p-4"
                                >
                                    <h3
                                        class="mb-4 text-lg font-medium text-gray-900 dark:text-white"
                                    >
                                        Personnalisation
                                    </h3>
                                    <TemplateSelector
                                        :modelValue="form.template_id"
                                        @update:modelValue="setTemplateId"
                                        :templates="templates"
                                        :company-default="companyProfile"
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
                                <div class="mb-8">
                                    <h3
                                        class="mb-6 border-b border-gray-200 dark:border-apple-dark-border pb-2 text-lg font-medium text-gray-900 dark:text-white"
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
                                    />
                                </div>

                                <!-- Totals Section (Mobile) -->
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
                                        type="submit"
                                        class="ml-4 inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:border-gray-900 focus:outline-none focus:ring focus:ring-gray-300 active:bg-gray-900 disabled:opacity-25"
                                        :class="{
                                            'opacity-25': form.processing,
                                        }"
                                        :disabled="form.processing"
                                    >
                                        Créer le devis
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Preview -->
                    <div class="lg:w-1/2">
                        <div class="sticky top-8">
                            <QuotePreview
                                :company="companyProfile"
                                :client="selectedClient"
                                :brandingOverride="selectedBranding"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
