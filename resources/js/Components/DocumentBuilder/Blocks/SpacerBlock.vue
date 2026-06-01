<script setup>
defineProps({
    content: Object,
});
</script>

<template>
    <div
        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center text-gray-400 dark:text-gray-500"
        :style="{
            height: (content.height || 50) + 'px',
            minHeight: '20px'
        }"
    >
        <span class="text-xs opacity-50">Espaceur</span>
    </div>
</template>
