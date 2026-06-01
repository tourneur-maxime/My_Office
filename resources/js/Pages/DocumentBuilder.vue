<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import draggable from 'vuedraggable';
import { useDocumentStore } from '@/Stores/useDocumentStore';
import AutoSaveIndicator from '@/Components/Common/AutoSaveIndicator.vue';
import BuilderBlock from '@/Components/DocumentBuilder/BuilderBlock.vue';
import TextStyleEditor from '@/Components/DocumentBuilder/TextStyleEditor.vue';
import ColumnEditor from '@/Components/DocumentBuilder/ColumnEditor.vue';
import SaveTemplateModal from '@/Components/DocumentBuilder/SaveTemplateModal.vue';
import TemplateManager from '@/Components/DocumentBuilder/TemplateManager.vue';
import AddressBlock from '@/Components/DocumentBuilder/Blocks/AddressBlock.vue';
import LogoBlock from '@/Components/DocumentBuilder/Blocks/LogoBlock.vue';
import LineItemsBlock from '@/Components/DocumentBuilder/Blocks/LineItemsBlock.vue';
import TotalsBlock from '@/Components/DocumentBuilder/Blocks/TotalsBlock.vue';
import TextBlock from '@/Components/DocumentBuilder/Blocks/TextBlock.vue';
import SpacerBlock from '@/Components/DocumentBuilder/Blocks/SpacerBlock.vue';

// Component mapping for dynamic rendering
const components = {
    LogoBlock,
    AddressBlock,
    LineItemsBlock,
    TotalsBlock,
    TextBlock,
    SpacerBlock,
};

// Available blocks for the palette
const availableBlocks = [
    { 
        type: 'LogoBlock', 
        label: 'Logo', 
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>`
    },
    { 
        type: 'AddressBlock', 
        label: 'Adresse', 
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>`
    },
    { 
        type: 'LineItemsBlock', 
        label: 'Articles', 
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>`
    },
    {
        type: 'TotalsBlock',
        label: 'Totaux',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>`
    },
    {
        type: 'TextBlock',
        label: 'Texte libre',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>`
    },
    {
        type: 'SpacerBlock',
        label: 'Espaceur',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16" />
        </svg>`
    },
];

const props = defineProps({
    type: String, // 'quote' or 'invoice'
    client: Object,
    clients: Array,
    templates: Array,
    companyProfile: Object,
    initialData: {
        type: Object,
        default: () => ({})
    },
});

const store = useDocumentStore();
store.setDocumentType(props.type);

// Default columns for LineItemsBlock
const getDefaultColumns = () => [
    { id: 'description', label: 'Description', type: 'text', align: 'left', visible: true, width: 'auto' },
    { id: 'quantity', label: 'Qté', type: 'number', align: 'right', visible: true, width: 'w-24' },
    { id: 'unit_price', label: 'PU HT', type: 'currency', align: 'right', visible: true, width: 'w-28' },
    { id: 'vat_rate', label: 'TVA %', type: 'percent', align: 'right', visible: true, width: 'w-20' },
    { id: 'total', label: 'Total HT', type: 'currency', align: 'right', visible: true, width: 'w-32', calculated: true }
];

// Initialize with some default blocks if empty
if (store.blocks.length === 0) {
    // Logo
    store.addBlock('LogoBlock', {
        url: props.companyProfile?.logo_path ? `/storage/${props.companyProfile.logo_path}` : null,
        size: 100,
        position: 'left'
    });

    // Address blocks - company info (50% width)
    const companyAddress = [
        props.companyProfile?.name,
        props.companyProfile?.address,
        props.companyProfile?.email,
        props.companyProfile?.phone,
        props.companyProfile?.siret ? `SIRET: ${props.companyProfile.siret}` : null,
    ].filter(Boolean).join('\n');

    const companyBlock = store.addBlock('AddressBlock', {
        label: 'De la part de',
        address: companyAddress
    });
    // Set to 50% width
    store.updateBlockLayout(companyBlock.id, { width: 50 });

    // Client/Prospect address block (50% width) - empty at start, will be filled when client is selected
    const clientBlock = store.addBlock('AddressBlock', {
        label: 'Destinataire',
        address: '',
        isClientBlock: true // Mark this as the client block
    });
    store.updateBlockLayout(clientBlock.id, { width: 50 });

    // Line items and totals
    store.addBlock('LineItemsBlock', { items: [], columns: getDefaultColumns() });
    store.addBlock('TotalsBlock', { subtotal: 0, vat_amount: 0, vat_details: {}, total: 0 });
}

