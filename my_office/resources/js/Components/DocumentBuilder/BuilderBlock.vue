<script setup>
defineProps({
    id: String,
    isSelected: Boolean,
});
defineEmits(['remove', 'select']);
</script>

<template>
    <div 
        class="group relative border-2 border-transparent transition-all duration-200 hover:border-blue-300"
        :class="{ 'border-blue-500 ring-2 ring-blue-500/20': isSelected }"
        @click="$emit('select', id)"
    >
        <!-- Drag Handle -->
        <div class="drag-handle absolute -left-8 top-1/2 -translate-y-1/2 cursor-grab opacity-0 group-hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 15h8" />
            </svg>
        </div>

        <!-- Action Buttons -->
        <div class="absolute -right-2 -top-2 z-10 hidden space-x-1 group-hover:flex">
            <button 
                @click.stop="$emit('remove', id)"
                class="rounded-full bg-red-500 p-1 text-white shadow-sm hover:bg-red-600"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Block Content -->
        <div class="bg-white">
            <slot />
        </div>
    </div>
</template>
