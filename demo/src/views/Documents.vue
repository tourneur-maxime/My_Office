<script setup lang="ts">
import { ref, computed } from 'vue';
import { fakeDocuments } from '../data/fake';

const selectedId = ref(fakeDocuments[0].id);
const selected = computed(() => fakeDocuments.find(d => d.id === selectedId.value)!);

const typeBadge: Record<string, string> = {
    'Contrat':        'bg-blue-500/15 text-blue-400 border-blue-500/20',
    'Proposition':    'bg-purple-500/15 text-purple-400 border-purple-500/20',
    'Document légal': 'bg-amber-500/15 text-amber-400 border-amber-500/20',
    'Template':       'bg-green-500/15 text-green-400 border-green-500/20',
};

const typeIcon: Record<string, string> = {
    'Contrat':        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'Proposition':    'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'Document légal': 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
    'Template':       'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414A1 1 0 0120 8.414V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2',
};
</script>

<template>
    <div class="flex h-full">
        <!-- Left: document list -->
        <aside class="w-64 shrink-0 border-r border-[hsl(var(--border))] flex flex-col">
            <div class="px-5 py-4 border-b border-[hsl(var(--border))] flex items-center justify-between">
                <div>
                    <h1 class="text-sm font-bold text-[hsl(var(--text-main))]">Documents</h1>
                    <p class="text-xs text-[hsl(var(--text-muted))] mt-0.5">{{ fakeDocuments.length }} modèles</p>
                </div>
                <button
                    class="w-7 h-7 rounded-lg flex items-center justify-center bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))] opacity-60 cursor-not-allowed"
                    disabled title="Nouveau document (démo)"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto p-2 space-y-1">
                <button
                    v-for="doc in fakeDocuments"
                    :key="doc.id"
                    @click="selectedId = doc.id"
                    :class="[
                        'w-full text-left px-3 py-3 rounded-lg transition-all',
                        selectedId === doc.id
                            ? 'bg-[hsl(var(--primary))]/10 border border-[hsl(var(--primary))]/20'
                            : 'hover:bg-[hsl(var(--bg-elevated))]'
                    ]"
                >
                    <div class="flex items-start gap-2.5">
                        <div :class="[
                            'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5',
                            selectedId === doc.id ? 'bg-[hsl(var(--primary))]/20' : 'bg-[hsl(var(--bg-elevated))]'
                        ]">
                            <svg :class="['w-3.5 h-3.5', selectedId === doc.id ? 'text-[hsl(var(--primary))]' : 'text-[hsl(var(--text-muted))]']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="typeIcon[doc.type]" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p :class="['text-xs font-medium truncate', selectedId === doc.id ? 'text-[hsl(var(--primary))]' : 'text-[hsl(var(--text-main))]']">
                                {{ doc.name }}
                            </p>
                            <p class="text-xs text-[hsl(var(--text-muted))] mt-0.5">{{ doc.updatedAt }}</p>
                        </div>
                    </div>
                </button>
            </nav>
        </aside>

        <!-- Right: document preview -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Doc header -->
            <div class="px-6 py-4 border-b border-[hsl(var(--border))] flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-bold text-[hsl(var(--text-main))]">{{ selected.name }}</h2>
                    <span :class="['text-xs px-2 py-0.5 rounded-full border font-medium', typeBadge[selected.type]]">
                        {{ selected.type }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[hsl(var(--text-muted))]">Modifié le {{ selected.updatedAt }}</span>
                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))] text-xs font-medium opacity-60 cursor-not-allowed" disabled>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Exporter PDF
                    </button>
                </div>
            </div>

            <!-- Fake toolbar -->
            <div class="px-6 py-2 border-b border-[hsl(var(--border))] flex items-center gap-1 shrink-0 bg-[hsl(var(--bg-surface))]/50">
                <span class="text-xs text-[hsl(var(--text-muted))] mr-2 font-medium">Formatage :</span>
                <button v-for="tool in ['Gras', 'Italique', 'Titre 2', 'Titre 3', 'Liste', 'Liste num.']" :key="tool"
                    class="px-2 py-1 rounded text-xs text-[hsl(var(--text-muted))] hover:bg-[hsl(var(--bg-elevated))] hover:text-[hsl(var(--text-main))] transition-colors"
                    disabled>{{ tool }}</button>
                <div class="flex-1" />
                <span class="text-xs px-2 py-1 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/20">
                    Mode lecture — démo
                </span>
            </div>

            <!-- Document content -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-2xl mx-auto px-8 py-8">
                    <div class="prose-demo" v-html="selected.content" />
                </div>
            </div>
        </main>
    </div>
</template>
