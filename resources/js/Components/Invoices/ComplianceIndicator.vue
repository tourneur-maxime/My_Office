<script setup>
import ValidationReportModal from '@/Components/Invoices/ValidationReportModal.vue';
import { useComplianceStore } from '@/Stores/complianceStore';
import { computed } from 'vue';

const props = defineProps({
    invoice: {
        type: Object,
        default: null,
    },
});

const store = useComplianceStore();

const isReadyForSignature = computed(() => {
    if (props.invoice?.is_ready_for_signature) return true;
    return store.status.signature === 'ready';
});

const overallStatus = computed(() => {
    if (store.isValidating) return 'validating';
    if (store.isReadyForGeneration) return 'valid';
    return 'invalid';
});

const statusClasses = computed(() => {
    switch (overallStatus.value) {
        case 'validating':
            return 'alert-info shadow-info/20';
        case 'valid':
            return 'alert-success shadow-success/20';
        case 'invalid':
            return 'alert-error shadow-error/20';
        default:
            return 'alert-info shadow-info/20';
    }
});

const statusText = computed(() => {
    switch (overallStatus.value) {
        case 'validating':
            return 'Analyse de conformité...';
        case 'valid':
            return 'Facture conforme Factur-X';
        case 'invalid':
            return 'Facture non conforme';
        default:
            return 'En attente de données';
    }
});

const getBadgeClass = (status) => {
    switch (status) {
        case 'valid':
            return 'badge-success';
        case 'invalid':
            return 'badge-error';
        case 'pending':
            return 'badge-warning animate-pulse';
        default:
            return 'badge-ghost';
    }
};
</script>

<template>
    <div
        class="card mt-6 overflow-hidden border border-base-300 bg-base-100 shadow-xl"
    >
        <div class="card-body p-5">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="bg-primary/10 rounded-lg p-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-base-content/70 text-sm font-bold uppercase tracking-widest"
                    >
                        Bouclier de Conformité
                    </h3>
                </div>
                <button
                    class="btn btn-ghost btn-xs gap-1 font-normal normal-case"
                    onclick="compliance_details_modal.showModal()"
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
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    Détails du rapport
                </button>
            </div>

            <div class="flex flex-col gap-5">
                <!-- Status Banner -->
                <div
                    :class="[
                        'alert border-none py-3 shadow-lg transition-all duration-500',
                        statusClasses,
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <svg
                            v-if="overallStatus === 'valid'"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 shrink-0 stroke-current"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <svg
                            v-else-if="overallStatus === 'invalid'"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 shrink-0 stroke-current"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span
                            v-else
                            class="loading loading-spinner loading-md"
                        ></span>

                        <div>
                            <h4 class="font-bold leading-none">
                                {{ statusText }}
                            </h4>
                            <p
                                class="mt-1 text-xs opacity-80"
                                v-if="overallStatus === 'valid'"
                            >
                                Prêt pour la certification fiscale
                            </p>
                            <p
                                class="mt-1 text-xs opacity-80"
                                v-else-if="overallStatus === 'invalid'"
                            >
                                {{ Object.keys(store.errors).length }} erreur(s)
                                bloquante(s)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Indicators Grid -->
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <!-- XML -->
                    <div
                        class="tooltip tooltip-bottom"
                        data-tip="Structure des données (CII)"
                    >
                        <div
                            class="flex items-center justify-between rounded-lg border border-base-300 bg-base-200 p-2"
                        >
                            <span class="mr-1 truncate font-semibold uppercase"
                                >XML CII</span
                            >
                            <div
                                :class="[
                                    'badge badge-xs',
                                    getBadgeClass(store.status.xml),
                                ]"
                            ></div>
                        </div>
                    </div>

                    <!-- Schematron -->
                    <div
                        class="tooltip tooltip-bottom"
                        data-tip="Règles métier EN 16931"
                    >
                        <div
                            class="flex items-center justify-between rounded-lg border border-base-300 bg-base-200 p-2"
                        >
                            <span class="mr-1 truncate font-semibold uppercase"
                                >Calculs</span
                            >
                            <div
                                :class="[
                                    'badge badge-xs',
                                    getBadgeClass(store.status.schematron),
                                ]"
                            ></div>
                        </div>
                    </div>

                    <!-- PDF/A-3 -->
                    <div
                        class="tooltip tooltip-bottom"
                        data-tip="Format d'archivage long terme"
                    >
                        <div
                            class="flex items-center justify-between rounded-lg border border-base-300 bg-base-200 p-2"
                        >
                            <span class="mr-1 truncate font-semibold uppercase"
                                >PDF/A-3</span
                            >
                            <div
                                :class="[
                                    'badge badge-xs',
                                    getBadgeClass(store.status.pdf),
                                ]"
                            ></div>
                        </div>
                    </div>

                    <!-- Signature -->
                    <div
                        class="tooltip tooltip-bottom"
                        data-tip="Préparation à la signature"
                    >
                        <div
                            class="flex items-center justify-between rounded-lg border border-base-300 bg-base-200 p-2"
                        >
                            <span class="mr-1 truncate font-semibold uppercase"
                                >Signable</span
                            >
                            <div
                                :class="[
                                    'badge badge-xs',
                                    getBadgeClass(
                                        isReadyForSignature
                                            ? 'valid'
                                            : 'pending',
                                    ),
                                ]"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ValidationReportModal
            id="compliance_details_modal"
            :invoice="invoice"
        />
    </div>
</template>
