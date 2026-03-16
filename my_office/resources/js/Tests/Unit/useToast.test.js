import { useToast } from '@/Composables/useToast';
import { beforeEach, describe, expect, it } from 'vitest';

describe('useToast', () => {
    beforeEach(() => {
        // Reset toasts before each test if necessary
        const { toasts } = useToast();
        toasts.value = [];
    });

    it('adds a success toast', () => {
        const { success, toasts } = useToast();
        success('Test success');
        expect(toasts.value).toHaveLength(1);
        expect(toasts.value[0].message).toBe('Test success');
        expect(toasts.value[0].type).toBe('success');
    });

    it('adds an error toast', () => {
        const { error, toasts } = useToast();
        error('Test error');
        expect(toasts.value).toHaveLength(1);
        expect(toasts.value[0].type).toBe('error');
    });

    it('dismisses a toast', () => {
        const { success, dismiss, toasts } = useToast();
        success('Test dismiss');
        const id = toasts.value[0].id;
        dismiss(id);
        expect(toasts.value).toHaveLength(0);
    });
});
