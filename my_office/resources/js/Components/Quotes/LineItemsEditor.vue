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
    const total = (item.quantity || 0) * (item.unit_price || 0);
    return isNaN(total) ? 0 : Math.round(total * 100) / 100;
};
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(item, index) in items"
            :key="index"
            :data-index="index"
            class="group relative rounded-lg border bg-gray-50 p-4"
        >
            <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-12">
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
                    <label :for="`description-${index}`" class="label">
                        <span class="label-text font-medium">Description</span>
                    </label>
                    <textarea
                        :id="`description-${index}`"
                        :value="item.description"
                        @input="
                            update(index, 'description', $event.target.value)
                        "
                        class="textarea-bordered textarea h-20 w-full"
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
                    <label :for="`quantity-${index}`" class="label">
                        <span class="label-text font-medium">Qté</span>
                    </label>
                    <input
                        :id="`quantity-${index}`"
                        :value="item.quantity"
                        @input="update(index, 'quantity', $event.target.value)"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="input-bordered input w-full"
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
                    <label :for="`unit_price-${index}`" class="label">
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
                            class="input-bordered input w-full pr-8"
                            required
                        />
                        <span class="absolute right-3 top-3 text-gray-400"
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
                    <label :for="`vat_rate-${index}`" class="label">
                        <span class="label-text font-medium">TVA %</span>
                    </label>
                    <input
                        :id="`vat_rate-${index}`"
                        :value="item.vat_rate"
                        @input="update(index, 'vat_rate', $event.target.value)"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        class="input-bordered input w-full"
                        required
                    />
                    <div
                        v-if="errors[`line_items.${index}.vat_rate`]"
                        class="mt-1 text-xs text-error"
                    >
                        {{ errors[`line_items.${index}.vat_rate`] }}
                    </div>
                </div>

                <!-- Line Total -->
                <div class="self-center pt-6 text-right md:col-span-2">
                    <span class="mb-1 block text-xs text-gray-500"
                        >Total HT</span
                    >
                    <span class="font-bold"
                        >{{ calculateLineTotal(item).toFixed(2) }} €</span
                    >
                </div>
            </div>

            <!-- Remove Button -->
            <button
                v-if="items.length > 1"
                @click="remove(index)"
                type="button"
                class="btn btn-error btn-xs btn-circle absolute -right-2 -top-2 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
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

        <div class="mt-6 flex justify-center">
            <button
                id="add-line-item-btn"
                @click="add"
                type="button"
                class="btn btn-outline btn-sm gap-2"
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                Ajouter un poste
            </button>
        </div>
    </div>
</template>
