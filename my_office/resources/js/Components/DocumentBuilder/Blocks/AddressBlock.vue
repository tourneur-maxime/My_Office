<script setup>
defineProps({
    content: Object,
    textColor: {
        type: String,
        default: '#1F2937'
    }
});
</script>

<template>
    <div class="space-y-1 text-sm">
        <div v-if="content.label" class="text-xs font-bold uppercase tracking-wider" :style="{ color: textColor, opacity: 0.7 }">{{ content.label }}</div>
        <div class="whitespace-pre-line" :style="{ color: textColor }">{{ content.address || 'Adresse ici...' }}</div>
    </div>
</template>
