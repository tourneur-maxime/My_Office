<script setup lang="ts">
import { fakeQuotes } from '../data/fake';

const statusLabel: Record<string, { label: string; cls: string }> = {
    draft:    { label: 'Brouillon', cls: 'bg-gray-500/15 text-gray-400 border-gray-500/20' },
    sent:     { label: 'Envoyé',   cls: 'bg-blue-500/15 text-blue-400 border-blue-500/20' },
    accepted: { label: 'Accepté',  cls: 'bg-green-500/15 text-green-400 border-green-500/20' },
    refused:  { label: 'Refusé',   cls: 'bg-red-500/15 text-red-400 border-red-500/20' },
};
</script>

<template>
    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-[hsl(var(--text-main))]">Devis</h1>
                <p class="text-sm text-[hsl(var(--text-muted))] mt-0.5">{{ fakeQuotes.length }} devis actifs</p>
            </div>
            <button class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[hsl(var(--primary))] text-white text-sm font-medium opacity-60 cursor-not-allowed" disabled>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau devis
            </button>
        </div>

        <div class="liquid-glass overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[hsl(var(--border))]">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Numéro</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-[hsl(var(--text-muted))] uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[hsl(var(--border))]">
                    <tr v-for="q in fakeQuotes" :key="q.id" class="hover:bg-[hsl(var(--bg-elevated))]/50 transition-colors">
                        <td class="px-4 py-3 font-medium text-[hsl(var(--text-main))]">{{ q.number }}</td>
                        <td class="px-4 py-3 text-[hsl(var(--text-muted))]">{{ q.client }}</td>
                        <td class="px-4 py-3 font-semibold text-[hsl(var(--text-main))]">{{ q.amount.toLocaleString('fr-FR') }} €</td>
                        <td class="px-4 py-3 text-[hsl(var(--text-muted))]">{{ q.date }}</td>
                        <td class="px-4 py-3">
                            <span :class="['text-xs px-2 py-0.5 rounded-full border', statusLabel[q.status].cls]">
                                {{ statusLabel[q.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button class="text-xs text-[hsl(var(--primary))] hover:underline opacity-60 cursor-not-allowed" disabled>
                                Convertir en facture
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
