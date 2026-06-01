<script setup lang="ts">
import { fakeProspects } from '../data/fake';

const statusCls: Record<string, string> = {
    'En cours':   'bg-blue-500/15 text-blue-400 border-blue-500/20',
    'À relancer': 'bg-amber-500/15 text-amber-400 border-amber-500/20',
    'Nouveau':    'bg-purple-500/15 text-purple-400 border-purple-500/20',
};
</script>

<template>
    <div class="p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-[hsl(var(--text-main))]">Prospects</h1>
                <p class="text-sm text-[hsl(var(--text-muted))] mt-0.5">{{ fakeProspects.length }} prospects en pipeline</p>
            </div>
            <button class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[hsl(var(--primary))] text-white text-sm font-medium opacity-60 cursor-not-allowed" disabled>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau prospect
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="p in fakeProspects"
                :key="p.id"
                class="liquid-glass p-5"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-sm font-bold text-purple-400">
                        {{ p.name.split(' ').map((w: string) => w[0]).join('').slice(0, 2) }}
                    </div>
                    <span :class="['text-xs px-2 py-0.5 rounded-full border', statusCls[p.status]]">{{ p.status }}</span>
                </div>
                <h3 class="font-semibold text-[hsl(var(--text-main))] mb-1">{{ p.name }}</h3>
                <p class="text-xs text-[hsl(var(--text-muted))] mb-1">{{ p.email }}</p>
                <div class="flex items-center justify-between text-xs text-[hsl(var(--text-muted))] pt-3 border-t border-[hsl(var(--border))] mt-3">
                    <span>Source : {{ p.source }}</span>
                    <span>{{ p.created_at }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
