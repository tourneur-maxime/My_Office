import ConfirmationModal from '@/Components/Common/ConfirmationModal.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ConfirmationModal', () => {
    it('is hidden by default', () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: false },
        });
        expect(wrapper.find('.modal').classes()).not.toContain('modal-open');
    });

    it('is visible when show prop is true', () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
        });
        expect(wrapper.find('.modal').classes()).toContain('modal-open');
    });

    it('displays the title and message', () => {
        const wrapper = mount(ConfirmationModal, {
            props: {
                show: true,
                title: 'Custom Title',
                message: 'Custom Message',
            },
        });
        expect(wrapper.text()).toContain('Custom Title');
        expect(wrapper.text()).toContain('Custom Message');
    });

    it('emits confirm when confirm button is clicked', async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
        });
        await wrapper.find('button.btn-error').trigger('click');
        expect(wrapper.emitted()).toHaveProperty('confirm');
    });

    it('emits cancel when cancel button is clicked', async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
        });
        await wrapper.find('button.btn-ghost').trigger('click');
        expect(wrapper.emitted()).toHaveProperty('cancel');
    });
});
