<script setup lang="ts">
import { fakeCompany, fakeUser } from '../data/fake';

defineProps<{ current: string }>();
const emit = defineEmits<{ navigate: [page: string] }>();

const nav = [
    { key: 'dashboard', label: 'Tableau de bord', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { key: 'invoices', label: 'Factures', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { key: 'quotes', label: 'Devis', icon: 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0120 8.414V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2' },
    { key: 'clients', label: 'Clients', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { key: 'prospects', label: 'Prospects', icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
];
</script>

<template>
    <aside class="w-64 shrink-0 h-screen sticky top-0 flex flex-col bg-[hsl(var(--bg-surface))]/80 backdrop-blur-xl border-r border-[hsl(var(--border))]">
        <!-- Company header -->
        <div class="px-5 py-4 border-b border-[hsl(var(--border))]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-[hsl(var(--primary))]/10 border border-[hsl(var(--primary))]/20 flex items-center justify-center">
                    <span class="text-xs font-bold text-[hsl(var(--primary))]">{{ fakeCompany.name.slice(0, 2) }}</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-[hsl(var(--text-main))]">{{ fakeCompany.name }}</p>
                    <p class="text-xs text-[hsl(var(--text-muted))]">Freelance</p>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <button
                v-for="item in nav"
                :key="item.key"
                @click="emit('navigate', item.key)"
                :class="[
                    'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all',
                    current === item.key
                        ? 'bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))]'
                        : 'text-[hsl(var(--text-muted))] hover:bg-[hsl(var(--bg-elevated))] hover:text-[hsl(var(--text-main))]'
                ]"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                </svg>
                {{ item.label }}
            </button>
        </nav>

        <!-- User footer -->
        <div class="px-4 py-3 border-t border-[hsl(var(--border))]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[hsl(var(--primary))]/20 flex items-center justify-center text-xs font-bold text-[hsl(var(--primary))]">
                    MT
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-[hsl(var(--text-main))] truncate">{{ fakeUser.name }}</p>
                    <p class="text-xs text-[hsl(var(--text-muted))] truncate">{{ fakeUser.email }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>
