<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import KpiConfigModal from '@/Components/KpiConfigModal.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stats: Object,
    recent_invoices: Array,
    recent_quotes: Array,
    favorites: Array,
    favorite_clients: Array,
    favorite_prospects: Array,
    recent_clients: Array,
    recent_prospects: Array,
    kpi_preferences: Array,
});

const showKpiConfig = ref(false);
const hoveredKpi = ref(null);

const availableKpis = [
    {
        id: 'clients',
        label: 'Clients',
        description: 'Nombre total de clients',
        icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />',
        color: 'bg-gradient-to-br from-blue-500/20 to-cyan-500/20 text-blue-600 dark:text-blue-400',
        textColor: 'text-[hsl(var(--text-main))]',
    },
    {
        id: 'prospects',
        label: 'Prospects',
        description: 'Nombre total de prospects',
        icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />',
        color: 'bg-gradient-to-br from-purple-500/20 to-pink-500/20 text-purple-600 dark:text-purple-400',
        textColor: 'text-[hsl(var(--text-main))]',
    },
    {
        id: 'quotes_pending',
        label: 'Devis en attente',
        description: 'Devis envoyés en attente',
        icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
        color: 'bg-gradient-to-br from-orange-500/20 to-amber-500/20 text-orange-600 dark:text-orange-400',
        textColor: 'text-orange-500',
    },
    {
        id: 'invoices_unpaid',
        label: 'Factures impayées',
        description: 'Factures en attente de paiement',
        icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        color: 'bg-gradient-to-br from-red-500/20 to-rose-500/20 text-red-600 dark:text-red-400',
        textColor: 'text-red-500',
    },
    {
        id: 'revenue_this_month',
        label: 'CA ce mois',
        description: 'Chiffre d\'affaires du mois',
        icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />',
        color: 'bg-gradient-to-br from-green-500/20 to-emerald-500/20 text-green-600 dark:text-green-400',
        textColor: 'text-green-600 dark:text-green-400',
    },
    {
        id: 'revenue_total',
        label: 'CA total',
        description: 'Chiffre d\'affaires total',
        icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        color: 'bg-gradient-to-br from-[hsl(var(--primary))]/20 to-cyan-500/20 text-[hsl(var(--primary))]',
        textColor: 'text-[hsl(var(--primary))]',
    },
];

const isKpiVisible = (kpiId) => {
    if (!props.kpi_preferences || props.kpi_preferences.length === 0) {
        return true; // Show all by default
    }
    const pref = props.kpi_preferences.find(p => p.id === kpiId);
    return pref ? pref.visible : true;
};

