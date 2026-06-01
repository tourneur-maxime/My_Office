import { useErrorHandler } from '@/Composables/useErrorHandler';
import { useInvoiceStore } from '@/Stores/invoiceStore';
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount } from 'vue';

export function useInvoiceForm(invoiceId = null) {
    const invoiceStore = useInvoiceStore();
    const { handleApiError } = useErrorHandler();

    // Reset store when creating a new invoice to avoid stale data
    if (!invoiceId) {
        invoiceStore.resetForm();
    }

    // Initialize Inertia form with data from the Pinia store
    const form = useForm({
        client_id: null,
        template_id: null,
        date: '',
        due_date: '',
        layout_configuration: null,
        line_items: invoiceStore.line_items.map((item) => ({ ...item })),
    });

    // Watch for changes from the store and update the form
    // IMPORTANT: Store the unsubscribe function to prevent memory leaks
    const unsubscribe = invoiceStore.$subscribe((mutation, state) => {
        if (
            mutation.events.key === 'line_items' ||
            Array.isArray(mutation.events)
        ) {
            form.line_items = state.line_items.map((item) => ({ ...item }));
        }
    });

    // Clean up subscription on component unmount to prevent memory leaks
    onBeforeUnmount(() => {
        unsubscribe();
    });

    const addLineItem = () => {
        invoiceStore.addLineItem();
    };

    const removeLineItem = (index) => {
        invoiceStore.removeLineItem(index);
    };

    const moveLineItem = (index, direction) => {
        invoiceStore.moveLineItem(index, direction);
    };

    const updateLineItem = (index, field, value) => {
        const updatedItems = invoiceStore.line_items.map((item, i) => {
            if (i === index) {
                return { ...item, [field]: value };
            }
            return item;
        });
        invoiceStore.setLineItems(updatedItems);
    };

    const submit = () => {
        // Ensure form data reflects the latest state from the store before submission
        form.line_items = invoiceStore.line_items;

        const routeName = invoiceId ? 'invoices.update' : 'invoices.store';
        const routeParam = invoiceId || null;

        const method = invoiceId ? 'put' : 'post';

        form[method](route(routeName, routeParam), {
            onSuccess: () => {
                if (!invoiceId) {
                    invoiceStore.resetForm();
                }
            },
            onError: (errors) => {
                if (!errors.response || errors.response.status !== 422) {
                    handleApiError(errors);
                }
                console.error('Submission errors:', errors);
            },
            preserveScroll: true,
        });
    };

    return {
        form,
        lineItems: computed(() => invoiceStore.line_items),
        addLineItem,
        removeLineItem,
        moveLineItem,
        updateLineItem,
        submit,
        calculateSubtotal: computed(() => invoiceStore.calculateSubtotal),
        calculateVatAmount: computed(() => invoiceStore.calculateVatAmount),
        calculateGrandTotal: computed(() => invoiceStore.calculateGrandTotal),
        calculateLineItemTotal: invoiceStore.calculateLineItemTotal,
    };
}
