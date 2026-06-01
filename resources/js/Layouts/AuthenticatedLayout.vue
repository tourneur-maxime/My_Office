<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NotificationCenter from '@/Components/NotificationCenter.vue';
import ToastContainer from '@/Components/Common/ToastContainer.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { useToast } from '@/Composables/useToast';
import { Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { useThemeStore } from '@/Stores/useThemeStore';

const showingNavigationDropdown = ref(false);
const { success, error } = useToast();
const page = usePage();
const themeStore = useThemeStore();
const companyProfile = page.props.companyProfile;

const toggleTheme = () => {
    const oldMode = themeStore.preferences.mode;
    const newMode = oldMode === 'dark' ? 'light' : 'dark';

    // Update local store immediately for visual feedback
    themeStore.updatePreferences({ mode: newMode });

    // Persist to backend without reloading page props
    fetch(route('settings.appearance.update'), {
        method: 'PATCH',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            mode: newMode,
            primary_color: themeStore.preferences.primary_color,
            gray_shade: themeStore.preferences.gray_shade,
            radius: themeStore.preferences.radius,
            card_border_style: themeStore.preferences.card_border_style,
        }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
    })
    .catch((err) => {
        // Revert on error
        themeStore.updatePreferences({ mode: oldMode });
        console.error('Failed to save theme preferences:', err);
        error('Erreur lors de la sauvegarde des préférences');
    });
};

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        if (flash.success) success(flash.success);
        if (flash.error) error(flash.error);
    },
    { deep: true },
);

// Watch for theme preferences changes from server
watch(
    () => page.props.auth.user.theme_preferences,
    (newPrefs) => {
        if (newPrefs) {
            themeStore.updatePreferences(newPrefs);
        }
    },
    { deep: true, immediate: false }
);

// Initial check on mount
onMounted(() => {
    // Flash messages
    if (page.props.flash.success) success(page.props.flash.success);
    if (page.props.flash.error) error(page.props.flash.error);

    // Initialize Theme
    themeStore.initTheme(page.props.auth.user.theme_preferences);
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-[hsl(var(--bg-base))] flex flex-col transition-colors duration-300">
            <nav class="nav-glass sticky top-0 z-50 transition-colors duration-300">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <img
                                        v-if="companyProfile?.logo_path"
                                        :src="`/storage/${companyProfile.logo_path}`"
                                        alt="Logo entreprise"
                                        class="block h-9 w-auto"
                                    />
                                    <ApplicationLogo
                                        v-else
                                        class="block h-9 w-auto text-[hsl(var(--primary))]"
                                    />
                                </Link>
                                <span class="ml-2 font-bold text-[hsl(var(--text-main))] hidden lg:block">
                                    {{ companyProfile?.company_name || 'My Office' }}
                                </span>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Tableau de bord
                                </NavLink>
                                <NavLink
                                    :href="route('clients.index')"
                                    :active="route().current('clients.*')"
                                >
                                    Clients
                                </NavLink>
                                <NavLink
                                    :href="route('quotes.index')"
                                    :active="route().current('quotes.*')"
                                >
                                    Devis
                                </NavLink>
                                <NavLink
                                    :href="route('invoices.index')"
                                    :active="route().current('invoices.*')"
                                >
                                    Factures
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-2">
                            <!-- Notification Center -->
                            <NotificationCenter />

                            <!-- Theme Toggle -->
                            <button
                                @click="toggleTheme"
                                class="p-2 rounded-[var(--radius)] text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))] hover:bg-[hsla(var(--text-main),0.05)] transition-all duration-200"
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

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-medium leading-4 text-[hsl(var(--text-muted))] transition duration-150 ease-in-out hover:text-[hsl(var(--text-main))] focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="block px-4 py-2 text-xs text-[hsl(var(--text-muted))]">
                                            Gérer mon compte
                                        </div>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Mon Profil
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('settings.appearance')"
                                        >
                                            Apparence & Thème
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('settings.company')"
                                        >
                                            Profil Entreprise
                                        </DropdownLink>
                                        <div class="border-t border-[hsl(var(--border))]"></div>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Déconnexion
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-[hsl(var(--text-muted))] transition duration-150 ease-in-out hover:bg-[hsl(var(--bg-surface))]/50 hover:text-[hsl(var(--text-main))] focus:bg-[hsl(var(--bg-surface))]/50 focus:text-[hsl(var(--text-main))] focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Tableau de bord
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('clients.index')"
                            :active="route().current('clients.*')"
                        >
                            Clients
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('quotes.index')"
                            :active="route().current('quotes.*')"
                        >
                            Devis
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('invoices.index')"
                            :active="route().current('invoices.*')"
                        >
                            Factures
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-[hsl(var(--border))] pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-[hsl(var(--text-main))]">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-[hsl(var(--text-muted))]">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Mon Profil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('settings.appearance')">
                                Apparence
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('settings.company')"
                            >
                                Profil Entreprise
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Déconnexion
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-[hsl(var(--bg-surface))] shadow-sm relative z-10 transition-colors duration-300" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-grow relative z-0">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))] py-6 transition-colors duration-300">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="flex flex-col md:flex-row items-center justify-between text-sm text-[hsl(var(--text-muted))]"
                    >
                        <div class="flex items-center gap-2 mb-2 md:mb-0">
                            <img
                                v-if="companyProfile?.logo_path"
                                :src="`/storage/${companyProfile.logo_path}`"
                                alt="Logo entreprise"
                                class="h-5 w-auto"
                            />
                            <ApplicationLogo v-else class="h-5 w-5" />
                            <span>{{ companyProfile?.company_name || 'My Office' }} - Solution de facturation</span>
                        </div>
                        <div class="flex space-x-4">
                            <span class="bg-[hsl(var(--bg-elevated))] px-2 py-1 rounded text-[hsl(var(--text-muted))]">v{{ $page.props.app_version }}</span>
                            <span class="bg-[hsl(var(--primary))]/10 text-[hsl(var(--primary))] px-2 py-1 rounded font-medium"
                                >Factur-X
                                {{ $page.props.facturx_version }}</span
                            >
                        </div>
                    </div>
                </div>
            </footer>

            <!-- Toast Notifications -->
            <ToastContainer />
        </div>
    </div>
</template>