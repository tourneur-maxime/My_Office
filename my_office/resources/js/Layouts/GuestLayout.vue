<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useThemeStore } from '@/Stores/useThemeStore';
import { onMounted } from 'vue';

const page = usePage();
const themeStore = useThemeStore();

const toggleTheme = () => {
    const newMode = themeStore.preferences.mode === 'dark' ? 'light' : 'dark';
    themeStore.updatePreferences({ mode: newMode });

    // Save to localStorage for guest users
    localStorage.setItem('theme_mode', newMode);
};

onMounted(() => {
    themeStore.initTheme();
});
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gradient-to-br from-apple-gray via-white to-blue-50 dark:from-apple-dark-bg dark:via-gray-900 dark:to-gray-950 pt-6 sm:justify-center sm:pt-0 relative"
    >
        <!-- Theme Toggle Button -->
        <div class="absolute top-4 right-4">
            <button
                @click="toggleTheme"
                class="p-3 rounded-full text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))] hover:bg-[hsla(var(--text-main),0.05)] transition-all duration-200 backdrop-blur-xl bg-[hsl(var(--bg-surface))]/60"
                :title="themeStore.preferences.mode === 'dark' ? 'Passer en mode clair' : 'Passer en mode sombre'"
            >
                <!-- Sun icon (shown in dark mode) -->
                <svg v-if="themeStore.preferences.mode === 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <!-- Moon icon (shown in light mode) -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>

        <div class="flex flex-col items-center gap-4">
            <Link href="/" class="group flex flex-col items-center gap-3 transition-all duration-300 hover:scale-105">
                <ApplicationLogo class="h-24 w-24 fill-current text-[hsl(var(--primary))] drop-shadow-lg transition-all duration-300 group-hover:drop-shadow-2xl" />
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-[hsl(var(--text-main))] tracking-tight">My Office</h1>
                    <p class="text-sm text-[hsl(var(--text-muted))] font-medium">Solution de facturation</p>
                </div>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden liquid-glass px-6 py-8 sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
