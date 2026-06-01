<script setup lang="ts">
import { ref } from 'vue';
import { fakeClients } from '../data/fake';

const search = ref('');
const filtered = () => fakeClients.filter(c =>
    c.name.toLowerCase().includes(search.value.toLowerCase()) ||
    c.city.toLowerCase().includes(search.value.toLowerCase())
);
</script>

<template>
    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-[hsl(var(--text-main))]">Clients</h1>
                <p class="text-sm text-[hsl(var(--text-muted))] mt-0.5">{{ fakeClients.length }} clients actifs</p>
            </div>
            <button class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[hsl(var(--primary))] text-white text-sm font-medium opacity-60 cursor-not-allowed" disabled>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau client
            </button>
        </div>

        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[hsl(var(--text-muted))]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                v-model="search"
                type="text"
                placeholder="Rechercher un client..."
                class="w-full max-w-sm pl-9 pr-4 py-2 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))] text-sm text-[hsl(var(--text-main))] placeholder:text-[hsl(var(--text-muted))] focus:outline-none focus:ring-2 focus:ring-[hsl(var(--primary))]/30"
            />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="client in filtered()"
                :key="client.id"
                class="liquid-glass p-5 hover:border-[hsl(var(--primary))]/30 transition-all"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[hsl(var(--primary))]/10 flex items-center justify-center text-sm font-bold text-[hsl(var(--primary))]">
                        {{ client.name.split(' ').map((w: string) => w[0]).join('').slice(0, 2) }}
                    </div>
                    <span class="text-xs text-[hsl(var(--text-muted))] bg-[hsl(var(--bg-elevated))] px-2 py-0.5 rounded-full">{{ client.city }}</span>
                </div>
                <h3 class="font-semibold text-[hsl(var(--text-main))] mb-1">{{ client.name }}</h3>
                <p class="text-xs text-[hsl(var(--text-muted))] mb-3">{{ client.email }}</p>
                <div class="flex items-center justify-between text-xs text-[hsl(var(--text-muted))] pt-3 border-t border-[hsl(var(--border))]">
                    <span>{{ client.invoices_count }} facture{{ client.invoices_count > 1 ? 's' : '' }}</span>
                    <span class="font-semibold text-[hsl(var(--text-main))]">{{ client.total_revenue.toLocaleString('fr-FR') }} €</span>
                </div>
            </div>
        </div>
    </div>
</template>
