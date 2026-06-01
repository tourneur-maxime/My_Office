<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
            color: '#1F2937',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textAlign: 'left',
        })
    }
});

const emit = defineEmits(['update:modelValue']);

const style = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const updateStyle = (key, value) => {
    emit('update:modelValue', { ...style.value, [key]: value });
};

const fontFamilies = [
    { value: 'Inter, sans-serif', label: 'Inter' },
    { value: 'Arial, sans-serif', label: 'Arial' },
    { value: 'Georgia, serif', label: 'Georgia' },
    { value: 'Courier New, monospace', label: 'Courier' },
    { value: 'Times New Roman, serif', label: 'Times' },
    { value: 'Verdana, sans-serif', label: 'Verdana' },
];

const fontSizes = ['10px', '12px', '14px', '16px', '18px', '20px', '24px'];
</script>

<template>
    <div class="space-y-4">
        <h4 class="text-sm font-bold text-gray-700 dark:text-white mb-3">Style de la Description</h4>

        <!-- Font Family -->
        <div>
            <label class="glass-label text-xs mb-2">Police</label>
            <select
                :value="style.fontFamily"
                @change="updateStyle('fontFamily', $event.target.value)"
                class="glass-select text-sm py-2.5 px-4 w-full"
            >
                <option v-for="font in fontFamilies" :key="font.value" :value="font.value">
                    {{ font.label }}
                </option>
            </select>
        </div>

        <!-- Font Size -->
        <div>
            <label class="glass-label text-xs mb-2">Taille</label>
            <select
                :value="style.fontSize"
                @change="updateStyle('fontSize', $event.target.value)"
                class="glass-select text-sm py-2.5 px-4 w-full"
            >
                <option v-for="size in fontSizes" :key="size" :value="size">
                    {{ size }}
                </option>
            </select>
        </div>

        <!-- Font Color -->
        <div>
            <label class="glass-label text-xs mb-2">Couleur</label>
            <div class="flex items-center gap-3">
                <input
                    type="color"
                    :value="style.color"
                    @input="updateStyle('color', $event.target.value)"
                    class="h-10 w-20 rounded-xl cursor-pointer border-0 shadow-sm"
                />
                <input
                    type="text"
                    :value="style.color"
                    @input="updateStyle('color', $event.target.value)"
                    class="glass-input flex-1 text-xs font-mono py-2 px-3"
                    placeholder="#1F2937"
                />
            </div>
        </div>

        <!-- Font Weight -->
        <div>
            <label class="glass-label text-xs mb-2">Graisse</label>
            <select
                :value="style.fontWeight"
                @change="updateStyle('fontWeight', $event.target.value)"
                class="glass-select text-sm py-2.5 px-4 w-full"
            >
                <option value="normal">Normal</option>
                <option value="bold">Gras</option>
                <option value="lighter">Léger</option>
            </select>
        </div>

        <!-- Font Style -->
        <div>
            <label class="glass-label text-xs mb-2">Style</label>
            <select
                :value="style.fontStyle"
                @change="updateStyle('fontStyle', $event.target.value)"
                class="glass-select text-sm py-2.5 px-4 w-full"
            >
                <option value="normal">Normal</option>
                <option value="italic">Italique</option>
            </select>
        </div>

        <!-- Text Align -->
        <div>
            <label class="glass-label text-xs mb-2">Alignement</label>
            <div class="flex gap-2">
                <button
                    v-for="align in ['left', 'center', 'right']"
                    :key="align"
                    @click="updateStyle('textAlign', align)"
                    :class="[
                        'flex-1 px-3 py-2 rounded-lg transition-colors',
                        style.textAlign === align
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-indigo-600 hover:text-white'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path v-if="align === 'left'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14" />
                        <path v-else-if="align === 'center'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
