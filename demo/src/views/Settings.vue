<script setup lang="ts">
import { inject, type Ref } from 'vue';
import { fakeCompany, fakeUser } from '../data/fake';

const isDark = inject<Ref<boolean>>('isDark')!;
const toggleDark = inject<() => void>('toggleDark')!;

const accentColors = [
    { label: 'Bleu (actuel)', value: '210 100% 50%', active: true },
    { label: 'Indigo', value: '245 80% 56%', active: false },
    { label: 'Violet', value: '270 75% 56%', active: false },
    { label: 'Vert', value: '152 60% 44%', active: false },
];

const invoicePrefix = 'FAC';
const quotePrefix = 'DEV';
const currentYear = new Date().getFullYear();
const nextNumber = 15;
</script>

<template>
    <div class="p-6 space-y-6 max-w-2xl">
        <div>
            <h1 class="text-xl font-bold text-[hsl(var(--text-main))]">Paramètres</h1>
            <p class="text-sm text-[hsl(var(--text-muted))] mt-0.5">Configuration de votre espace de travail</p>
        </div>

        <!-- Company -->
        <section class="liquid-glass overflow-hidden">
            <div class="px-5 py-3 border-b border-[hsl(var(--border))]">
                <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Entreprise</h2>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-[hsl(var(--border))]">
                    <div class="w-14 h-14 rounded-2xl bg-[hsl(var(--primary))]/10 border border-[hsl(var(--primary))]/20 flex items-center justify-center">
                        <span class="text-lg font-bold text-[hsl(var(--primary))]">{{ fakeCompany.name.slice(0, 2) }}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-[hsl(var(--text-main))]">{{ fakeCompany.name }}</p>
                        <p class="text-xs text-[hsl(var(--text-muted))] mt-0.5">Freelance / Auto-entrepreneur</p>
                    </div>
                    <button class="ml-auto text-xs px-3 py-1.5 rounded-lg border border-[hsl(var(--border))] text-[hsl(var(--text-muted))] opacity-60 cursor-not-allowed" disabled>
                        Modifier le logo
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="field in [
                        { label: 'Raison sociale', value: fakeCompany.name },
                        { label: 'SIRET', value: fakeCompany.siret },
                        { label: 'N° de TVA', value: fakeCompany.vat },
                        { label: 'IBAN', value: fakeCompany.iban },
                    ]" :key="field.label">
                        <label class="block text-xs font-medium text-[hsl(var(--text-muted))] mb-1.5">{{ field.label }}</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-base))]/50 text-sm text-[hsl(var(--text-main))] font-mono">
                            {{ field.value }}
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-[hsl(var(--text-muted))] mb-1.5">Adresse</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-base))]/50 text-sm text-[hsl(var(--text-main))]">
                            {{ fakeCompany.address }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Appearance -->
        <section class="liquid-glass overflow-hidden">
            <div class="px-5 py-3 border-b border-[hsl(var(--border))]">
                <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Apparence</h2>
            </div>
            <div class="p-5 space-y-5">
                <!-- Dark mode -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-[hsl(var(--text-main))]">Mode sombre</p>
                        <p class="text-xs text-[hsl(var(--text-muted))] mt-0.5">Basculer entre l'interface claire et sombre</p>
                    </div>
                    <button
                        @click="toggleDark()"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                            isDark ? 'bg-[hsl(var(--primary))]' : 'bg-[hsl(var(--border-strong))]'
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                                isDark ? 'translate-x-6' : 'translate-x-1'
                            ]"
                        />
                    </button>
                </div>
                <!-- Accent color -->
                <div>
                    <p class="text-sm font-medium text-[hsl(var(--text-main))] mb-2">Couleur d'accent</p>
                    <div class="flex items-center gap-3">
                        <button
                            v-for="color in accentColors"
                            :key="color.value"
                            class="relative w-7 h-7 rounded-full border-2 transition-transform hover:scale-110 focus:outline-none"
                            :style="{
                                backgroundColor: `hsl(${color.value})`,
                                borderColor: color.active ? `hsl(${color.value})` : 'transparent',
                                boxShadow: color.active ? `0 0 0 3px hsl(${color.value} / 0.25)` : 'none'
                            }"
                            :title="color.label"
                        >
                            <span v-if="color.active" class="absolute inset-0 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>
                        <span class="text-xs text-[hsl(var(--text-muted))]">Personnalisation disponible dans la version complète</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Numbering -->
        <section class="liquid-glass overflow-hidden">
            <div class="px-5 py-3 border-b border-[hsl(var(--border))]">
                <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Numérotation</h2>
            </div>
            <div class="p-5 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-[hsl(var(--text-muted))] mb-1.5">Préfixe facture</label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 px-3 py-2 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-base))]/50 text-sm font-mono text-[hsl(var(--text-main))]">
                            {{ invoicePrefix }}
                        </div>
                        <span class="text-xs text-[hsl(var(--text-muted))]">→ {{ invoicePrefix }}-{{ currentYear }}-{{ String(nextNumber).padStart(3, '0') }}</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-[hsl(var(--text-muted))] mb-1.5">Préfixe devis</label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 px-3 py-2 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-base))]/50 text-sm font-mono text-[hsl(var(--text-main))]">
                            {{ quotePrefix }}
                        </div>
                        <span class="text-xs text-[hsl(var(--text-muted))]">→ {{ quotePrefix }}-{{ currentYear }}-008</span>
                    </div>
                </div>
                <div class="col-span-2 pt-2 border-t border-[hsl(var(--border))]">
                    <p class="text-xs text-[hsl(var(--text-muted))]">Remise à zéro automatique chaque année · Séquence par type de document</p>
                </div>
            </div>
        </section>

        <!-- Account -->
        <section class="liquid-glass overflow-hidden">
            <div class="px-5 py-3 border-b border-[hsl(var(--border))]">
                <h2 class="text-sm font-semibold text-[hsl(var(--text-main))]">Compte</h2>
            </div>
            <div class="p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-[hsl(var(--primary))]/20 flex items-center justify-center text-sm font-bold text-[hsl(var(--primary))]">
                    MT
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-[hsl(var(--text-main))]">{{ fakeUser.name }}</p>
                    <p class="text-xs text-[hsl(var(--text-muted))]">{{ fakeUser.email }}</p>
                </div>
                <button class="text-xs px-3 py-1.5 rounded-lg border border-[hsl(var(--border))] text-[hsl(var(--text-muted))] opacity-60 cursor-not-allowed" disabled>
                    Modifier le profil
                </button>
            </div>
        </section>
    </div>
</template>