const selectedBlockId = ref(null);
const selectedBlock = computed(() => store.blocks.find(b => b.id === selectedBlockId.value));

// Logo upload handling
const logoPreview = ref(null);
const logoForm = useForm({
    logo: null,
});

const handleLogoFileChange = (event) => {
    const file = event.target.files[0];
    if (file && selectedBlock.value && selectedBlock.value.type === 'LogoBlock') {
        logoForm.logo = file;

        // Upload the logo first
        logoForm.post(route('logo.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (response) => {
                // Get the new logo path from the updated companyProfile
                const newLogoPath = response.props.companyProfile?.logo_path;
                if (newLogoPath) {
                    const logoUrl = `/storage/${newLogoPath}`;
                    selectedBlock.value.content.url = logoUrl;
                    logoPreview.value = logoUrl;
                }
                logoForm.reset();
            },
            onError: (errors) => {
                console.error('Logo upload failed:', errors);
                alert('Erreur lors du téléchargement du logo');
            }
        });
    }
};

const resetLogoToDefault = () => {
    if (selectedBlock.value && selectedBlock.value.type === 'LogoBlock') {
        selectedBlock.value.content.url = props.companyProfile?.logo_path
            ? `/storage/${props.companyProfile.logo_path}`
            : null;
        logoPreview.value = null;
    }
};

// Add block from palette
const addBlock = (blockType) => {
    const blockDefaults = {
        LogoBlock: { url: props.companyProfile?.logo_path ? `/storage/${props.companyProfile.logo_path}` : null, size: 100, position: 'left' },
        AddressBlock: { label: 'Adresse', address: '', isClientBlock: false },
        LineItemsBlock: { items: [], columns: getDefaultColumns() },
        TotalsBlock: { subtotal: 0, vat_amount: 0, vat_details: {}, total: 0 },
        TextBlock: {
            text: 'Cliquez pour éditer ce texte...',
            fontSize: '14px',
            fontFamily: 'Inter, sans-serif',
            color: '#1F2937',
            fontWeight: 'normal',
            fontStyle: 'normal',
            textDecoration: 'none',
            textAlign: 'left',
            lineHeight: '1.5',
        },
        SpacerBlock: {
            height: 50,
        },
    };
    store.addBlock(blockType, blockDefaults[blockType] || {});
};

// Line items management
const addLineItem = () => {
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    if (itemsBlock) {
        itemsBlock.content.items.push({
            id: Date.now(),
            description: '',
            quantity: 1,
            unit_price: 0,
            vat_rate: 20, // Taux de TVA par défaut 20%
        });
        updateTotals();
    }
};

const removeLineItem = (itemId) => {
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    if (itemsBlock) {
        itemsBlock.content.items = itemsBlock.content.items.filter(i => i.id !== itemId);
        updateTotals();
    }
};

const moveLineItem = (itemId, direction) => {
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    if (!itemsBlock) return;

    const index = itemsBlock.content.items.findIndex(i => i.id === itemId);
    if (direction === 'up' && index > 0) {
        [itemsBlock.content.items[index], itemsBlock.content.items[index - 1]] =
        [itemsBlock.content.items[index - 1], itemsBlock.content.items[index]];
    } else if (direction === 'down' && index < itemsBlock.content.items.length - 1) {
        [itemsBlock.content.items[index], itemsBlock.content.items[index + 1]] =
        [itemsBlock.content.items[index + 1], itemsBlock.content.items[index]];
    }
};

const updateLineItem = (itemId, updates) => {
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    if (itemsBlock) {
        const item = itemsBlock.content.items.find(i => i.id === itemId);
        if (item) {
            Object.assign(item, updates);
            updateTotals();
        }
    }
};

