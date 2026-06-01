<script setup lang="ts">
import { ref, provide } from 'vue';
import Login from './views/Login.vue';
import Dashboard from './views/Dashboard.vue';
import Invoices from './views/Invoices.vue';
import Quotes from './views/Quotes.vue';
import Clients from './views/Clients.vue';
import Prospects from './views/Prospects.vue';
import Documents from './views/Documents.vue';
import Settings from './views/Settings.vue';
import Sidebar from './components/Sidebar.vue';

const loggedIn = ref(false);
const currentPage = ref('dashboard');

const pages: Record<string, object> = {
    dashboard: Dashboard,
    invoices: Invoices,
    quotes: Quotes,
    clients: Clients,
    prospects: Prospects,
    documents: Documents,
    settings: Settings,
};

// Dark mode
const stored = typeof window !== 'undefined' ? localStorage.getItem('theme') : null;
const prefersDark = typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
const isDark = ref(stored === 'dark' || (!stored && prefersDark));
if (isDark.value) document.documentElement.classList.add('dark');

function toggleDark() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}

provide('isDark', isDark);
provide('toggleDark', toggleDark);
</script>

<template>
    <!-- Demo badge -->
    <div class="demo-badge">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
        Démo — données fictives
    </div>

    <!-- Login screen -->
    <Login v-if="!loggedIn" @login="loggedIn = true" />

    <!-- App shell -->
    <div v-else class="flex h-screen overflow-hidden">
        <Sidebar :current="currentPage" @navigate="currentPage = $event" />
        <main
            :class="[
                'flex-1 overflow-y-auto bg-[hsl(var(--bg-base))]',
                currentPage === 'documents' ? 'overflow-hidden flex flex-col' : ''
            ]"
        >
            <component :is="pages[currentPage]" />
        </main>
    </div>
</template>
