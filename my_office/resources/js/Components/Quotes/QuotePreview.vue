<script setup>
import { useQuoteStore } from '@/Stores/quoteStore';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';

const props = defineProps({
    company: {
        type: Object,
        required: true,
    },
    client: {
        type: Object,
        required: false,
        default: null,
    },
    brandingOverride: {
        type: Object,
        required: false,
        default: null,
    },
});

const store = useQuoteStore();
const { line_items, expires_at } = storeToRefs(store);

const branding = computed(() => props.brandingOverride || props.company);

const subtotal = computed(() => store.calculateSubtotal);
const vatAmount = computed(() => store.calculateVatAmount);
const total = computed(() => store.calculateGrandTotal);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR');
};

const logoUrl = computed(() => {
    return branding.value.logo_path
        ? `/storage/${branding.value.logo_path}`
        : null;
});

const logoStyles = computed(() => ({
    width: `${branding.value.logo_size || 100}px`,
    maxWidth: '100%',
}));

const headerStyles = computed(() => ({
    textAlign: branding.value.logo_position || 'left',
}));

const previewPdf = () => {
    if (!props.client) return;

    // We use a form submission to a new tab because Axios doesn't handle PDF streams easily for display
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('quotes.preview');
    form.target = '_blank';

    // CSRF Token
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrfToken;
        form.appendChild(input);
    }

    // Data
    const data = {
        client_id: props.client.id,
        template_id: store.template_id,
        expires_at: store.expires_at,
        line_items: store.line_items,
    };

    // Helper to append nested data
    const appendData = (obj, prefix = '') => {
        for (const key in obj) {
            const value = obj[key];
            const name = prefix ? `${prefix}[${key}]` : key;
            if (typeof value === 'object' && value !== null) {
                appendData(value, name);
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }
        }
    };

    appendData(data);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-lg border bg-white shadow-lg"
    >
        <!-- Preview Header -->
        <div
            class="flex items-center justify-between border-b bg-gray-100 px-4 py-2"
        >
            <span
                class="text-xs font-bold uppercase tracking-wider text-gray-500"
                >Prévisualisation en direct</span
            >
            <div class="flex gap-2">
                <button
                    @click="previewPdf"
                    class="btn btn-primary btn-outline btn-xs"
                    :disabled="!client"
                >
                    Aperçu PDF
                </button>
                <span
                    class="badge badge-sm h-6"
                    :style="{
                        backgroundColor: branding.primary_color,
                        color: 'white',
                    }"
                >
                    {{ branding.name || 'Défaut' }}
                </span>
            </div>
        </div>

        <!-- Document Content -->
        <div
            class="flex-grow overflow-y-auto bg-white p-8"
            :style="{ fontFamily: branding.font_family || 'sans-serif' }"
        >
            <!-- Header: Logo -->
            <div class="mb-8" :style="headerStyles">
                <img
                    v-if="logoUrl"
                    :src="logoUrl"
                    :style="logoStyles"
                    class="inline-block"
                    alt="Logo"
                />
                <div
                    v-else
                    class="inline-block flex h-16 w-32 items-center justify-center border-2 border-dashed border-gray-300 bg-gray-100 text-xs font-bold uppercase text-gray-400"
                >
                    Aperçu Logo
                </div>
            </div>

            <!-- Company Info and Title -->
            <div class="mb-8 flex items-start justify-between">
                <div>
                    <h1
                        class="text-xl font-bold"
                        :style="{ color: branding.primary_color }"
                    >
                        {{ company.name }}
                    </h1>
                    <p class="whitespace-pre-line text-xs text-gray-600">
                        {{ company.address }}
                    </p>
                    <p v-if="company.email" class="text-xs text-gray-600">
                        Email: {{ company.email }}
                    </p>
                    <p v-if="company.phone" class="text-xs text-gray-600">
                        Tél: {{ company.phone }}
                    </p>
                    <p class="text-xs text-gray-600">
                        SIRET: {{ company.siret }}
                    </p>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold uppercase text-gray-400">
                        Devis
                    </h2>
                    <p class="text-sm font-bold">N° DEVIS-2026-XXXX</p>
                    <p class="text-xs text-gray-500">
                        Date: {{ formatDate(new Date()) }}
                    </p>
                    <p v-if="expires_at" class="text-xs text-gray-500">
                        Valide jusqu'au: {{ formatDate(expires_at) }}
                    </p>
                </div>
            </div>

            <!-- Client Info -->
            <div class="mb-8 flex justify-end">
                <div
                    v-if="client"
                    class="w-1/2 rounded border p-4"
                    :style="{
                        borderColor: branding.primary_color + '40',
                    }"
                >
                    <p class="mb-1 text-xs font-bold uppercase text-gray-400">
                        Destinataire
                    </p>
                    <p class="font-bold">{{ client.company || client.name }}</p>
                    <p v-if="client.company" class="text-sm">
                        {{ client.name }}
                    </p>
                    <p class="whitespace-pre-line text-sm">
                        {{ client.address }}
                    </p>
                </div>
                <div
                    v-else
                    class="flex w-1/2 items-center justify-center rounded border border-dashed bg-gray-50 p-4 text-sm italic text-gray-400"
                >
                    Aucun client sélectionné
                </div>
            </div>

            <!-- Table -->
            <table class="mb-8 w-full text-sm">
                <thead>
                    <tr
                        class="text-white"
                        :style="{
                            backgroundColor:
                                branding.secondary_color ||
                                branding.primary_color,
                        }"
                    >
                        <th class="px-3 py-2 text-left">Description</th>
                        <th class="px-3 py-2 text-center">Qté</th>
                        <th class="px-3 py-2 text-right">P.U. HT</th>
                        <th class="px-3 py-2 text-right">TVA</th>
                        <th class="px-3 py-2 text-right">Total HT</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="(item, index) in line_items" :key="index">
                        <td class="px-3 py-3">
                            <div>{{ item.description || 'Description de l\'article' }}</div>
                        </td>
                        <td class="px-3 py-3 text-center">
                            {{ item.quantity }}
                        </td>
                        <td class="px-3 py-3 text-right">
                            {{ item.unit_price.toFixed(2) }} €
                        </td>
                        <td class="px-3 py-3 text-right">
                            {{ item.vat_rate }}%
                        </td>
                        <td class="px-3 py-3 text-right">
                            {{ (item.quantity * item.unit_price).toFixed(2) }} €
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-1/3 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span>Sous-total HT</span>
                        <span>{{ subtotal.toFixed(2) }} €</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>TVA</span>
                        <span>{{ vatAmount.toFixed(2) }} €</span>
                    </div>
                    <div
                        class="flex justify-between border-t pt-2 text-lg font-bold"
                        :style="{ color: branding.primary_color }"
                    >
                        <span>Total TTC</span>
                        <span>{{ total.toFixed(2) }} €</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="mt-12 border-t pt-8 text-center text-[10px] italic text-gray-400"
            >
                <p v-if="company.is_vat_exempt">
                    TVA non applicable, art. 293 B du CGI
                </p>
                <p>
                    Micro-entrepreneur selon l'article L. 123-1-1 du code de
                    commerce
                </p>
            </div>
        </div>
    </div>
</template>
