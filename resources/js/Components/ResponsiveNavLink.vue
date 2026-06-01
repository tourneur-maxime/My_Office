<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[hsl(var(--primary))] text-start text-base font-medium text-[hsl(var(--primary))] bg-[hsl(var(--primary))]/10 focus:outline-none focus:text-[hsl(var(--primary))] focus:bg-[hsl(var(--primary))]/15 focus:border-[hsl(var(--primary))] transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))] hover:bg-[hsl(var(--bg-surface))]/50 hover:border-[hsl(var(--border))] focus:outline-none focus:text-[hsl(var(--text-main))] focus:bg-[hsl(var(--bg-surface))]/50 focus:border-[hsl(var(--border))] transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
