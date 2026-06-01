import { useDocumentStore } from '@/Stores/useDocumentStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

describe('useDocumentStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        let count = 0;
        Object.defineProperty(global, 'crypto', {
            value: {
                randomUUID: vi.fn(() => `uuid-${count++}`)
            },
            configurable: true
        });
    });

    it('adds a block', () => {
        const store = useDocumentStore();
        store.addBlock('AddressBlock', { address: 'Test' });
        expect(store.blocks).toHaveLength(1);
        expect(store.blocks[0].type).toBe('AddressBlock');
        expect(store.blocks[0].id).toBe('uuid-0');
    });

    it('removes a block', () => {
        const store = useDocumentStore();
        store.addBlock('AddressBlock');
        const id = store.blocks[0].id;
        store.removeBlock(id);
        expect(store.blocks).toHaveLength(0);
    });

    it('reorders blocks', () => {
        const store = useDocumentStore();
        store.addBlock('Block1'); // uuid-0
        store.addBlock('Block2'); // uuid-1
        const b1 = store.blocks[0];
        const b2 = store.blocks[1];
        store.reorderBlocks([b2, b1]);
        expect(store.blocks[0].type).toBe('Block2');
        expect(store.blocks[1].type).toBe('Block1');
    });
});