const visibleKpis = computed(() => {
    return availableKpis.filter(kpi => isKpiVisible(kpi.id));
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(value || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getKpiRoute = (kpiId) => {
    const routes = {
        clients: route('clients.index', { status: 'client' }),
        prospects: route('clients.index', { status: 'prospect' }),
        quotes_pending: route('quotes.index'),
        invoices_unpaid: route('invoices.index'),
    };
    return routes[kpiId] || null;
};

const getExpandedList = (kpiId) => {
    if (kpiId === 'clients') {
        return props.favorite_clients.length > 0 ? props.favorite_clients : props.recent_clients;
    } else if (kpiId === 'prospects') {
        return props.favorite_prospects.length > 0 ? props.favorite_prospects : props.recent_prospects;
    }
    return [];
};

const getExpandedListTitle = (kpiId) => {
    if (kpiId === 'clients') {
        return props.favorite_clients.length > 0 ? 'Favoris' : 'Derniers ajoutés';
    } else if (kpiId === 'prospects') {
        return props.favorite_prospects.length > 0 ? 'Favoris' : 'Derniers ajoutés';
    }
    return '';
};
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-900 dark:text-white">
                    Tableau de bord
                </h2>
                <button
                    @click="showKpiConfig = true"
                    class="btn btn-outline flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configurer
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Stats Grid with Hover Expansion -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <template v-for="kpi in visibleKpis.slice(0, 4)" :key="kpi.id">
                        <div
                            v-if="['clients', 'prospects'].includes(kpi.id)"
                            class="relative"
                            @mouseenter="hoveredKpi = kpi.id"
                            @mouseleave="hoveredKpi = null"
                        >
                            <Link
                                :href="getKpiRoute(kpi.id)"
                                class="liquid-glass p-6 group transition-all duration-300 cursor-pointer block"
                                :class="hoveredKpi === kpi.id ? 'scale-[1.02]' : ''"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-[hsl(var(--text-muted))]">{{ kpi.label }}</p>
                                        <p class="text-3xl font-bold mt-2" :class="kpi.textColor">
                                            {{ stats[kpi.id] }}
                                        </p>
                                    </div>
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" :class="kpi.color">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" v-html="kpi.icon">
                                        </svg>
                                    </div>
                                </div>
                            </Link>

                            <!-- Expanded List -->
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div
                                    v-if="hoveredKpi === kpi.id && getExpandedList(kpi.id).length > 0"
                                    class="absolute top-full left-0 right-0 mt-2 liquid-glass p-4 z-10"
                                >
                                    <div class="text-xs font-semibold text-[hsl(var(--text-muted))] mb-2">
                                        {{ getExpandedListTitle(kpi.id) }}
                                    </div>
                                    <div class="space-y-2">
                                        <Link
                                            v-for="item in getExpandedList(kpi.id)"
                                            :key="item.id"
                                            :href="route('clients.show', item.id)"
                                            class="block p-2 rounded-lg hover:bg-[hsl(var(--primary))]/10 transition-colors"
                                        >
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" :class="kpi.color">
                                                    {{ (item.alias || item.name).charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-medium text-sm text-[hsl(var(--text-main))] truncate">
                                                        {{ item.alias || item.name }}
                                                    </div>
                                                    <div class="text-xs text-[hsl(var(--text-muted))] truncate">
                                                        {{ item.company }}
                                                    </div>
                                                </div>
                                                <svg v-if="item.is_favorite" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-500" viewBox="0 0 24 24" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <Link
                            v-else
                            :href="getKpiRoute(kpi.id) || '#'"
                            :class="[
                                'liquid-glass p-6 group hover:scale-[1.02] transition-all duration-300',
                                getKpiRoute(kpi.id) ? 'cursor-pointer' : 'cursor-default'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-[hsl(var(--text-muted))]">{{ kpi.label }}</p>
                                    <p class="text-3xl font-bold mt-2" :class="kpi.textColor">
                                        <template v-if="['revenue_this_month', 'revenue_total'].includes(kpi.id)">
                                            {{ formatCurrency(stats[kpi.id]) }}
                                        </template>
                                        <template v-else>
                                            {{ stats[kpi.id] }}
                                        </template>
                                    </p>
                                </div>
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform" :class="kpi.color">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" v-html="kpi.icon">
                                    </svg>
                                </div>
                            </div>
                        </Link>
                    </template>
                </div>

                <!-- Favorites Section -->
                <div v-if="favorites && favorites.length > 0" class="liquid-glass p-6">
                    <h3 class="text-lg font-semibold mb-4 text-[hsl(var(--text-main))] flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                        </svg>
                        Favoris
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <Link
                            v-for="favorite in favorites"
                            :key="favorite.id"
                            :href="route('clients.show', favorite.id)"
                            class="p-4 rounded-xl hover:bg-[hsl(var(--primary))]/5 transition-all border border-transparent hover:border-[hsl(var(--primary))]/20 group flex flex-col items-center text-center"
                        >
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[hsl(var(--primary))]/20 to-cyan-500/20 flex items-center justify-center text-[hsl(var(--primary))] mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="font-medium text-sm text-[hsl(var(--text-main))] group-hover:text-[hsl(var(--primary))] transition-colors">
                                {{ favorite.alias || favorite.name }}
                            </div>
                            <div v-if="favorite.alias" class="text-xs text-[hsl(var(--text-muted))] mt-0.5">
                                {{ favorite.name }}
                            </div>
                            <div class="text-xs text-[hsl(var(--text-muted))] mt-1">
                                {{ favorite.company }}
                            </div>
                            <span v-if="favorite.status === 'client'" class="mt-2 px-2 py-0.5 text-xs rounded-full bg-blue-500/20 text-blue-600 dark:text-blue-400">
                                Client
                            </span>
                            <span v-else class="mt-2 px-2 py-0.5 text-xs rounded-full bg-purple-500/20 text-purple-600 dark:text-purple-400">
                                Prospect
                            </span>
                        </Link>
                    </div>
                </div>

                <!-- Additional KPIs (if more than 4) -->
                <div v-if="visibleKpis.length > 4" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <template v-for="kpi in visibleKpis.slice(4)" :key="kpi.id">
                        <div class="liquid-glass p-8 group hover:scale-[1.02] transition-all duration-300 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 rounded-full blur-3xl" :class="kpi.color.replace('text-', 'bg-').replace('dark:text-', 'dark:bg-')"></div>
                            <div class="relative">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="kpi.color">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" v-html="kpi.icon">
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-[hsl(var(--text-muted))]">
                                        {{ kpi.label }}
                                    </p>
                                </div>
                                <p class="text-4xl font-bold" :class="kpi.textColor">
                                    <template v-if="['revenue_this_month', 'revenue_total'].includes(kpi.id)">
                                        {{ formatCurrency(stats[kpi.id]) }}
                                    </template>
                                    <template v-else>
                                        {{ stats[kpi.id] }}
                                    </template>
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Quick Actions -->
                <div class="liquid-glass p-8">
                    <h3 class="text-lg font-semibold mb-6 text-center text-[hsl(var(--text-main))] flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(var(--primary))]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Actions rapides
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <Link :href="route('clients.create')" class="btn btn-primary group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Nouveau prospect
                        </Link>
                        <Link :href="route('quotes.create')" class="btn btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Nouveau devis
                        </Link>
                        <Link :href="route('invoices.create')" class="btn btn-outline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Nouvelle facture
                        </Link>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Quotes -->
                    <div class="liquid-glass p-6">
                        <h3 class="text-lg font-semibold mb-4 text-[hsl(var(--text-main))] flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(var(--primary))]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Derniers devis
                        </h3>
                        <div v-if="recent_quotes.length > 0" class="space-y-3">
                            <Link
                                v-for="quote in recent_quotes"
                                :key="quote.id"
                                :href="route('quotes.show', quote.id)"
                                class="block p-3 rounded-xl hover:bg-[hsl(var(--primary))]/5 transition-all border border-transparent hover:border-[hsl(var(--primary))]/20 group"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <span class="font-medium text-[hsl(var(--text-main))] group-hover:text-[hsl(var(--primary))] transition-colors">
                                            {{ quote.quote_number }}
                                        </span>
                                        <p class="text-sm text-[hsl(var(--text-muted))]">
                                            {{ quote.client?.company || quote.client?.name }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-bold text-[hsl(var(--primary))]">
                                        {{ formatCurrency(quote.total) }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-apple-dark-secondary text-center py-4">
                            Aucun devis pour le moment
                        </p>
                    </div>

                    <!-- Recent Invoices -->
                    <div class="liquid-glass p-6">
                        <h3 class="text-lg font-semibold mb-4 text-[hsl(var(--text-main))] flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[hsl(var(--primary))]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Dernières factures
                        </h3>
                        <div v-if="recent_invoices.length > 0" class="space-y-3">
                            <Link
                                v-for="invoice in recent_invoices"
                                :key="invoice.id"
                                :href="route('invoices.show', invoice.id)"
                                class="block p-3 rounded-xl hover:bg-[hsl(var(--primary))]/5 transition-all border border-transparent hover:border-[hsl(var(--primary))]/20 group"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <span class="font-medium text-[hsl(var(--text-main))] group-hover:text-[hsl(var(--primary))] transition-colors">
                                            {{ invoice.invoice_number }}
                                        </span>
                                        <p class="text-sm text-[hsl(var(--text-muted))]">
                                            {{ invoice.client?.company || invoice.client?.name }}
                                        </p>
                                    </div>
                                    <span class="text-sm font-bold text-[hsl(var(--primary))]">
                                        {{ formatCurrency(invoice.total) }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-apple-dark-secondary text-center py-4">
                            Aucune facture pour le moment
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Configuration Modal -->
        <KpiConfigModal
            :show="showKpiConfig"
            :kpi-preferences="kpi_preferences"
            :available-kpis="availableKpis"
            @close="showKpiConfig = false"
        />
    </AuthenticatedLayout>
</template>
