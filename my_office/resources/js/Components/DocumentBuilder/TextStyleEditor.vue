<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
            color: '#1F2937',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textDecoration: 'none',
            textAlign: 'left',
        })
    }
});

const emit = defineEmits(['update:modelValue']);

const localStyle = ref({ ...props.modelValue });

watch(() => props.modelValue, (newVal) => {
    localStyle.value = { ...newVal };
}, { deep: true });

const updateStyle = (key, value) => {
    localStyle.value[key] = value;
    emit('update:modelValue', { ...localStyle.value });
};

const fontSizes = ['10px', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px', '36px', '48px'];
const fontFamilies = [
    { label: 'Inter (Défaut)', value: 'Inter, sans-serif' },
    { label: 'Arial', value: 'Arial, sans-serif' },
    { label: 'Times New Roman', value: '"Times New Roman", serif' },
    { label: 'Georgia', value: 'Georgia, serif' },
    { label: 'Courier New', value: '"Courier New", monospace' },
    { label: 'Verdana', value: 'Verdana, sans-serif' },
];
</script>

<template>
    <div class="space-y-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Personnalisation du texte</h4>

        <!-- Font Family -->
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Police</label>
            <select
                :value="localStyle.fontFamily"
                @change="updateStyle('fontFamily', $event.target.value)"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-700"
            >
                <option v-for="font in fontFamilies" :key="font.value" :value="font.value">
                    {{ font.label }}
                </option>
            </select>
        </div>

        <!-- Font Size -->
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Taille</label>
            <select
                :value="localStyle.fontSize"
                @change="updateStyle('fontSize', $event.target.value)"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-700"
            >
                <option v-for="size in fontSizes" :key="size" :value="size">
                    {{ size }}
                </option>
            </select>
        </div>

        <!-- Color -->
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Couleur</label>
            <div class="flex gap-2 items-center">
                <input
                    type="color"
                    :value="localStyle.color"
                    @input="updateStyle('color', $event.target.value)"
                    class="h-10 w-20 rounded cursor-pointer border border-gray-300 dark:border-gray-600"
                />
                <input
                    type="text"
                    :value="localStyle.color"
                    @input="updateStyle('color', $event.target.value)"
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-gray-700 font-mono"
                    placeholder="#000000"
                />
            </div>
        </div>

        <!-- Text Style Buttons -->
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Style</label>
            <div class="flex gap-2">
                <!-- Bold -->
                <button
                    type="button"
                    @click="updateStyle('fontWeight', localStyle.fontWeight === 'bold' ? 'normal' : 'bold')"
                    :class="localStyle.fontWeight === 'bold' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 font-bold hover:bg-indigo-500 hover:text-white transition-colors"
                >
                    B
                </button>

                <!-- Italic -->
                <button
                    type="button"
                    @click="updateStyle('fontStyle', localStyle.fontStyle === 'italic' ? 'normal' : 'italic')"
                    :class="localStyle.fontStyle === 'italic' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 italic hover:bg-indigo-500 hover:text-white transition-colors"
                >
                    I
                </button>

                <!-- Underline -->
                <button
                    type="button"
                    @click="updateStyle('textDecoration', localStyle.textDecoration === 'underline' ? 'none' : 'underline')"
                    :class="localStyle.textDecoration === 'underline' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 underline hover:bg-indigo-500 hover:text-white transition-colors"
                >
                    U
                </button>
            </div>
        </div>

        <!-- Text Align -->
        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Alignement</label>
            <div class="flex gap-2">
                <button
                    type="button"
                    @click="updateStyle('textAlign', 'left')"
                    :class="localStyle.textAlign === 'left' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-indigo-500 hover:text-white transition-colors"
                    title="Aligné à gauche"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="updateStyle('textAlign', 'center')"
                    :class="localStyle.textAlign === 'center' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-indigo-500 hover:text-white transition-colors"
                    title="Centré"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="updateStyle('textAlign', 'right')"
                    :class="localStyle.textAlign === 'right' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-indigo-500 hover:text-white transition-colors"
                    title="Aligné à droite"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    @click="updateStyle('textAlign', 'justify')"
                    :class="localStyle.textAlign === 'justify' ? 'bg-indigo-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-indigo-500 hover:text-white transition-colors"
                    title="Justifié"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
