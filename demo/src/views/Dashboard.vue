<script setup lang="ts">
import { fakeStats, fakeInvoices, fakeQuotes } from '../data/fake';

const kpis = [
    { label: 'Clients', value: fakeStats.clients, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', color: 'from-blue-500/20 to-cyan-500/20 text-blue-400' },
    { label: 'Prospects', value: fakeStats.prospects, icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', color: 'from-purple-500/20 to-pink-500/20 text-purple-400' },
    { label: 'Devis en attente', value: fakeStats.quotes_pending, icon: 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0120 8.414V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2', color: 'from-amber-500/20 to-orange-500/20 text-amber-400' },
    { label: 'Factures impayées', value: fakeStats.invoices_unpaid, icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', color: 'from-red-500/20 to-rose-500/20 text-red-400' },
    { label: 'CA ce mois', value: `${fakeStats.revenue_month.toLocaleString('fr-FR')} €`, icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color: 'from-green-500/20 to-emerald-500/20 text-green-400' },
    { label: 'CA cette année', value: `${fakeStats.revenue_year.toLocaleString('fr-FR')} €`, icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', color: 'from-teal-500/20 to-cyan-500/20 text-teal-400' },
];

const statusLabel: Record<string, { label: string; cls: string }> = {
    paid:    { label: 'Payée',   cls: 'bg-green-500/15 text-green-400 border-green-500/20' },
    sent:    { label: 'Envoyée', cls: 'bg-blue-500/15 text-blue-400 border-blue-500/20' },
    overdue: { label: 'En retard', cls: 'bg-red-500/15 text-red-400 border-red-500/20' },
    draft:   { label: 'Brouillon', cls: 'bg-gray-500/15 text-gray-400 border-gray-500/20' },
    accepted: { label: 'Accepté', cls: 'bg-green-500/15 text-green-400 border-green-500/20' },
};

const recentInvoices = fakeInvoices.slice(0, 4);
const recentQuotes = fakeQuotes.slice(0, 3);
</script>

<template>
    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-xl font-bold text-[hsl(var(--text-main))]">Tableau de bord</h1>
            <p class="text-sm text-[hsl(var(--text-muted))] mt-0.5">Bonjour ! Voici un résumé de votre activité.</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="kpi in kpis"
                :key="kpi.label"
                class="liquid-glass p-4 flex items-center gap-4"
            >
                <div :class="['w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center shrink-0', kpi.color]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="kpi.icon" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-[hsl(var(--text-main))]">{{ kpi.value }}</p>
                    <p class="text-xs text-[hsl(var(--text-muted))]">{{ kpi.label }}</p>
                </div>
            </div>
        </div>

        <!-- Tables row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Recent invoices -->
            <div class="liquid-glass overflow-hidden">
                <div class="px-4 py-3 border-b border-[hsl(var(--border))]">
                    <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Dernières factures</h2>
                </div>
                <div class="divide-y divide-[hsl(var(--border))]">
                    <div v-for="inv in recentInvoices" :key="inv.id" class="px-4 py-3 flex items-center justify-between hover:bg-[hsl(var(--bg-elevated))]/50 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-[hsl(var(--text-main))]">{{ inv.number }}</p>
                            <p class="text-xs text-[hsl(var(--text-muted))]">{{ inv.client }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-[hsl(var(--text-main))]">{{ inv.amount.toLocaleString('fr-FR') }} €</p>
                            <span :class="['text-xs px-2 py-0.5 rounded-full border', statusLabel[inv.status].cls]">
                                {{ statusLabel[inv.status].label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent quotes -->
            <div class="liquid-glass overflow-hidden">
                <div class="px-4 py-3 border-b border-[hsl(var(--border))]">
                    <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Derniers devis</h2>
                </div>
                <div class="divide-y divide-[hsl(var(--border))]">
                    <div v-for="q in recentQuotes" :key="q.id" class="px-4 py-3 flex items-center justify-between hover:bg-[hsl(var(--bg-elevated))]/50 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-[hsl(var(--text-main))]">{{ q.number }}</p>
                            <p class="text-xs text-[hsl(var(--text-muted))]">{{ q.client }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-[hsl(var(--text-main))]">{{ q.amount.toLocaleString('fr-FR') }} €</p>
                            <span :class="['text-xs px-2 py-0.5 rounded-full border', statusLabel[q.status].cls]">
                                {{ statusLabel[q.status].label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
