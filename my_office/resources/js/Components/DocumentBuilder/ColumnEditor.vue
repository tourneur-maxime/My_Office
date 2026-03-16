<script setup>
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    columns: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['update:columns']);

const localColumns = ref([...props.columns]);

const availableTypes = [
    { value: 'text', label: 'Texte' },
    { value: 'number', label: 'Nombre' },
    { value: 'currency', label: 'Devise (€)' },
    { value: 'percent', label: 'Pourcentage (%)' }
];

const availableAlignments = [
    { value: 'left', label: 'Gauche', icon: 'M4 6h16M4 12h10M4 18h14' },
    { value: 'center', label: 'Centre', icon: 'M4 6h16M7 12h10M5 18h14' },
    { value: 'right', label: 'Droite', icon: 'M4 6h16M10 12h10M6 18h14' }
];

const updateColumns = () => {
    emit('update:columns', localColumns.value);
};

const toggleVisibility = (columnId) => {
    const column = localColumns.value.find(col => col.id === columnId);
    if (column) {
        column.visible = !column.visible;
        updateColumns();
    }
};

const updateLabel = (columnId, newLabel) => {
    const column = localColumns.value.find(col => col.id === columnId);
    if (column) {
        column.label = newLabel;
        updateColumns();
    }
};

const updateAlignment = (columnId, newAlignment) => {
    const column = localColumns.value.find(col => col.id === columnId);
    if (column) {
        column.align = newAlignment;
        updateColumns();
    }
};

const addCustomColumn = () => {
    const newId = 'custom_' + Date.now();
    localColumns.value.push({
        id: newId,
        label: 'Nouvelle colonne',
        type: 'text',
        align: 'left',
        visible: true,
        width: 'auto',
        calculated: false
    });
    updateColumns();
};

const removeColumn = (columnId) => {
    if (confirm('Supprimer cette colonne ?')) {
        localColumns.value = localColumns.value.filter(col => col.id !== columnId);
        updateColumns();
    }
};

const onDragEnd = () => {
    updateColumns();
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold text-[hsl(var(--text-main))]">
                Colonnes du tableau
            </h4>
            <button
                @click="addCustomColumn"
                class="text-xs px-2 py-1 bg-[hsl(var(--primary))] text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-1"
                title="Ajouter une colonne personnalisée"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Ajouter
            </button>
        </div>

        <draggable
            v-model="localColumns"
            @end="onDragEnd"
            item-key="id"
            handle=".drag-handle"
            class="space-y-2"
        >
            <template #item="{ element: column }">
                <div
                    class="p-3 rounded-xl border-2 transition-all"
                    :class="[
                        column.visible
                            ? 'bg-[hsl(var(--bg-base))] border-[hsl(var(--border))]'
                            : 'bg-gray-100 dark:bg-gray-800/50 border-gray-300 dark:border-gray-700 opacity-60'
                    ]"
                >
                    <!-- Header Row -->
                    <div class="flex items-center gap-2 mb-2">
                        <button
                            class="drag-handle cursor-move text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))]"
                            title="Glisser pour réorganiser"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                            </svg>
                        </button>

                        <input
                            :value="column.label"
                            @input="updateLabel(column.id, $event.target.value)"
                            type="text"
                            class="flex-1 px-2 py-1 text-sm bg-transparent border border-[hsl(var(--border))] rounded-lg focus:outline-none focus:ring-2 focus:ring-[hsl(var(--primary))]"
                            placeholder="Nom de la colonne"
                        />

                        <button
                            @click="toggleVisibility(column.id)"
                            :class="[
                                'p-1.5 rounded-lg transition-colors',
                                column.visible
                                    ? 'text-[hsl(var(--primary))] bg-[hsl(var(--primary))]/10 hover:bg-[hsl(var(--primary))]/20'
                                    : 'text-[hsl(var(--text-muted))] hover:bg-[hsl(var(--text-main))]/5'
                            ]"
                            :title="column.visible ? 'Masquer' : 'Afficher'"
                        >
                            <svg v-if="column.visible" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>

                        <button
                            v-if="!column.calculated && localColumns.length > 1"
                            @click="removeColumn(column.id)"
                            class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                            title="Supprimer"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Details (shown when visible) -->
                    <div v-if="column.visible && !column.calculated" class="mt-2 pt-2 border-t border-[hsl(var(--border))] space-y-2">
                        <!-- Type selector -->
                        <div>
                            <label class="text-xs text-[hsl(var(--text-muted))] mb-1 block">Type</label>
                            <select
                                :value="column.type"
                                @change="column.type = $event.target.value; updateColumns()"
                                class="w-full px-2 py-1 text-xs bg-[hsl(var(--bg-base))] border border-[hsl(var(--border))] rounded-lg focus:outline-none focus:ring-2 focus:ring-[hsl(var(--primary))]"
                            >
                                <option v-for="type in availableTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Alignment buttons -->
                        <div>
                            <label class="text-xs text-[hsl(var(--text-muted))] mb-1 block">Alignement</label>
                            <div class="flex gap-1">
                                <button
                                    v-for="alignment in availableAlignments"
                                    :key="alignment.value"
                                    @click="updateAlignment(column.id, alignment.value)"
                                    :class="[
                                        'flex-1 p-1.5 rounded-lg transition-colors',
                                        column.align === alignment.value
                                            ? 'bg-[hsl(var(--primary))] text-white'
                                            : 'bg-[hsl(var(--text-main))]/5 text-[hsl(var(--text-muted))] hover:bg-[hsl(var(--text-main))]/10'
                                    ]"
                                    :title="alignment.label"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="alignment.icon" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Calculated indicator -->
                    <div v-if="column.calculated" class="mt-2 pt-2 border-t border-[hsl(var(--border))]">
                        <span class="text-xs text-[hsl(var(--text-muted))] italic flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Colonne calculée automatiquement
                        </span>
                    </div>
                </div>
            </template>
        </draggable>

        <div class="text-xs text-[hsl(var(--text-muted))] italic mt-4">
            💡 Glissez-déposez les colonnes pour les réorganiser
        </div>
    </div>
</template>
