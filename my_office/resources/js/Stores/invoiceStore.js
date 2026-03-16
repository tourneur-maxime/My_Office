import { defineStore } from 'pinia';

export const useInvoiceStore = defineStore('invoice', {
    state: () => ({
        line_items: [
            {
                description: '',
                quantity: 1,
                unit_price: 0.0,
                vat_rate: 20.0,
            },
        ],
        status: 'Brouillon',
    }),

    getters: {
        // Helper to calculate subtotal for a single line item (without VAT)
        calculateLineItemTotal: () => (item) => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.unit_price) || 0;
            const subtotal = qty * price;
            return isNaN(subtotal) ? 0 : Math.round(subtotal * 100) / 100;
        },

        calculateSubtotal: (state) => {
            const subtotal = state.line_items.reduce((sum, item) => {
                const qty = parseFloat(item.quantity) || 0;
                const price = parseFloat(item.unit_price) || 0;
                return sum + qty * price;
            }, 0);
            return Math.round(subtotal * 100) / 100;
        },

        calculateVatAmount: (state) => {
            const vatAmount = state.line_items.reduce((sum, item) => {
                const qty = parseFloat(item.quantity) || 0;
                const price = parseFloat(item.unit_price) || 0;
                const vatRate = parseFloat(item.vat_rate) || 0;
                const lineSubtotal = Math.round(qty * price * 100) / 100;
                return (
                    sum + Math.round(lineSubtotal * (vatRate / 100) * 100) / 100
                );
            }, 0);
            return Math.round(vatAmount * 100) / 100;
        },

        calculateGrandTotal() {
            return (
                Math.round(
                    (this.calculateSubtotal + this.calculateVatAmount) * 100,
                ) / 100
            );
        },
    },

    actions: {
        addLineItem() {
            // Immutable update: create a new array
            this.line_items = [
                ...this.line_items,
                {
                    description: '',
                    quantity: 1,
                    unit_price: 0.0,
                    vat_rate: 20.0,
                },
            ];
        },

        removeLineItem(index) {
            // Immutable update: filter out the item
            this.line_items = this.line_items.filter((_, i) => i !== index);
        },

        moveLineItem(index, direction) {
            const newIndex = direction === 'up' ? index - 1 : index + 1;
            if (newIndex >= 0 && newIndex < this.line_items.length) {
                const items = [...this.line_items];
                const temp = items[index];
                items[index] = items[newIndex];
                items[newIndex] = temp;
                this.line_items = items;
            }
        },

        setLineItems(items) {
            // Ensure immutable update as per project-context.md
            this.line_items = [...items];
        },

        loadInvoice(invoice) {
            if (invoice && invoice.line_items) {
                this.line_items = invoice.line_items
                    .map((item) => ({
                        id: item.id,
                        description: item.description,
                        quantity: parseFloat(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        vat_rate: parseFloat(item.vat_rate),
                        sort_order: item.sort_order,
                    }))
                    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
                this.status = invoice.status;
            }
        },

        resetForm() {
            this.line_items = [
                {
                    description: '',
                    quantity: 1,
                    unit_price: 0.0,
                    vat_rate: 20.0,
                },
            ];
            this.status = 'Brouillon';
        },
    },
});