// Calculate and update totals based on line items
const updateTotals = () => {
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    const totalsBlock = store.blocks.find(b => b.type === 'TotalsBlock');

    if (!itemsBlock || !totalsBlock) return;

    const items = itemsBlock.content.items || [];

    // Calculate subtotal HT
    const subtotal = items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.unit_price) || 0;
        return sum + (qty * price);
    }, 0);

    // Calculate VAT grouped by rate
    const vatByRate = {};
    items.forEach(item => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.unit_price) || 0;
        const vatRate = parseFloat(item.vat_rate) || 0;
        const lineHT = qty * price;
        const vatAmount = lineHT * (vatRate / 100);

        if (!vatByRate[vatRate]) {
            vatByRate[vatRate] = 0;
        }
        vatByRate[vatRate] += vatAmount;
    });

    // Total VAT amount
    const vat_amount = Object.values(vatByRate).reduce((sum, amount) => sum + amount, 0);

    // Total TTC
    const total = subtotal + vat_amount;

    // Update TotalsBlock content
    totalsBlock.content = {
        subtotal: Math.round(subtotal * 100) / 100,
        vat_amount: Math.round(vat_amount * 100) / 100,
        vat_details: vatByRate, // Store VAT breakdown by rate
        total: Math.round(total * 100) / 100,
    };
};

// Update columns for LineItemsBlock
const updateColumns = (newColumns) => {
    if (selectedBlock.value && selectedBlock.value.type === 'LineItemsBlock') {
        selectedBlock.value.content.columns = newColumns;
    }
};

const form = useForm({
    client_id: props.client?.id || null,
    line_items: [],
    template_id: null,
    expires_at: null,
    layout_configuration: [],
});

// Watch for client selection and update the client address block
watch(() => form.client_id, (newClientId) => {
    if (newClientId) {
        const selectedClient = props.clients.find(c => c.id === newClientId);
        if (selectedClient) {
            // Find the client address block
            const clientBlock = store.blocks.find(b => b.type === 'AddressBlock' && b.content.isClientBlock);
            if (clientBlock) {
                const clientAddress = [
                    selectedClient.company || selectedClient.name,
                    selectedClient.name !== selectedClient.company ? selectedClient.name : null,
                    selectedClient.address,
                    selectedClient.zip_code && selectedClient.city ? `${selectedClient.zip_code} ${selectedClient.city}` : null,
                    selectedClient.email,
                    selectedClient.phone,
                    selectedClient.siret ? `SIRET: ${selectedClient.siret}` : null,
                ].filter(Boolean).join('\n');

                clientBlock.content.address = clientAddress;
            }
        }
    }
});

// Template management
const showSaveTemplateModal = ref(false);
const showTemplateManager = ref(false);

const saveTemplate = async (templateName) => {
    try {
        const response = await fetch(route('document-templates.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name: templateName,
                type: props.type,
                blocks: store.blocks,
            }),
        });

        if (response.ok) {
            showSaveTemplateModal.value = false;
            // Show success message
            alert('Template sauvegardé avec succès !');
        } else {
            alert('Erreur lors de la sauvegarde du template');
        }
    } catch (error) {
        console.error('Error saving template:', error);
        alert('Erreur lors de la sauvegarde du template');
    }
};

const loadTemplate = (template) => {
    if (confirm('Charger ce template remplacera la configuration actuelle. Continuer ?')) {
        // Clear current blocks
        store.blocks = [];

        // Load template blocks
        if (template.blocks && Array.isArray(template.blocks)) {
            template.blocks.forEach(block => {
                store.addBlock(block.type, block.content);
            });
        }

        showTemplateManager.value = false;

        // Update totals if there's a LineItemsBlock
        updateTotals();
    }
};

// Auto-save state
const lastSaved = ref(null);
const isSaving = ref(false);
let autoSaveTimeout = null;

// Watch for changes to trigger auto-save
watch([() => store.blocks, () => store.globalSettings], () => {
    triggerAutoSave();
}, { deep: true });

const triggerAutoSave = () => {
    if (!form.client_id) return; // Don't auto-save without a client

    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        autoSave();
    }, 3000); // 3 seconds delay
};

const autoSave = () => {
    if (isSaving.value) return;

    isSaving.value = true;
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');

    // For now, we just update the state
    // In a real app, you'd save to localStorage or backend
    setTimeout(() => {
        lastSaved.value = new Date();
        isSaving.value = false;
    }, 500);
};

