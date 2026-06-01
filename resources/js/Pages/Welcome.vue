<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useThemeStore } from '@/Stores/useThemeStore';
import { onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const page = usePage();
const themeStore = useThemeStore();

const toggleTheme = () => {
    const newMode = themeStore.preferences.mode === 'dark' ? 'light' : 'dark';
    themeStore.updatePreferences({ mode: newMode });
};

onMounted(() => {
    themeStore.initTheme();
});
</script>

<template>
    <Head title="Bienvenue" />

    <div class="min-h-screen bg-gradient-to-br from-[hsl(var(--bg-base))] via-[hsl(var(--bg-surface))] to-blue-50 dark:to-gray-950 font-sans text-[hsl(var(--text-main))] relative overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-[hsl(var(--primary))]/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s"></div>
        </div>

        <!-- Navbar with Glass Effect -->
        <nav class="fixed top-0 left-0 right-0 z-50 nav-glass">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex items-center gap-2">
                        <ApplicationLogo class="h-8 w-8 text-[hsl(var(--primary))]" />
                        <span class="text-xl font-bold tracking-tight text-[hsl(var(--text-main))]">My Office</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Theme Toggle -->
                        <button
                            @click="toggleTheme"
                            class="p-2 rounded-lg text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))] hover:bg-[hsla(var(--text-main),0.05)] transition-all duration-200"
                            :title="themeStore.preferences.mode === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'"
                        >
                            <svg v-if="themeStore.preferences.mode === 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <div v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-primary">
                                Mon Bureau
                            </Link>
                            <template v-else>
                                <Link :href="route('login')" class="btn btn-ghost">Connexion</Link>
                                <Link v-if="canRegister" :href="route('register')" class="btn btn-primary">
                                    Inscription
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <header class="relative pt-32 pb-20 px-4">
            <div class="mx-auto max-w-5xl text-center relative z-10">
                <div class="mb-6 inline-block animate-[fadeInDown_0.8s_ease-out]">
                    <span class="liquid-glass px-4 py-2 rounded-full text-sm font-medium text-[hsl(var(--primary))] flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        Nouveau : Builder de documents personnalisé
                    </span>
                </div>
                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight mb-6 text-[hsl(var(--text-main))] leading-tight animate-[fadeInUp_1s_ease-out]">
                    La facturation simplifiée pour les
                    <span class="bg-gradient-to-r from-[hsl(var(--primary))] via-cyan-500 to-blue-600 bg-clip-text text-transparent">
                        micro-entrepreneurs
                    </span>
                </h1>
                <p class="mx-auto max-w-2xl text-xl text-[hsl(var(--text-muted))] mb-10 leading-relaxed animate-[fadeInUp_1.2s_ease-out]">
                    Gérez vos clients, créez vos devis et générez des factures conformes Factur-X en quelques secondes.
                    <span class="font-semibold text-[hsl(var(--text-main))]">Simple, rapide, professionnel.</span>
                </p>
                <div class="flex justify-center gap-4 flex-wrap animate-[fadeInUp_1.4s_ease-out]">
                    <Link :href="route('register')" class="btn btn-primary text-lg px-8 py-3 shadow-lg shadow-[hsl(var(--primary))]/30 hover:shadow-[hsl(var(--primary))]/40 hover:scale-105 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        Commencer gratuitement
                    </Link>
                    <a href="#features" class="btn btn-outline text-lg px-8 py-3 transition-all duration-300 ease-in-out hover:scale-105">
                        En savoir plus
                    </a>
                </div>
            </div>
        </header>

        <!-- Features with Liquid Glass Cards -->
        <section id="features" class="relative py-24 px-4">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold mb-4 text-[hsl(var(--text-main))]">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p class="text-xl text-[hsl(var(--text-muted))] max-w-2xl mx-auto">
                        Des outils puissants pour gérer votre activité en toute simplicité
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="liquid-glass p-8 hover:scale-[1.03] hover:shadow-xl transition-all duration-300 group animate-[fadeInUp_0.6s_ease-out]">
                        <div class="h-16 w-16 bg-gradient-to-br from-[hsl(var(--primary))]/20 to-cyan-500/20 text-[hsl(var(--primary))] rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3 text-[hsl(var(--text-main))]">Gestion des Clients</h3>
                        <p class="text-[hsl(var(--text-muted))] leading-relaxed">
                            Suivez vos prospects et gérez votre base client en un clin d'œil. Organisez vos contacts et suivez vos relations commerciales.
                        </p>
                    </div>

                    <div class="liquid-glass p-8 hover:scale-[1.03] hover:shadow-xl transition-all duration-300 group">
                        <div class="h-16 w-16 bg-gradient-to-br from-[hsl(var(--primary))]/20 to-cyan-500/20 text-[hsl(var(--primary))] rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3 text-[hsl(var(--text-main))]">Devis & Factures</h3>
                        <p class="text-[hsl(var(--text-muted))] leading-relaxed">
                            Convertissez vos devis en factures en un clic. Personnalisez vos documents avec notre builder visuel et créez votre identité de marque.
                        </p>
                    </div>

                    <div class="liquid-glass p-8 hover:scale-[1.03] hover:shadow-xl transition-all duration-300 group">
                        <div class="h-16 w-16 bg-gradient-to-br from-[hsl(var(--primary))]/20 to-cyan-500/20 text-[hsl(var(--primary))] rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3 text-[hsl(var(--text-main))]">Conformité Factur-X</h3>
                        <p class="text-[hsl(var(--text-muted))] leading-relaxed">
                            Générez des PDF hybrides conformes aux dernières normes fiscales françaises. Validation automatique et export XML inclus.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="relative py-20 px-4">
            <div class="mx-auto max-w-4xl">
                <div class="liquid-glass p-12 text-center rounded-3xl hover:scale-[1.02] transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[hsl(var(--primary))]/5 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-cyan-500/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <h2 class="text-3xl sm:text-4xl font-bold mb-4 text-[hsl(var(--text-main))]">
                            Prêt à simplifier votre facturation ?
                        </h2>
                        <p class="text-lg text-[hsl(var(--text-muted))] mb-8 max-w-2xl mx-auto">
                            Rejoignez des centaines de micro-entrepreneurs qui font confiance à My Office pour gérer leur activité.
                        </p>
                        <Link :href="route('register')" class="btn btn-primary text-lg px-8 py-3 shadow-lg shadow-[hsl(var(--primary))]/30 hover:shadow-[hsl(var(--primary))]/50 hover:scale-105 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Créer mon compte gratuitement
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative border-t border-[hsl(var(--border))]/50 py-12 bg-[hsl(var(--bg-surface))]/50 backdrop-blur-xl">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-wrap justify-between items-center text-sm text-[hsl(var(--text-muted))] gap-4">
                <div class="flex items-center gap-2">
                    <ApplicationLogo class="h-5 w-5 text-[hsl(var(--primary))]" />
                    <span>&copy; 2026 My Office. Tous droits réservés.</span>
                </div>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-[hsl(var(--primary))] transition-colors">Conditions</a>
                    <a href="#" class="hover:text-[hsl(var(--primary))] transition-colors">Confidentialité</a>
                </div>
            </div>
        </footer>
    </div>
</template>