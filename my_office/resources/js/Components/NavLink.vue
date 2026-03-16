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
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[hsl(var(--primary))] text-sm font-medium leading-5 text-[hsl(var(--text-main))] focus:outline-none focus:border-[hsl(var(--primary))] transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))] hover:border-[hsl(var(--border))] focus:outline-none focus:text-[hsl(var(--text-main))] focus:border-[hsl(var(--border))] transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
