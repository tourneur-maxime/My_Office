<script setup>
import { useComplianceStore } from '@/Stores/complianceStore';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    invoice: {
        type: Object,
        default: null,
    },
    id: {
        type: String,
        default: 'compliance_details_modal',
    },
});

const store = useComplianceStore();

const signatureHash = computed(() => {
    return props.invoice?.signature_hash || store.status.signature_hash;
});

const overallStatus = computed(() => {
    if (store.isValidating) return 'validating';
    if (store.isReadyForGeneration) return 'valid';
    return 'invalid';
});

const requirements = computed(() => [
    {
        name: 'Données structurées (XML)',
        status: store.status.xml,
        desc: 'Validation de la structure XML selon le standard CII.',
    },
    {
        name: 'Règles métier (Schematron)',
        status: store.status.schematron,
        desc: 'Respect des règles de calcul et de cohérence EN 16931.',
    },
    {
        name: 'Format PDF/A-3',
        status: store.status.pdf,
        desc: "Conformité du fichier PDF pour l'archivage longue durée.",
    },
    {
        name: 'Métadonnées XMP',
        status: store.status.xmp,
        desc: 'Présence des métadonnées Factur-X obligatoires dans le PDF.',
    },
    {
        name: 'Mentions Légales',
        status: store.status.legal_mentions,
        desc: 'Vérification des 11 mentions obligatoires françaises.',
    },
]);

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'valid':
            return 'badge-success';
        case 'invalid':
            return 'badge-error';
        case 'pending':
            return 'badge-warning';
        default:
            return 'badge-ghost';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'valid':
            return '✓';
        case 'invalid':
            return '✕';
        case 'pending':
            return '...';
        default:
            return '?';
    }
};
</script>

<template>
    <dialog :id="id" class="modal">
        <div class="modal-box w-11/12 max-w-2xl">
            <h3 class="mb-4 flex items-center gap-2 text-lg font-bold">
                Rapport de Conformité Factur-X
                <span
                    v-if="overallStatus === 'valid'"
                    class="badge badge-success"
                    >Conforme</span
                >
                <span
                    v-else-if="overallStatus === 'validating'"
                    class="badge badge-info"
                    >Analyse...</span
                >
                <span v-else class="badge badge-error">Non Conforme</span>
            </h3>

            <div class="space-y-6">
                <!-- Checklist section -->
                <div class="grid grid-cols-1 gap-2">
                    <div
                        v-for="req in requirements"
                        :key="req.name"
                        class="flex items-start justify-between rounded-lg bg-base-200 p-3"
                    >
                        <div>
                            <span class="block text-sm font-semibold">{{
                                req.name
                            }}</span>
                            <span class="text-xs text-gray-500">{{
                                req.desc
                            }}</span>
                        </div>
                        <div
                            :class="['badge', getStatusBadgeClass(req.status)]"
                        >
                            {{ getStatusIcon(req.status) }}
                        </div>
                    </div>
                </div>

                <!-- Errors section -->
                <div v-if="store.hasCriticalErrors" class="space-y-3">
                    <h4
                        class="text-sm font-bold uppercase tracking-wider text-error"
                    >
                        Erreurs à corriger :
                    </h4>
                    <div
                        v-for="(fieldErrors, field) in store.errors"
                        :key="field"
                        class="alert alert-error py-2 text-sm shadow-sm"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 shrink-0 stroke-current"
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
                        <div>
                            <span class="font-bold capitalize"
                                >{{
                                    field === 'global' ? 'Erreur' : field
                                }}:</span
                            >
                            <ul class="mt-1 list-inside list-disc">
                                <li v-for="error in fieldErrors" :key="error">
                                    {{ error }}
                                </li>
                            </ul>
                            <div v-if="field === 'company'" class="mt-2">
                                <Link
                                    :href="route('settings.company')"
                                    class="btn btn-outline btn-xs"
                                >
                                    Configurer le profil entreprise
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success message -->
                <div
                    v-else-if="overallStatus === 'valid'"
                    class="alert alert-success shadow-sm"
                >
                    <svg
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
                    <span
                        >Toutes les vérifications sont passées. La facture est
                        conforme à la norme Factur-X EN 16931.</span
                    >
                </div>

                <!-- Signature hash -->
                <div
                    v-if="signatureHash"
                    class="rounded-lg border-2 border-dashed border-base-300 p-3"
                >
                    <h4
                        class="mb-2 flex items-center gap-2 text-xs font-bold text-gray-500"
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
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        SCELLÉ D'INTÉGRITÉ SHA-256
                    </h4>
                    <code
                        class="block break-all rounded bg-base-200 p-2 font-mono text-[10px] text-gray-600"
                        >{{ signatureHash }}</code
                    >
                </div>
            </div>

            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-sm">Fermer</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</template>
