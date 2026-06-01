<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import { Color } from '@tiptap/extension-color';
import { watch } from 'vue';

const props = defineProps({
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    extensions: [
        StarterKit,
        Underline,
        Color,
    ],
    content: props.modelValue || '',
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value || '');
    }
});

const toggleBold = () => editor.value?.chain().focus().toggleBold().run();
const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run();
const toggleUnderline = () => editor.value?.chain().focus().toggleUnderline().run();

// Font sizes
const setFontSize = (size) => {
    editor.value?.chain().focus().setMark('textStyle', { fontSize: size }).run();
};

// Text colors
const setColor = (color) => {
    editor.value?.chain().focus().setColor(color).run();
};
</script>

<template>
    <div class="space-y-2">
        <!-- Toolbar -->
        <div class="flex gap-1 border-b pb-2 flex-wrap items-center">
            <!-- Bold, Italic, Underline -->
            <button
                @click="toggleBold"
                :class="editor?.isActive('bold') ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'"
                class="px-3 py-1 rounded text-sm font-bold hover:bg-indigo-600 hover:text-white transition"
                type="button"
                title="Gras"
            >
                G
            </button>
            <button
                @click="toggleItalic"
                :class="editor?.isActive('italic') ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'"
                class="px-3 py-1 rounded text-sm italic hover:bg-indigo-600 hover:text-white transition"
                type="button"
                title="Italique"
            >
                I
            </button>
            <button
                @click="toggleUnderline"
                :class="editor?.isActive('underline') ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200'"
                class="px-3 py-1 rounded text-sm underline hover:bg-indigo-600 hover:text-white transition"
                type="button"
                title="Souligné"
            >
                U
            </button>

            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

            <!-- Font Size -->
            <select
                @change="setFontSize($event.target.value)"
                class="px-2 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border-none focus:ring-1 focus:ring-indigo-500"
                title="Taille"
            >
                <option value="">Taille</option>
                <option value="12px">12px</option>
                <option value="14px">14px</option>
                <option value="16px">16px</option>
                <option value="18px">18px</option>
                <option value="20px">20px</option>
                <option value="24px">24px</option>
            </select>

            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

            <!-- Text Color -->
            <div class="flex gap-1">
                <button
                    v-for="color in ['#000000', '#991B1B', '#1E40AF', '#065F46', '#7C2D12', '#6B21A8']"
                    :key="color"
                    @click="setColor(color)"
                    :style="{ backgroundColor: color }"
                    class="w-6 h-6 rounded border-2 border-gray-300 dark:border-gray-600 hover:scale-110 transition"
                    type="button"
                    :title="'Couleur ' + color"
                ></button>
            </div>
        </div>

        <!-- Editor Content -->
        <EditorContent
            :editor="editor"
            class="prose prose-sm max-w-none min-h-[80px] px-3 py-2 bg-transparent rounded border border-gray-300 dark:border-gray-600 focus-within:ring-1 focus-within:ring-indigo-500"
        />
    </div>
</template>

<style>
/* Tiptap editor styles */
.ProseMirror {
    outline: none;
    min-height: 60px;
}

.ProseMirror p {
    margin: 0.5em 0;
}

.ProseMirror p:first-child {
    margin-top: 0;
}

.ProseMirror p:last-child {
    margin-bottom: 0;
}
</style>
