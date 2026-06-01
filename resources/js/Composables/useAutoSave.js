import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export function useAutoSave(data, url, options = {}) {
    const {
        delay = 2000,
        method = 'patch',
        preserveScroll = true,
        onSuccess = null,
        onError = null,
    } = options;

    const isSaving = ref(false);
    const lastSaved = ref(null);
    let saveTimeout = null;

    const save = () => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            isSaving.value = true;

            router[method](url, data.value || data, {
                preserveScroll,
                onSuccess: () => {
                    isSaving.value = false;
                    lastSaved.value = new Date();
                    if (onSuccess) onSuccess();
                },
                onError: (errors) => {
                    isSaving.value = false;
                    if (onError) onError(errors);
                },
            });
        }, delay);
    };

    watch(
        () => (data.value ? { ...data.value } : { ...data }),
        () => {
            save();
        },
        { deep: true }
    );

    return {
        isSaving,
        lastSaved,
    };
}
