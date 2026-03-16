<script setup>
const props = defineProps({
    content: Object,
    primaryColor: String,
    textColor: {
        type: String,
        default: '#1F2937'
    }
});

// Check if there are multiple VAT rates
const hasMultipleVatRates = () => {
    const details = props.content?.vat_details || {};
    return Object.keys(details).length > 1;
};
</script>

<template>
    <div class="flex justify-end pt-4">
        <div class="w-80 space-y-2 text-sm">
            <!-- Subtotal HT -->
            <div class="flex justify-between">
                <span :style="{ color: textColor, opacity: 0.7 }">Sous-total HT</span>
                <span class="font-medium" :style="{ color: textColor }">{{ (content.subtotal || 0).toFixed(2) }} €</span>
            </div>

            <!-- VAT Details (if multiple rates) -->
            <template v-if="hasMultipleVatRates()">
                <div v-for="(amount, rate) in content.vat_details" :key="rate" class="flex justify-between pl-4">
                    <span :style="{ color: textColor, opacity: 0.6 }">TVA {{ parseFloat(rate).toFixed(1) }}%</span>
                    <span class="font-medium" :style="{ color: textColor, opacity: 0.8 }">{{ amount.toFixed(2) }} €</span>
                </div>
            </template>

            <!-- Total VAT -->
            <div class="flex justify-between pb-2" :style="{ borderBottom: '1px solid ' + textColor + '30' }">
                <span :style="{ color: textColor, opacity: 0.7 }" class="font-medium">{{ hasMultipleVatRates() ? 'Total TVA' : 'TVA' }}</span>
                <span class="font-medium" :style="{ color: textColor }">{{ (content.vat_amount || 0).toFixed(2) }} €</span>
            </div>

            <!-- Total TTC -->
            <div class="flex justify-between text-lg font-bold pt-2" :style="{ color: primaryColor }">
                <span>TOTAL TTC</span>
                <span>{{ (content.total || 0).toFixed(2) }} €</span>
            </div>
        </div>
    </div>
</template>
