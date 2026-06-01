<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    report: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const isSuccess = computed(() => props.report?.overall_status === 'success');

const successMessages = computed(
    () => props.report?.messages?.filter((m) => m.type === 'success') ?? [],
);

const errorMessages = computed(
    () => props.report?.messages?.filter((m) => m.type === 'error') ?? [],
);

const warningMessages = computed(
    () => props.report?.messages?.filter((m) => m.type === 'warning') ?? [],
);

const getStepLabel = (step) => {
    const labels = {
        xml_generation: 'Generation XML',
        xsd_validation: 'Validation XSD',
        schematron_validation: 'Validation Schematron',
        pdf_generation: 'Generation PDF',
        xml_embedding: 'Embarquement XML',
        xmp_metadata: 'Metadonnees XMP',
        validation: 'Validation',
        generation: 'Generation',
    };
    return labels[step] || step;
};
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="p-6">
            <div class="mb-6 flex items-center">
                <div v-if="isSuccess" class="flex items-center text-green-600">
                    <svg
                        class="mr-3 h-8 w-8"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <h2 class="text-xl font-semibold">
                        Generation Factur-X reussie
                    </h2>
                </div>
                <div v-else class="flex items-center text-red-600">
                    <svg
                        class="mr-3 h-8 w-8"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <h2 class="text-xl font-semibold">
                        Generation Factur-X echouee
                    </h2>
                </div>
            </div>

            <!-- Error Messages -->
            <div v-if="errorMessages.length > 0" class="mb-6">
                <h3 class="mb-3 text-lg font-medium text-red-700">Erreurs</h3>
                <div class="space-y-2">
                    <div
                        v-for="(message, index) in errorMessages"
                        :key="'error-' + index"
                        class="flex items-start rounded-lg border border-red-200 bg-red-50 p-3"
                    >
                        <svg
                            class="mr-3 mt-0.5 h-5 w-5 flex-shrink-0 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        <div>
                            <span
                                class="text-xs font-medium uppercase text-red-600"
                                >{{ getStepLabel(message.step) }}</span
                            >
                            <p class="text-sm text-red-800">
                                {{ message.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Warning Messages -->
            <div v-if="warningMessages.length > 0" class="mb-6">
                <h3 class="mb-3 text-lg font-medium text-yellow-700">
                    Avertissements
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="(message, index) in warningMessages"
                        :key="'warning-' + index"
                        class="flex items-start rounded-lg border border-yellow-200 bg-yellow-50 p-3"
                    >
                        <svg
                            class="mr-3 mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <div>
                            <span
                                class="text-xs font-medium uppercase text-yellow-600"
                                >{{ getStepLabel(message.step) }}</span
                            >
                            <p class="text-sm text-yellow-800">
                                {{ message.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Messages -->
            <div v-if="successMessages.length > 0" class="mb-6">
                <h3 class="mb-3 text-lg font-medium text-green-700">
                    Etapes validees
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="(message, index) in successMessages"
                        :key="'success-' + index"
                        class="flex items-start rounded-lg border border-green-200 bg-green-50 p-3"
                    >
                        <svg
                            class="mr-3 mt-0.5 h-5 w-5 flex-shrink-0 text-green-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                        <div>
                            <span
                                class="text-xs font-medium uppercase text-green-600"
                                >{{ getStepLabel(message.step) }}</span
                            >
                            <p class="text-sm text-green-800">
                                {{ message.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <SecondaryButton @click="close"> Fermer </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
