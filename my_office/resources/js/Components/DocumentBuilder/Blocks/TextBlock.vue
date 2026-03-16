<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        default: () => ({
            text: 'Cliquez pour éditer le texte...',
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
            color: '#1F2937',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textDecoration: 'none',
            textAlign: 'left',
            lineHeight: '1.5',
        })
    },
    primaryColor: String,
    textColor: {
        type: String,
        default: '#1F2937'
    },
    editable: {
        type: Boolean,
        default: false
    },
    onUpdate: Function
});

const isEditing = ref(false);
const localText = ref(props.content.text);

const textStyle = computed(() => ({
    fontSize: props.content.fontSize || '14px',
    fontFamily: props.content.fontFamily || 'Inter, sans-serif',
    color: props.content.color || props.textColor,
    fontWeight: props.content.fontWeight || 'normal',
    fontStyle: props.content.fontStyle || 'normal',
    textDecoration: props.content.textDecoration || 'none',
    textAlign: props.content.textAlign || 'left',
    lineHeight: props.content.lineHeight || '1.5',
}));

const startEditing = () => {
    if (props.editable) {
        isEditing.value = true;
        localText.value = props.content.text;
    }
};

const saveText = () => {
    if (props.onUpdate) {
        props.onUpdate('text', localText.value);
    }
    isEditing.value = false;
};

const updateStyle = (key, value) => {
    if (props.onUpdate) {
        props.onUpdate(key, value);
    }
};
</script>

<template>
    <div class="text-block">
        <!-- Edit Mode -->
        <div v-if="editable && isEditing" class="space-y-4">
            <textarea
                v-model="localText"
                @blur="saveText"
                @keydown.esc="isEditing = false"
                class="w-full min-h-[100px] p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
                :style="textStyle"
                autofocus
            ></textarea>
        </div>

        <!-- Display Mode -->
        <div
            v-else
            @click="startEditing"
            :class="{ 'cursor-text hover:bg-gray-50 dark:hover:bg-gray-800/30 p-2 rounded transition-colors': editable }"
            :style="textStyle"
            class="whitespace-pre-wrap"
        >
            {{ content.text }}
        </div>
    </div>
</template>

<style scoped>
.text-block {
    width: 100%;
}
</style>
