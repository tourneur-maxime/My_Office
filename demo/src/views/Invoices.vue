<script setup lang="ts">
import { ref } from 'vue';
import { fakeInvoices } from '../data/fake';

const statusLabel: Record<string, { label: string; cls: string }> = {
    paid:    { label: 'Payée',   cls: 'bg-green-500/15 text-green-400 border-green-500/20' },
    sent:    { label: 'Envoyée', cls: 'bg-blue-500/15 text-blue-400 border-blue-500/20' },
    overdue: { label: 'En retard', cls: 'bg-red-500/15 text-red-400 border-red-500/20' },
    draft:   { label: 'Brouillon', cls: 'bg-gray-500/15 text-gray-400 border-gray-500/20' },
};

const search = ref('');
const filtered = () => fakeInvoices.filter(inv =>
    inv.number.toLowerCase().includes(search.value.toLowerCase()) ||
    inv.client.toLowerCase().includes(search.value.toLowerCase())
);
</script>

<template>
    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-[hsl(var(--text-main))]">Factures</h1>
                <p class="text-sm text-[hsl(var(--text-muted))] mt-0.5">{{ fakeInvoices.length }} factures au total</p>
            </div>
            <button class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[hsl(var(--primary))] text-white text-sm font-medium opacity-60 cursor-not-allowed" disabled>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle facture
            </button>
        </div>

        <!-- Search -->
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[hsl(var(--text-muted))]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                v-model="search"
                type="text"
                placeholder="Rechercher une facture..."
                class="w-full max-w-sm pl-9 pr-4 py-2 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))] text-sm text-[hsl(var(--text-main))] placeholder:text-[hsl(var(--text-muted))] focus:outline-none focus:ring-2 focus:ring-[hsl(var(--primary))]/30"
            />
        </div>

        <!-- Table -->
        <div class="liquid-glass overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[hsl(var(--border))]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Numéro</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Échéance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[hsl(var(--border))]">
                    <tr
                        v-for="inv in filtered()"
                        :key="inv.id"
                        class="hover:bg-[hsl(var(--bg-elevated))]/50 transition-colors"
                    >
                        <td class="px-4 py-3 font-medium text-[hsl(var(--text-main))]">{{ inv.number }}</td>
                        <td class="px-4 py-3 text-[hsl(var(--text-muted))]">{{ inv.client }}</td>
                        <td class="px-4 py-3 font-semibold text-[hsl(var(--text-main))]">{{ inv.amount.toLocaleString('fr-FR') }} €</td>
                        <td class="px-4 py-3 text-[hsl(var(--text-muted))]">{{ inv.date }}</td>
                        <td class="px-4 py-3 text-[hsl(var(--text-muted))]">{{ inv.due_date }}</td>
                        <td class="px-4 py-3">
                            <span :class="['text-xs px-2 py-0.5 rounded-full border', statusLabel[inv.status].cls]">
                                {{ statusLabel[inv.status].label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
