import { defineStore } from 'pinia';

export const useDocumentStore = defineStore('document', {
    state: () => ({
        blocks: [],
        globalSettings: {
            primaryColor: '#6366F1',
            secondaryColor: '#4F46E5',
            fontFamily: 'sans-serif',
            textColor: '#1F2937',
            backgroundColor: '#FFFFFF',
            blockBackgrounds: {}, // Per-block backgrounds { blockId: color }
        },
        documentType: 'quote', // 'quote' or 'invoice'
    }),
    actions: {
        setDocumentType(type) {
            this.documentType = type;
        },
        addBlock(type, content = {}, style = {}, layout = {}) {
            const id = typeof crypto !== 'undefined' && crypto.randomUUID
                ? crypto.randomUUID()
                : 'block-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

            const block = {
                id,
                type,
                content,
                style: {
                    backgroundColor: '#FFFFFF',
                    textColor: '#1F2937',
                    ...style
                },
                layout: {
                    width: 100, // Percentage (100 = full width)
                    row: null, // Row ID for grouping blocks in same row
                    ...layout
                }
            };

            this.blocks.push(block);
            return block; // Return the created block
        },
        removeBlock(id) {
            this.blocks = this.blocks.filter(b => b.id !== id);
        },
        updateBlock(id, content) {
            const index = this.blocks.findIndex(b => b.id === id);
            if (index !== -1) {
                this.blocks[index].content = { ...this.blocks[index].content, ...content };
            }
        },
        updateBlockStyle(id, style) {
            const index = this.blocks.findIndex(b => b.id === id);
            if (index !== -1) {
                this.blocks[index].style = { ...this.blocks[index].style, ...style };
            }
        },
        updateBlockLayout(id, layout) {
            const index = this.blocks.findIndex(b => b.id === id);
            if (index !== -1) {
                this.blocks[index].layout = { ...this.blocks[index].layout, ...layout };
            }
        },
        reorderBlocks(newBlocks) {
            this.blocks = newBlocks;
        },
        setGlobalSettings(settings) {
            this.globalSettings = { ...this.globalSettings, ...settings };
        },
        reset() {
            this.blocks = [];
            this.globalSettings = {
                primaryColor: '#6366F1',
                secondaryColor: '#4F46E5',
                fontFamily: 'sans-serif',
                textColor: '#1F2937',
                backgroundColor: '#FFFFFF',
                blockBackgrounds: {},
            };
        }
    }
});
