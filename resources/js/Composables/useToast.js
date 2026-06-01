import { ref } from 'vue';

const toasts = ref([]);
let toastCount = 0;

export function useToast() {
    const show = (message, type = 'info', duration = 5000) => {
        const id = ++toastCount;
        const toast = { id, message, type, duration };
        toasts.value = [...toasts.value, toast];

        if (duration > 0) {
            setTimeout(() => {
                dismiss(id);
            }, duration);
        }
    };

    const success = (message, duration) => show(message, 'success', duration);
    const error = (message, duration) => show(message, 'error', duration);
    const warning = (message, duration) => show(message, 'warning', duration);
    const info = (message, duration) => show(message, 'info', duration);

    const dismiss = (id) => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    };

    return {
        toasts,
        success,
        error,
        warning,
        info,
        dismiss,
    };
}