const save = () => {
    // Extract line items from the LineItemsBlock
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    form.line_items = itemsBlock?.content?.items || [];
    form.layout_configuration = store.blocks;

    if (props.type === 'quote') {
        form.post(route('quotes.store', { prospect: form.client_id }));
    } else {
        form.post(route('invoices.store', { client: form.client_id }));
    }
};

// Initialize totals on mount and ensure existing items have vat_rate and columns
onMounted(() => {
    const itemsBlock = store.blocks.find(b => b.type === 'LineItemsBlock');
    if (itemsBlock) {
        // Add default columns if not present
        if (!itemsBlock.content.columns) {
            itemsBlock.content.columns = getDefaultColumns();
        }

        // Add default vat_rate to existing items that don't have one
        if (itemsBlock.content.items) {
            itemsBlock.content.items.forEach(item => {
                if (item.vat_rate === undefined) {
                    item.vat_rate = 20; // Default 20%
                }
            });
        }
    }
    // Calculate initial totals
    updateTotals();
});
</script>

<template>
    <Head :title="type === 'quote' ? 'Éditeur de Devis' : 'Éditeur de Facture'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                        {{ type === 'quote' ? 'Nouveau Devis' : 'Nouvelle Facture' }}
                    </h2>
                    <AutoSaveIndicator :isSaving="isSaving" :lastSaved="lastSaved" />
                </div>
                <div class="flex gap-2">
                    <button
                        @click="showTemplateManager = true"
                        class="btn btn-ghost btn-sm flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Mes Templates
                    </button>
                    <button
                        @click="showSaveTemplateModal = true"
                        class="btn btn-secondary btn-sm flex items-center gap-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Sauvegarder template
                    </button>
                    <button
                        @click="save"
                        class="btn btn-primary btn-sm"
                        :disabled="form.processing || !form.client_id"
                    >
                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                        Enregistrer
                    </button>
                    <Link :href="route(type === 'quote' ? 'quotes.index' : 'invoices.index')" class="btn btn-secondary btn-sm">Annuler</Link>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-120px)] overflow-hidden bg-apple-gray dark:bg-apple-dark-bg">
            <!-- Left Sidebar: Palette -->
            <aside class="w-64 border-r border-white/30 dark:border-white/10 bg-white/70 dark:bg-apple-dark-card/70 backdrop-blur-2xl p-4 shadow-sm overflow-y-auto">
                <div class="mb-8 border-b pb-6">
                    <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-apple-dark-secondary">
                        {{ type === 'quote' ? 'Prospect' : 'Client' }}
                    </h3>
                    <select v-model="form.client_id" class="glass-select text-sm">
                        <option :value="null" disabled>
                            {{ type === 'quote' ? 'Sélectionner un prospect' : 'Sélectionner un client' }}
                        </option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company || c.name }}</option>
                    </select>
                    <div v-if="form.errors.client_id" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.client_id }}</div>
                </div>

                <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-apple-dark-secondary">Ajouter un bloc</h3>
                <div class="grid grid-cols-1 gap-2">
                    <button
                        v-for="block in availableBlocks"
                        :key="block.type"
                        @click="addBlock(block.type)"
                        class="flex items-center gap-3 rounded-2xl border border-white/30 dark:border-white/10 p-3 bg-white/30 dark:bg-white/5 hover:bg-white/50 dark:hover:bg-white/10 hover:border-indigo-500/30 transition-all text-left group"
                    >
                        <span class="text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" v-html="block.icon"></span>
                        <span class="text-sm font-medium text-gray-700 dark:text-white">{{ block.label }}</span>
                    </button>
                </div>

                <div class="mt-8 border-t border-gray-200 dark:border-apple-dark-border pt-8">
                    <h3 class="mb-4 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-apple-dark-secondary">Style Global du Document</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="glass-label text-xs mb-2">Couleur Principale</label>
                            <div class="flex items-center gap-3">
                                <input type="color" v-model="store.globalSettings.primaryColor" class="h-10 w-20 rounded-xl cursor-pointer border-0 shadow-sm" />
                                <input type="text" v-model="store.globalSettings.primaryColor" class="glass-input flex-1 text-xs font-mono py-2 px-3" placeholder="#6366F1" />
                            </div>
                        </div>
                        <div>
                            <label class="glass-label text-xs mb-2">Couleur de Fond du Document</label>
                            <div class="flex items-center gap-3">
                                <input type="color" v-model="store.globalSettings.backgroundColor" class="h-10 w-20 rounded-xl cursor-pointer border-0 shadow-sm" />
                                <input type="text" v-model="store.globalSettings.backgroundColor" class="glass-input flex-1 text-xs font-mono py-2 px-3" placeholder="#FFFFFF" />
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-apple-dark-secondary">Le document ne changera pas avec le thème dark/light</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Canvas -->
            <main class="flex-1 p-8 overflow-y-auto custom-scrollbar">
                <div class="mx-auto max-w-[210mm] min-h-[297mm] shadow-2xl p-16 rounded-sm" :style="{ backgroundColor: store.globalSettings.backgroundColor, colorScheme: 'light' }">
                    <draggable
                        v-model="store.blocks"
                        item-key="id"
                        handle=".drag-handle"
                        class="flex flex-wrap gap-6 min-h-[200px]"
                        ghost-class="opacity-50"
                    >
                        <template #item="{ element }">
                            <BuilderBlock
                                :id="element.id"
                                :isSelected="selectedBlockId === element.id"
                                @select="selectedBlockId = $event"
                                @remove="store.removeBlock($event)"
                                :style="{
                                    backgroundColor: element.style?.backgroundColor || '#FFFFFF',
                                    width: element.layout?.width ? `calc(${element.layout.width}% - 1.5rem)` : '100%',
                                    flexShrink: 0
                                }"
                            >
                                <component
                                    :is="components[element.type]"
                                    :content="element.content"
                                    :primaryColor="store.globalSettings.primaryColor"
                                    :textColor="element.style?.textColor || store.globalSettings.textColor"
                                    :editable="element.type === 'LineItemsBlock' || element.type === 'TextBlock'"
                                    :onAdd="element.type === 'LineItemsBlock' ? () => addLineItem() : undefined"
                                    :onRemove="element.type === 'LineItemsBlock' ? (index) => { element.content.items.splice(index, 1); updateTotals(); } : undefined"
                                    :onUpdate="element.type === 'LineItemsBlock' ? (index, field, value) => { element.content.items[index][field] = value; updateTotals(); } : element.type === 'TextBlock' ? (key, value) => { element.content[key] = value; } : undefined"
                                />
                            </BuilderBlock>
                        </template>
                    </draggable>
                </div>
            </main>

            <!-- Right Sidebar: Properties -->
            <aside class="w-[420px] border-l border-white/30 dark:border-white/10 bg-white/70 dark:bg-apple-dark-card/70 backdrop-blur-2xl p-6 shadow-sm overflow-y-auto">
                <h3 class="mb-6 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-apple-dark-secondary">Paramétrage du bloc</h3>
                
                <div v-if="selectedBlock" class="space-y-6">
                    <div class="rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 p-3 text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                        {{ selectedBlock.type }}
                    </div>

                    <!-- Generic Content Editor (placeholder for real inputs) -->
                    <div v-if="selectedBlock.type === 'AddressBlock'" class="space-y-5">
                        <div>
                            <label class="glass-label">Libellé</label>
                            <input v-model="selectedBlock.content.label" type="text" class="glass-input text-sm py-2.5 px-4" placeholder="Ex: De la part de..." />
                        </div>
                        <div>
                            <label class="glass-label">Adresse / Texte</label>
                            <textarea v-model="selectedBlock.content.address" rows="5" class="glass-textarea text-sm py-3 px-4" placeholder="Saisissez l'adresse ici..."></textarea>
                        </div>
                    </div>

                    <div v-if="selectedBlock.type === 'LogoBlock'" class="space-y-5">
                        <!-- Logo Preview -->
                        <div>
                            <label class="glass-label mb-2">Aperçu du logo</label>
                            <div class="h-24 w-full rounded-lg border-2 border-gray-200 dark:border-apple-dark-border bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden p-2">
                                <img 
                                    v-if="selectedBlock.content.url" 
                                    :src="selectedBlock.content.url" 
                                    alt="Logo preview" 
                                    class="max-h-full max-w-full object-contain"
                                />
                                <span v-else class="text-gray-400 dark:text-apple-dark-secondary text-sm">Aucun logo</span>
                            </div>
                        </div>

                        <!-- Logo Upload -->
                        <div>
                            <label class="glass-label mb-2">Logo personnalisé</label>
                            <div class="space-y-2">
                                <input
                                    type="file"
                                    id="logo-upload-builder"
                                    accept="image/*"
                                    @change="handleLogoFileChange"
                                    class="hidden"
                                />
                                <label 
                                    for="logo-upload-builder" 
                                    class="btn btn-secondary btn-sm w-full cursor-pointer flex items-center justify-center gap-2"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Télécharger un logo
                                </label>
                                <button
                                    v-if="selectedBlock.content.url && selectedBlock.content.url !== (props.companyProfile?.logo_path ? `/storage/${props.companyProfile.logo_path}` : null)"
                                    type="button"
                                    @click="resetLogoToDefault"
                                    class="btn btn-ghost btn-sm w-full text-xs"
                                >
                                    Utiliser le logo par défaut
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-apple-dark-secondary">
                                Par défaut : logo de votre profil entreprise
                            </p>
                        </div>

                        <div>
                            <label class="glass-label">Position</label>
                            <select v-model="selectedBlock.content.position" class="glass-select text-sm py-2.5 px-4">
                                <option value="left">Gauche</option>
                                <option value="center">Centre</option>
                                <option value="right">Droite</option>
                            </select>
                        </div>
                        <div>
                            <label class="glass-label mb-3 block">Taille ({{ selectedBlock.content.size || 100 }}px)</label>
                            <input type="range" v-model.number="selectedBlock.content.size" min="50" max="300" class="w-full h-2 rounded-full appearance-none cursor-pointer bg-gray-200 dark:bg-gray-700
                                [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:rounded-full
                                [&::-webkit-slider-thumb]:bg-indigo-600 [&::-webkit-slider-thumb]:shadow-lg [&::-webkit-slider-thumb]:cursor-pointer
                                [&::-moz-range-thumb]:w-5 [&::-moz-range-thumb]:h-5 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-indigo-600
                                [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:shadow-lg [&::-moz-range-thumb]:cursor-pointer" />
                        </div>
                    </div>

                    <!-- Block Customization (Works for all blocks) -->
                    <div v-if="selectedBlock" class="mt-6 pt-6 border-t border-gray-200 dark:border-apple-dark-border space-y-5">
                        <h4 class="text-sm font-bold text-gray-700 dark:text-white">Personnalisation du Bloc</h4>

                        <div>
                            <label class="glass-label text-xs mb-2">Couleur de Fond du Bloc</label>
                            <div class="flex items-center gap-3">
                                <input type="color" v-model="selectedBlock.style.backgroundColor" @input="store.updateBlockStyle(selectedBlock.id, { backgroundColor: $event.target.value })" class="h-10 w-20 rounded-xl cursor-pointer border-0 shadow-sm" />
                                <input type="text" v-model="selectedBlock.style.backgroundColor" @input="store.updateBlockStyle(selectedBlock.id, { backgroundColor: $event.target.value })" class="glass-input flex-1 text-xs font-mono py-2 px-3" placeholder="#FFFFFF" />
                            </div>
                        </div>

                        <div>
                            <label class="glass-label text-xs mb-2">Couleur du Texte du Bloc</label>
                            <div class="flex items-center gap-3">
                                <input type="color" v-model="selectedBlock.style.textColor" @input="store.updateBlockStyle(selectedBlock.id, { textColor: $event.target.value })" class="h-10 w-20 rounded-xl cursor-pointer border-0 shadow-sm" />
                                <input type="text" v-model="selectedBlock.style.textColor" @input="store.updateBlockStyle(selectedBlock.id, { textColor: $event.target.value })" class="glass-input flex-1 text-xs font-mono py-2 px-3" placeholder="#1F2937" />
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-apple-dark-secondary">Affecte tous les textes de ce bloc</p>
                        </div>

                        <!-- Layout Controls -->
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-apple-dark-border">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-white mb-4">Mise en page</h4>

                            <div>
                                <label class="glass-label text-xs mb-2">Largeur du bloc ({{ selectedBlock.layout?.width || 100 }}%)</label>
                                <div class="space-y-3">
                                    <input
                                        type="range"
                                        :value="selectedBlock.layout?.width || 100"
                                        @input="store.updateBlockLayout(selectedBlock.id, { width: parseInt($event.target.value) })"
                                        min="25"
                                        max="100"
                                        step="25"
                                        class="w-full h-2 rounded-full appearance-none cursor-pointer bg-gray-200 dark:bg-gray-700
                                            [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:rounded-full
                                            [&::-webkit-slider-thumb]:bg-indigo-600 [&::-webkit-slider-thumb]:shadow-lg [&::-webkit-slider-thumb]:cursor-pointer
                                            [&::-moz-range-thumb]:w-5 [&::-moz-range-thumb]:h-5 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-indigo-600
                                            [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:shadow-lg [&::-moz-range-thumb]:cursor-pointer"
                                    />
                                    <div class="flex gap-2">
                                        <button
                                            v-for="width in [25, 33, 50, 66, 75, 100]"
                                            :key="width"
                                            @click="store.updateBlockLayout(selectedBlock.id, { width })"
                                            :class="(selectedBlock.layout?.width || 100) === width ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                            class="flex-1 px-2 py-1 text-xs font-medium rounded hover:bg-indigo-600 hover:text-white transition-colors"
                                        >
                                            {{ width }}%
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-apple-dark-secondary">
                                    Utilisez une largeur inférieure à 100% pour placer plusieurs blocs côte à côte
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedBlock.type === 'LineItemsBlock'" class="space-y-4">
                        <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800">
                            <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Modifiez les articles directement dans le tableau à gauche
                            </p>
                        </div>

                        <div class="border-t border-[hsl(var(--border))] pt-4">
                            <ColumnEditor
                                :columns="selectedBlock.content.columns || []"
                                @update:columns="updateColumns"
                            />
                        </div>

                        <div class="border-t border-[hsl(var(--border))] pt-4 text-sm text-gray-600 dark:text-gray-400">
                            <p>💡 <strong>Astuce :</strong> Sélectionnez le texte dans la description pour le formater (gras, italique, couleur, etc.)</p>
                        </div>
                    </div>

                    <div v-if="selectedBlock.type === 'TextBlock'" class="space-y-4">
                        <TextStyleEditor v-model="selectedBlock.content" />
                    </div>

                    <div v-if="selectedBlock.type === 'SpacerBlock'" class="space-y-4">
                        <div>
                            <label class="glass-label mb-3 block">Hauteur ({{ selectedBlock.content.height || 50 }}px)</label>
                            <input
                                type="range"
                                v-model.number="selectedBlock.content.height"
                                min="10"
                                max="500"
                                step="10"
                                class="w-full h-2 rounded-full appearance-none cursor-pointer bg-gray-200 dark:bg-gray-700
                                    [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:rounded-full
                                    [&::-webkit-slider-thumb]:bg-indigo-600 [&::-webkit-slider-thumb]:shadow-lg [&::-webkit-slider-thumb]:cursor-pointer
                                    [&::-moz-range-thumb]:w-5 [&::-moz-range-thumb]:h-5 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-indigo-600
                                    [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:shadow-lg [&::-moz-range-thumb]:cursor-pointer"
                            />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-apple-dark-secondary">
                            Utilisez la largeur du bloc (ci-dessous) pour contrôler la largeur de l'espaceur
                        </p>
                    </div>
                </div>

                <div v-else class="flex h-full flex-col items-center justify-center text-center text-gray-400 dark:text-apple-dark-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                    </svg>
                    <p class="text-sm">Sélectionnez un bloc pour<br/>voir ses réglages.</p>
                </div>
            </aside>
        </div>

        <!-- Template Modals -->
        <SaveTemplateModal
            :show="showSaveTemplateModal"
            :document-type="type"
            @close="showSaveTemplateModal = false"
            @save="saveTemplate"
        />

        <TemplateManager
            :show="showTemplateManager"
            :document-type="type"
            @close="showTemplateManager = false"
            @load="loadTemplate"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #4B5563;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #6B7280;
}
</style>
