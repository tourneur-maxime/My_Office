import axios from 'axios';
import { debounce } from 'lodash';
import { defineStore } from 'pinia';
import { computed, reactive, ref } from 'vue';

export const useComplianceStore = defineStore('compliance', () => {
    const status = reactive({
        xml: 'pending', // pending, valid, invalid
        pdf: 'valid', // Assumed valid if using standard templates
        xmp: 'valid', // Assumed valid if using standard templates
        schematron: 'pending', // pending, valid, invalid
        signature: 'pending', // ready, not_ready, not_configured
        signature_hash: null,
        legal_mentions: 'pending', // valid, warning
    });

    const errors = ref({});
    const isValidating = ref(false);
    const report = ref(null);
    const isLoading = ref(false);
    const isSuccess = ref(false);

    // Computed properties for readiness
    const isReadyForGeneration = computed(() => {
        return (
            status.xml === 'valid' &&
            status.pdf === 'valid' &&
            status.xmp === 'valid' &&
            status.schematron === 'valid' &&
            status.legal_mentions === 'valid'
        );
    });

    const hasCriticalErrors = computed(() => {
        return Object.keys(errors.value).length > 0;
    });

    const reset = () => {
        status.xml = 'pending';
        status.pdf = 'valid';
        status.xmp = 'valid';
        status.schematron = 'pending';
        status.signature = 'pending';
        status.signature_hash = null;
        status.legal_mentions = 'pending';
        errors.value = {};
        report.value = null;
        isSuccess.value = false;
        isValidating.value = false;
    };

    const generateFacturX = async (invoiceId) => {
        if (!isReadyForGeneration.value) {
            return {
                success: false,
                message:
                    "La facture n'est pas conforme aux exigences Factur-X.",
            };
        }

        isLoading.value = true;
        try {
            const response = await axios.post(
                route('invoices.generate', invoiceId),
                {},
                {
                    responseType: 'blob',
                },
            );

            // Trigger file download
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `facture-${invoiceId}.pdf`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);

            isSuccess.value = true;
            return { success: true, message: 'Facture générée avec succès.' };
        } catch (error) {
            isSuccess.value = false;
            return { success: false, message: 'Erreur lors de la génération.' };
        } finally {
            isLoading.value = false;
        }
    };

    const validateData = async (invoiceData) => {
        isValidating.value = true;
        status.xml = 'pending';
        status.schematron = 'pending';
        errors.value = {};

        try {
            const response = await axios.post(
                route('api.compliance.validate'),
                invoiceData,
            );

            if (response.data.success) {
                const data = response.data.data;
                // Update statuses from API
                if (data.statuses) {
                    status.xml = data.statuses.xml || 'valid';
                    status.pdf = data.statuses.pdf || 'valid';
                    status.xmp = data.statuses.xmp || 'valid';
                    status.schematron = data.statuses.schematron || 'valid';
                    status.legal_mentions =
                        data.statuses.legal_mentions || 'valid';

                    // Handle signature specific states
                    if (data.statuses.signature === 'not_ready_no_cert') {
                        status.signature = 'not_configured';
                    } else {
                        status.signature = data.statuses.signature || 'ready';
                    }
                } else {
                    status.xml = data.valid ? 'valid' : 'invalid';
                    status.schematron = data.valid ? 'valid' : 'invalid';
                }

                errors.value = data.errors || {};
                report.value = data.report || null;
            } else {
                status.xml = 'invalid';
                status.schematron = 'invalid';
                errors.value = response.data.data.errors || {};
            }
        } catch (error) {
            status.xml = 'invalid';
            status.schematron = 'invalid';
            status.signature = 'not_ready';

            if (
                error.response &&
                error.response.data &&
                error.response.data.errors
            ) {
                errors.value = error.response.data.errors;
            } else if (
                error.response &&
                error.response.data &&
                error.response.data.message
            ) {
                errors.value = { global: [error.response.data.message] };
            } else {
                errors.value = {
                    global: [
                        'Une erreur technique est survenue lors de la validation.',
                    ],
                };
            }
        } finally {
            isValidating.value = false;
        }
    };

    // Debounced version for real-time validation
    const validateDataDebounced = debounce((invoiceData) => {
        validateData(invoiceData);
    }, 1000);

    return {
        status,
        errors,
        isValidating,
        isLoading,
        isSuccess,
        report,
        isReadyForGeneration,
        hasCriticalErrors,
        reset,
        generateFacturX,
        validateData,
        validateDataDebounced,
    };
});
