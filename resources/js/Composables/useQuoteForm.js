import { useErrorHandler } from '@/Composables/useErrorHandler'; // Import useErrorHandler
import { useQuoteStore } from '@/Stores/quoteStore';
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

export function useQuoteForm(quoteId = null) {
    const quoteStore = useQuoteStore();
    const selectedClientId = ref(null);
    const { handleApiError } = useErrorHandler();

    // Reset store when creating a new quote to avoid stale data
    if (!quoteId) {
        quoteStore.resetForm();
    }

    // Initialize Inertia form with data from the Pinia store
    const form = useForm({
        expires_at: quoteStore.expires_at,
        line_items: quoteStore.line_items.map((item) => ({ ...item })),
        client_id: selectedClientId.value,
        template_id: quoteStore.template_id,
    });

    // Watch for changes from the store and update the form
    // IMPORTANT: Store the unsubscribe function to prevent memory leaks
    const unsubscribe = quoteStore.$subscribe((mutation, state) => {
        if (mutation.events.key === 'expires_at') {
            form.expires_at = state.expires_at;
        }
        if (mutation.events.key === 'line_items') {
            form.line_items = state.line_items.map((item) => ({ ...item }));
        }
        if (mutation.events.key === 'template_id') {
            form.template_id = state.template_id;
        }
    });

    // Clean up subscription on component unmount to prevent memory leaks
    onBeforeUnmount(() => {
        unsubscribe();
    });

    const addLineItem = () => {
        quoteStore.addLineItem();
    };

    const removeLineItem = (index) => {
        quoteStore.removeLineItem(index);
    };

    const moveLineItem = (index, direction) => {
        quoteStore.moveLineItem(index, direction);
    };

    const setExpiresAt = (date) => {
        quoteStore.setExpiresAt(date);
    };

    const setTemplateId = (id) => {
        quoteStore.setTemplateId(id);
        form.template_id = id;
    };

    const setSelectedClient = (clientId) => {
        selectedClientId.value = clientId;
        form.client_id = clientId;
    };

    // Bind form line items to store's line items for two-way data flow
    const updateLineItem = (index, field, value) => {
        const updatedItems = quoteStore.line_items.map((item, i) => {
            if (i === index) {
                return { ...item, [field]: value };
            }
            return item;
        });
        quoteStore.setLineItems(updatedItems);
    };

    const submit = () => {
        // Ensure form data reflects the latest state from the store before submission
        form.expires_at = quoteStore.expires_at;
        form.line_items = quoteStore.line_items;
        form.template_id = quoteStore.template_id;

        if (!selectedClientId.value) {
            alert('Veuillez sélectionner un client.');
            return;
        }

        form.post(route('quotes.store', selectedClientId.value), {
            onSuccess: () => {
                quoteStore.resetForm();
                setSelectedClient(null);
            },
            onError: (errors) => {
                // Inertia handles validation errors automatically via form.errors
                // For non-validation errors, use the errorHandler.
                if (!errors.response || errors.response.status !== 422) {
                    // 422 is for validation errors
                    handleApiError(errors); // Use the centralized error handler
                }
                console.error('Submission errors:', errors); // Keep for debugging
            },
            preserveScroll: true,
        });
    };

    return {
        form,
        lineItems: computed(() => quoteStore.line_items),
        expiresAt: computed(() => quoteStore.expires_at),
        templateId: computed(() => quoteStore.template_id),
        selectedClientId,
        addLineItem,
        removeLineItem,
        moveLineItem,
        updateLineItem,
        setExpiresAt,
        setTemplateId,
        setSelectedClient,
        submit,
        calculateSubtotal: computed(() => quoteStore.calculateSubtotal),
        calculateVatAmount: computed(() => quoteStore.calculateVatAmount),
        calculateGrandTotal: computed(() => quoteStore.calculateGrandTotal),
        calculateLineItemTotal: quoteStore.calculateLineItemTotal,
    };
}
