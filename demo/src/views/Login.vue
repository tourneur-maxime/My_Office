<script setup lang="ts">
const emit = defineEmits<{ login: [] }>();
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-[hsl(var(--bg-base))]">
        <div class="w-full max-w-sm px-6">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[hsl(var(--primary))]/10 border border-[hsl(var(--primary))]/20 mb-4">
                    <svg class="w-7 h-7 text-[hsl(var(--primary))]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-[hsl(var(--text-main))]">My Office</h1>
                <p class="text-sm text-[hsl(var(--text-muted))] mt-1">Gestion freelance simplifiée</p>
            </div>

            <!-- Demo bypass -->
            <button
                @click="emit('login')"
                class="w-full py-3 px-4 rounded-xl font-semibold text-white text-sm mb-4 flex items-center justify-center gap-2"
                style="background: linear-gradient(135deg, hsl(210 100% 50%), hsl(220 90% 56%))"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Accès démo — connexion instantanée
            </button>

            <div class="relative my-5">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[hsl(var(--border))]"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-3 bg-[hsl(var(--bg-base))] text-xs text-[hsl(var(--text-muted))]">ou connexion normale</span>
                </div>
            </div>

            <!-- Normal form (non-functional in demo) -->
            <div class="space-y-4 opacity-50 pointer-events-none select-none">
                <div>
                    <label class="block text-sm font-medium text-[hsl(var(--text-main))] mb-1.5">Adresse e-mail</label>
                    <input type="email" placeholder="vous@exemple.fr"
                        class="w-full px-4 py-2.5 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))] text-sm focus:outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-[hsl(var(--text-main))] mb-1.5">Mot de passe</label>
                    <input type="password" placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))] text-sm focus:outline-none" />
                </div>
                <button class="w-full py-2.5 rounded-xl bg-[hsl(var(--primary))] text-white text-sm font-semibold opacity-40 cursor-not-allowed">
                    Se connecter
                </button>
            </div>

            <p class="text-center text-xs text-[hsl(var(--text-muted))] mt-6">
                Démo en lecture seule — aucune donnée réelle
            </p>
        </div>
    </div>
</template>
