<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['add', 'remove', 'move', 'update']);

const add = () => emit('add');
const remove = (index) => emit('remove', index);
const move = (index, direction) => emit('move', index, direction);
const update = (index, field, value) => {
    let finalValue = value;
    if (['quantity', 'unit_price', 'vat_rate'].includes(field)) {
        finalValue = isNaN(parseFloat(value)) ? 0 : parseFloat(value);
    }
    emit('update', index, field, finalValue);
};

const calculateLineTotal = (item) => {
    // Match backend calculation: include VAT in the total
    // Backend: round(qty * unit_price * (1 + vat_rate/100), 2)
    const qty = parseFloat(item.quantity) || 0;
    const price = parseFloat(item.unit_price) || 0;
    const vatRate = parseFloat(item.vat_rate) || 0;

    if (isNaN(qty) || isNaN(price) || isNaN(vatRate)) {
        return 0;
    }

    const total = qty * price * (1 + vatRate / 100);
    return Math.round(total * 100) / 100;
};
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(item, index) in items"
            :key="index"
            :data-index="index"
            class="group relative rounded-lg border-2 border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))]/60 backdrop-blur-xl p-5 hover:border-[hsl(var(--primary))]/30 transition-all duration-200"
        >
            <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-12 md:gap-3">
                <!-- Reordering Buttons -->
                <div
                    class="flex items-center justify-center space-x-2 pt-6 md:col-span-1 md:flex-col md:space-x-0 md:space-y-2"
                >
                    <button
                        type="button"
                        @click="move(index, 'up')"
                        :disabled="index === 0"
                        class="btn btn-ghost btn-xs btn-circle disabled:opacity-30"
                        title="Déplacer vers le haut"
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
                                d="M5 15l7-7 7 7"
                            />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="move(index, 'down')"
                        :disabled="index === items.length - 1"
                        class="btn btn-ghost btn-xs btn-circle disabled:opacity-30"
                        title="Déplacer vers le bas"
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
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Description -->
                <div class="md:col-span-5">
                    <label :for="`description-${index}`" class="glass-label">
                        <span class="label-text font-medium">Description</span>
                    </label>
                    <textarea
                        :id="`description-${index}`"
                        :value="item.description"
                        @input="
                            update(index, 'description', $event.target.value)
                        "
                        class="glass-textarea h-20 w-full"
                        placeholder="Description de la prestation ou de l'article"
                        required
                    ></textarea>
                    <div
                        v-if="errors[`line_items.${index}.description`]"
                        class="mt-1 text-xs text-error"
                    >
                        {{ errors[`line_items.${index}.description`] }}
                    </div>
                </div>

                <!-- Quantity -->
                <div class="md:col-span-1">
                    <label :for="`quantity-${index}`" class="glass-label">
                        <span class="label-text font-medium">Qté</span>
                    </label>
                    <input
                        :id="`quantity-${index}`"
                        :value="item.quantity"
                        @input="update(index, 'quantity', $event.target.value)"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="glass-input w-full text-center font-semibold tabular-nums"
                        required
                    />
                    <div
                        v-if="errors[`line_items.${index}.quantity`]"
                        class="mt-1 text-xs text-error"
                    >
                        {{ errors[`line_items.${index}.quantity`] }}
                    </div>
                </div>

                <!-- Unit Price -->
                <div class="md:col-span-2">
                    <label :for="`unit_price-${index}`" class="glass-label">
                        <span class="label-text font-medium"
                            >Prix Unitaire HT</span
                        >
                    </label>
                    <div class="relative">
                        <input
                            :id="`unit_price-${index}`"
                            :value="item.unit_price"
                            @input="
                                update(index, 'unit_price', $event.target.value)
                            "
                            type="number"
                            step="0.01"
                            min="0"
                            class="glass-input w-full pr-10 font-semibold tabular-nums"
                            required
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[hsl(var(--text-muted))] font-bold"
                            >€</span
                        >
                    </div>
                    <div
                        v-if="errors[`line_items.${index}.unit_price`]"
                        class="mt-1 text-xs text-error"
                    >
                        {{ errors[`line_items.${index}.unit_price`] }}
                    </div>
                </div>

                <!-- VAT Rate -->
                <div class="md:col-span-1">
                    <label :for="`vat_rate-${index}`" class="glass-label">
                        <span class="label-text font-medium">TVA %</span>
                    </label>
                    <div class="relative">
                        <input
                            :id="`vat_rate-${index}`"
                            :value="item.vat_rate"
                            @input="update(index, 'vat_rate', $event.target.value)"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            class="glass-input w-full pr-8 text-center font-semibold tabular-nums"
                            required
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[hsl(var(--text-muted))] font-bold text-xs">%</span>
                    </div>
                    <div
                        v-if="errors[`line_items.${index}.vat_rate`]"
                        class="mt-1 text-xs text-error"
                    >
                        {{ errors[`line_items.${index}.vat_rate`] }}
                    </div>
                </div>

                <!-- Line Total (TTC) -->
                <div class="self-center pt-6 text-right md:col-span-2">
                    <span class="mb-1 block text-xs text-gray-500 dark:text-apple-dark-secondary"
                        >Total TTC</span
                    >
                    <span class="font-bold text-gray-900 dark:text-white"
                        >{{ calculateLineTotal(item).toFixed(2) }} €</span
                    >
                </div>
            </div>

            <!-- Remove Button -->
            <button
                v-if="items.length > 1"
                @click.stop="remove(index)"
                type="button"
                class="absolute -right-2 -top-2 btn btn-error btn-xs btn-circle shadow-lg transition-all opacity-0 group-hover:opacity-100 hover:scale-110 pointer-events-none group-hover:pointer-events-auto"
                title="Supprimer cette ligne"
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
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div class="mt-8 pt-4 border-t-2 border-dashed border-[hsl(var(--border))] flex justify-center">
            <button
                id="add-line-item-btn"
                @click="add"
                type="button"
                class="group relative inline-flex items-center gap-3 px-6 py-3 rounded-xl bg-gradient-to-r from-[hsl(var(--primary))]/10 to-[hsl(var(--primary))]/5 border-2 border-[hsl(var(--primary))]/30 text-[hsl(var(--primary))] font-semibold hover:from-[hsl(var(--primary))]/20 hover:to-[hsl(var(--primary))]/10 hover:border-[hsl(var(--primary))]/50 hover:shadow-lg hover:shadow-[hsl(var(--primary))]/20 hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[hsl(var(--primary))]/30 focus:ring-offset-2"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 transition-transform duration-200 group-hover:rotate-90"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                <span>Ajouter un poste</span>
            </button>
        </div>
    </div>
</template>
