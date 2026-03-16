<script setup>
import { ref } from 'vue';

defineProps({
    text: {
        type: String,
        required: true,
    },
    position: {
        type: String,
        default: 'top',
        validator: (value) =>
            ['top', 'bottom', 'left', 'right'].includes(value),
    },
});

const isVisible = ref(false);

const showTooltip = () => {
    isVisible.value = true;
};

const hideTooltip = () => {
    isVisible.value = false;
};

const positionClasses = {
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
    left: 'right-full top-1/2 -translate-y-1/2 mr-2',
    right: 'left-full top-1/2 -translate-y-1/2 ml-2',
};

const arrowClasses = {
    top: 'top-full left-1/2 -translate-x-1/2 border-t-[hsl(var(--bg-elevated))]',
    bottom: 'bottom-full left-1/2 -translate-x-1/2 border-b-[hsl(var(--bg-elevated))]',
    left: 'left-full top-1/2 -translate-y-1/2 border-l-[hsl(var(--bg-elevated))]',
    right: 'right-full top-1/2 -translate-y-1/2 border-r-[hsl(var(--bg-elevated))]',
};
</script>

<template>
    <div class="relative inline-block">
        <div
            @mouseenter="showTooltip"
            @mouseleave="hideTooltip"
            @focus="showTooltip"
            @blur="hideTooltip"
        >
            <slot>
                <span class="cursor-help text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </span>
            </slot>
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isVisible"
                :class="[
                    'absolute z-50 whitespace-nowrap rounded-lg bg-[hsl(var(--bg-elevated))] px-3 py-2 text-sm text-[hsl(var(--text-main))] border border-[hsl(var(--border))] shadow-lg',
                    positionClasses[position],
                ]"
            >
                {{ text }}
                <div
                    :class="[
                        'absolute h-0 w-0 border-4 border-transparent',
                        arrowClasses[position],
                    ]"
                ></div>
            </div>
        </Transition>
    </div>
</template>
