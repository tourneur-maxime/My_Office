<script setup>
import { computed } from 'vue';

const props = defineProps({
    isSaving: Boolean,
    lastSaved: Date,
});

const lastSavedText = computed(() => {
    if (!props.lastSaved) return null;

    const now = new Date();
    const diff = Math.floor((now - props.lastSaved) / 1000);

    if (diff < 5) return 'À l\'instant';
    if (diff < 60) return `Il y a ${diff}s`;
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)}min`;
    return props.lastSaved.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
});
</script>

<template>
    <div class="flex items-center gap-2 text-xs text-[hsl(var(--text-muted))]">
        <div v-if="isSaving" class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
            <span>Enregistrement...</span>
        </div>
        <div v-else-if="lastSaved" class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
            <span>{{ lastSavedText }}</span>
        </div>
    </div>
</template>
