<script setup>
import { computed } from 'vue';

const props = defineProps({
    logo: String,
    logoSize: Number,
    logoPosition: String,
    primaryColor: String,
    secondaryColor: String,
    fontFamily: String,
    companyName: String,
    companyAddress: String,
    companyEmail: String,
    companyPhone: String,
});

const logoStyles = computed(() => ({
    width: `${props.logoSize}px`,
    maxWidth: '100%',
}));

const containerStyles = computed(() => ({
    textAlign: props.logoPosition,
}));

const accentStyles = computed(() => ({
    color: props.primaryColor,
}));

const fontStyles = computed(() => ({
    fontFamily: props.fontFamily || 'sans-serif',
}));
</script>

<template>
    <div
        class="mx-auto min-h-[400px] max-w-2xl border border-gray-300 bg-white p-8 shadow-lg"
        :style="fontStyles"
    >
        <!-- Header with Logo -->
        <div class="mb-8" :style="containerStyles">
            <img
                v-if="logo"
                :src="logo"
                alt="Logo"
                :style="logoStyles"
                class="inline-block"
            />
            <div
                v-else
                class="inline-block flex h-16 w-32 items-center justify-center border-2 border-dashed border-gray-300 bg-gray-100 text-xs font-bold uppercase text-gray-400"
            >
                Aperçu Logo
            </div>
        </div>

        <!-- Document Content Simulation -->
        <div class="mb-8 flex justify-between">
            <div>
                <h3
                    class="mb-1 text-sm font-bold uppercase"
                    :style="accentStyles"
                >
                    {{ companyName || 'Ma Super Entreprise' }}
                </h3>
                <p class="whitespace-pre-line text-xs text-gray-600">
                    {{ companyAddress || '123 Rue du Test\n75000 Paris' }}
                </p>
                <p v-if="companyEmail" class="mt-1 text-[10px] text-gray-500">
                    Email: {{ companyEmail }}
                </p>
                <p v-if="companyPhone" class="text-[10px] text-gray-500">
                    Tél: {{ companyPhone }}
                </p>
            </div>
            <div class="text-right">
                <h2 class="mb-2 text-2xl font-bold" :style="accentStyles">
                    FACTURE
                </h2>
                <p class="text-xs font-semibold">FAC-2026-0001</p>
                <p class="text-xs text-gray-500">Date: 20/01/2026</p>
            </div>
        </div>

        <!-- Horizontal Line -->
        <div
            class="mb-6 h-1 w-full"
            :style="{ backgroundColor: primaryColor || '#3B82F6' }"
        ></div>

        <div class="mb-8">
            <p class="mb-2 text-xs font-bold">Facturé à :</p>
            <p class="text-sm">Client Exemple</p>
            <p class="text-xs text-gray-600">456 Avenue de l'Avenir, Lyon</p>
        </div>

        <!-- Table simulation -->
        <table class="mb-8 w-full text-xs">
            <thead>
                <tr
                    class="border-b-2"
                    :style="{ borderBottomColor: secondaryColor || '#10B981' }"
                >
                    <th class="py-2 text-left">Description</th>
                    <th class="py-2 text-right">Qté</th>
                    <th class="py-2 text-right">Prix Unitaire</th>
                    <th class="py-2 text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b">
                    <td class="py-2">Service de Consulting Stratégique</td>
                    <td class="py-2 text-right">1</td>
                    <td class="py-2 text-right">1 200,00 €</td>
                    <td class="py-2 text-right">1 200,00 €</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals Simulation -->
        <div class="flex justify-end">
            <div class="w-48 text-xs">
                <div class="flex justify-between py-1">
                    <span>Sous-total HT</span>
                    <span>1 200,00 €</span>
                </div>
                <div
                    class="mt-2 flex justify-between border-t py-1 pt-2 text-sm font-bold"
                    :style="accentStyles"
                >
                    <span>Total TTC</span>
                    <span>1 440,00 €</span>
                </div>
            </div>
        </div>
    </div>
</template>
