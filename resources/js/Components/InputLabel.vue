<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="glass-label">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
