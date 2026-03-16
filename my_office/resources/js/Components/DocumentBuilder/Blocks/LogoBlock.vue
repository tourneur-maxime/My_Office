<script setup>
defineProps({
    content: Object,
});
</script>

<template>
    <div :class="['flex', { 'justify-start': content.position === 'left', 'justify-center': content.position === 'center', 'justify-end': content.position === 'right' }]">
        <img v-if="content.url" :src="content.url" :style="{ maxHeight: (content.size || 100) + 'px' }" class="object-contain" />
        <div v-else class="flex h-16 w-32 items-center justify-center border-2 border-dashed text-gray-400">Logo</div>
    </div>
</template>
