<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmer la suppression',
    },
    message: {
        type: String,
        default:
            'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.',
    },
    confirmText: {
        type: String,
        default: 'Supprimer',
    },
    cancelText: {
        type: String,
        default: 'Annuler',
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
});

const emit = defineEmits(['confirm', 'cancel']);

const close = () => {
    emit('cancel');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <div class="modal" :class="{ 'modal-open': show }" role="dialog">
        <div class="modal-box" :class="`max-w-${maxWidth}`">
            <h3 class="flex items-center gap-2 text-lg font-bold">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6 text-error"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                {{ title }}
            </h3>
            <p class="py-4 text-[hsl(var(--text-muted))]">
                {{ message }}
            </p>
            <div class="modal-action">
                <button type="button" class="btn btn-ghost" @click="close">
                    {{ cancelText }}
                </button>
                <button type="button" class="btn btn-error" @click="confirm">
                    {{ confirmText }}
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="close">
            <button>close</button>
        </div>
    </div>
</template>
