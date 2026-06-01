<script setup>
import { computed, ref } from 'vue';
import RichTextEditor from '../RichTextEditor.vue';

const props = defineProps({
    content: Object,
    primaryColor: {
        type: String,
        default: '#6366F1'
    },
    textColor: {
        type: String,
        default: '#1F2937'
    },
    editable: {
        type: Boolean,
        default: false
    },
    onAdd: Function,
    onRemove: Function,
    onUpdate: Function
});

// Column resizing
const resizingColumn = ref(null);
const columnWidths = ref({
    description: 40,
    quantity: 12,
    unit_price: 14,
    vat_rate: 12,
    total: 18
});

const handleMouseDown = (e, columnId) => {
    if (!props.editable) return;

    resizingColumn.value = columnId;
    const startX = e.clientX;
    const startWidth = columnWidths.value[columnId];

    const handleMouseMove = (moveEvent) => {
        const delta = (moveEvent.clientX - startX);
        const newWidth = Math.max(8, startWidth + (delta / 10));
        columnWidths.value[columnId] = newWidth;
    };

    const handleMouseUp = () => {
        document.removeEventListener('mousemove', handleMouseMove);
        document.removeEventListener('mouseup', handleMouseUp);
        resizingColumn.value = null;
    };

    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
};

// Default columns configuration
const defaultColumns = [
    { id: 'description', label: 'Description', type: 'text', align: 'left', visible: true, width: 'auto' },
    { id: 'quantity', label: 'Qté', type: 'number', align: 'right', visible: true, width: 'w-24' },
    { id: 'unit_price', label: 'PU HT', type: 'currency', align: 'right', visible: true, width: 'w-28' },
    { id: 'vat_rate', label: 'TVA %', type: 'percent', align: 'right', visible: true, width: 'w-20' },
    { id: 'total', label: 'Total HT', type: 'currency', align: 'right', visible: true, width: 'w-32', calculated: true }
];

const columns = computed(() => {
    return props.content.columns || defaultColumns;
});

const visibleColumns = computed(() => {
    return columns.value.filter(col => col.visible);
});

const addItem = () => {
    if (props.onAdd) props.onAdd();
};

const removeItem = (index) => {
    if (props.onRemove) props.onRemove(index);
};

const updateItem = (index, field, value) => {
    if (props.onUpdate) props.onUpdate(index, field, value);
};

const calculateTotal = (item) => {
    return ((item.quantity || 0) * (item.unit_price || 0)).toFixed(2);
};

const formatValue = (item, column) => {
    const value = item[column.id];

    switch (column.type) {
        case 'currency':
            return `${(value || 0).toFixed(2)} €`;
        case 'percent':
            return `${(value || 20).toFixed(1)} %`;
        case 'number':
            return value || 1;
        default:
            return value || '';
    }
};
</script>

<template>
    <div class="space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr :style="{ backgroundColor: primaryColor + '20', borderBottom: '2px solid ' + primaryColor }">
                        <th v-if="editable" class="py-3 px-2 w-12"></th>
                        <th
                            v-for="column in visibleColumns"
                            :key="column.id"
                            class="py-3 px-4 font-bold whitespace-nowrap relative group"
                            :class="[
                                column.align === 'right' ? 'text-right' :
                                column.align === 'center' ? 'text-center' : 'text-left'
                            ]"
                            :style="{
                                color: textColor,
                                width: columnWidths[column.id] + '%'
                            }"
                        >
                            {{ column.label }}
                            <!-- Resize handle -->
                            <div
                                v-if="editable"
                                @mousedown="handleMouseDown($event, column.id)"
                                class="absolute right-0 top-0 h-full w-1 bg-gray-300 opacity-0 group-hover:opacity-100 hover:opacity-100 cursor-col-resize hover:bg-indigo-500 transition-all"
                            ></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in content.items" :key="index" :style="{ borderBottom: '1px solid ' + primaryColor + '20' }" class="group">
                        <td v-if="editable" class="py-3 px-2 text-center">
                            <button
                                v-if="content.items.length > 1"
                                @click.stop="removeItem(index)"
                                type="button"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                title="Supprimer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </td>

                        <td
                            v-for="column in visibleColumns"
                            :key="column.id"
                            class="py-3 px-4 whitespace-nowrap"
                            :class="[
                                column.align === 'right' ? 'text-right' :
                                column.align === 'center' ? 'text-center' : 'text-left',
                                column.id === 'total' ? 'font-semibold' : '',
                                column.id === 'description' ? 'whitespace-normal' : ''
                            ]"
                            :style="{ color: textColor }"
                        >
                            <!-- Description field (rich text editor) -->
                            <template v-if="column.id === 'description'">
                                <RichTextEditor
                                    v-if="editable"
                                    :model-value="item.description || ''"
                                    @update:model-value="updateItem(index, 'description', $event)"
                                />
                                <div
                                    v-else
                                    class="whitespace-normal break-words prose prose-sm max-w-none"
                                >
                                    {{ item.description || 'Description...' }}
                                </div>
                            </template>

                            <!-- Calculated total field (read-only) -->
                            <template v-else-if="column.calculated && column.id === 'total'">
                                {{ calculateTotal(item) }} €
                            </template>

                            <!-- Number fields -->
                            <template v-else-if="column.type === 'number' || column.type === 'currency' || column.type === 'percent'">
                                <input
                                    v-if="editable"
                                    :value="item[column.id]"
                                    @input="updateItem(index, column.id, parseFloat($event.target.value) || (column.type === 'percent' ? 20 : column.type === 'number' ? 1 : 0))"
                                    type="number"
                                    :min="column.type === 'number' ? '0.01' : '0'"
                                    :max="column.type === 'percent' ? '100' : undefined"
                                    :step="column.type === 'number' ? '0.01' : column.type === 'currency' ? '0.01' : '0.1'"
                                    :class="column.width !== 'auto' ? column.width : 'w-20'"
                                    class="bg-transparent border-none text-right focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded px-2 py-1"
                                />
                                <span v-else>{{ formatValue(item, column) }}</span>
                            </template>

                            <!-- Text fields -->
                            <template v-else>
                                <input
                                    v-if="editable"
                                    :value="item[column.id]"
                                    @input="updateItem(index, column.id, $event.target.value)"
                                    type="text"
                                    class="w-full bg-transparent border-none focus:outline-none focus:ring-1 focus:ring-indigo-500 rounded px-2 py-1"
                                    :placeholder="column.label"
                                />
                                <span v-else>{{ item[column.id] || '' }}</span>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!content.items?.length">
                        <td :colspan="(editable ? 1 : 0) + visibleColumns.length" class="py-8 text-center italic" :style="{ color: textColor, opacity: 0.5 }">
                            La liste des articles apparaîtra ici
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add Item Button (only in edit mode) -->
        <div v-if="editable" class="flex justify-center pt-4 border-t-2 border-dashed" :style="{ borderColor: primaryColor + '40' }">
            <button
                @click="addItem"
                type="button"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r font-semibold hover:shadow-lg hover:scale-105 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :style="{
                    backgroundColor: primaryColor + '10',
                    borderColor: primaryColor + '30',
                    color: primaryColor,
                    borderWidth: '2px',
                    borderStyle: 'solid'
                }"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Ajouter un poste</span>
            </button>
        </div>
    </div>
</template>
