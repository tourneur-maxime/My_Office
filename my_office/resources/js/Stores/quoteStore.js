import { defineStore } from 'pinia';

export const useQuoteStore = defineStore('quote', {
    state: () =>
        // eslint-disable-next-line no-unused-vars
        ({
            line_items: [
                {
                    description: '',
                    quantity: 1,
                    unit_price: 0.0,
                    vat_rate: 20.0,
                },
            ],
            expires_at: null,
            template_id: null,
            templates: [],
        }),

    getters: {
        currentTemplate: (state) => {
            return (
                state.templates.find((t) => t.id === state.template_id) ||
                state.templates.find((t) => t.is_default) ||
                null
            );
        },
        // Helper to calculate total for a single line item
        calculateLineItemTotal: () => (item) => {
            const total = item.quantity * item.unit_price;
            return isNaN(total) ? 0 : total;
        },

        calculateSubtotal() {
            return this.line_items.reduce(
                (sum, item) => sum + this.calculateLineItemTotal(item),
                0,
            );
        },

        calculateVatAmount() {
            return this.line_items.reduce((sum, item) => {
                const itemTotal = this.calculateLineItemTotal(item);
                return sum + itemTotal * (item.vat_rate / 100);
            }, 0);
        },

        calculateGrandTotal() {
            return this.calculateSubtotal + this.calculateVatAmount;
        },
    },

    actions: {
        addLineItem() {
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

        setExpiresAt(date) {
            this.expires_at = date;
        },

        setTemplates(templates) {
            this.templates = [...templates];
            if (!this.template_id && templates.length > 0) {
                const defaultTemplate = templates.find((t) => t.is_default);
                if (defaultTemplate) this.template_id = defaultTemplate.id;
            }
        },

        setTemplateId(id) {
            this.template_id = id;
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
            this.expires_at = null;
            const defaultTemplate = this.templates.find((t) => t.is_default);
            this.template_id = defaultTemplate ? defaultTemplate.id : null;
        },
    },
});
