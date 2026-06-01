<script setup>
import { computed } from 'vue';

const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: [Number, String],
        default: null,
    },
    companyDefault: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const selectTemplate = (id) => {
    emit('update:modelValue', id);
    const selected =
        id === null
            ? props.companyDefault
            : props.templates.find((t) => t.id === id);
    emit('change', selected);
};

const options = computed(() => {
    const list = [
        { id: null, name: 'Par défaut (Entreprise)', ...props.companyDefault },
    ];
    return [...list, ...props.templates];
});
</script>

<template>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
        <div
            v-for="tpl in options"
            :key="tpl.id ?? 'default'"
            @click="selectTemplate(tpl.id)"
            class="relative cursor-pointer rounded-lg border-2 p-2 transition-all hover:shadow-md"
            :class="[
                modelValue === tpl.id
                    ? 'border-indigo-600 ring-1 ring-indigo-600'
                    : 'border-gray-200 hover:border-gray-300',
            ]"
        >
            <!-- Mini Preview -->
            <div
                class="mb-2 flex h-20 w-full flex-col space-y-1 overflow-hidden rounded border border-gray-100 bg-gray-50 p-2"
            >
                <!-- Mini Logo Placeholder -->
                <div
                    class="h-4 w-8 rounded-sm bg-gray-200"
                    :style="{ backgroundColor: tpl.primary_color }"
                ></div>
                <!-- Mini Content Lines -->
                <div class="h-1 w-full bg-gray-100"></div>
                <div class="h-1 w-2/3 bg-gray-100"></div>
                <div
                    class="mt-auto h-4 w-full"
                    :style="{ borderTop: `2px solid ${tpl.secondary_color}` }"
                ></div>
            </div>

            <div class="flex items-center justify-between">
                <span
                    class="truncate text-xs font-medium text-gray-700"
                    :title="tpl.name"
                >
                    {{ tpl.name }}
                </span>
                <div
                    v-if="modelValue === tpl.id"
                    class="flex h-3 w-3 items-center justify-center rounded-full bg-indigo-600"
                >
                    <svg
                        class="h-2 w-2 text-white"
                        fill="currentColor"
                        viewBox="0 0 8 8"
                    >
                        <path
                            d="M6.41 0l-.69.72-2.78 2.78-.81-.78-.72-.69-1.41 1.41.69.72 1.5 1.5.69.72.72-.69 3.5-3.5.69-.72-1.41-1.41z"
                            transform="translate(0 1)"
                        />
                    </svg>
                </div>
            </div>

            <div v-if="tpl.is_default" class="mt-1">
                <span
                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-800"
                >
                    Défaut
                </span>
            </div>
        </div>
    </div>
</template>
