<script setup>
defineProps({
    text: {
        type: String,
        required: true,
    },
    position: {
        type: String,
        default: 'tooltip-top',
    },
});
</script>

<template>
    <div :class="['tooltip cursor-help', position]" :data-tip="text">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            class="h-4 w-4 shrink-0 stroke-current text-info"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            ></path>
        </svg>
    </div>
</template>
