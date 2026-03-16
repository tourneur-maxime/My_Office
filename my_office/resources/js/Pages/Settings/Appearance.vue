<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useThemeStore } from '@/Stores/useThemeStore';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AutoSaveIndicator from '@/Components/Common/AutoSaveIndicator.vue';
import { useAutoSave } from '@/Composables/useAutoSave';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    preferences: Object,
});

const themeStore = useThemeStore();

// Don't re-initialize themeStore here - it's already initialized in AuthenticatedLayout
// Re-initializing would overwrite unsaved changes from the navbar toggle
// Instead, initialize form from current themeStore state (which is already synced)
const form = useForm({
    mode: props.preferences?.mode ?? themeStore.preferences.mode,
    primary_color: props.preferences?.primary_color ?? themeStore.preferences.primary_color,
    gray_shade: props.preferences?.gray_shade ?? themeStore.preferences.gray_shade,
    radius: props.preferences?.radius ?? themeStore.preferences.radius,
    card_border_style: props.preferences?.card_border_style ?? themeStore.preferences.card_border_style,
});

// Sync form to themeStore for live preview
let isUpdatingFromStore = false;
watch(() => form.data(), (newData) => {
    if (!isUpdatingFromStore) {
        themeStore.updatePreferences(newData);
    }
}, { deep: true });

// Sync themeStore to form (when navbar toggle is used)
watch(() => themeStore.preferences, (newPrefs) => {
    // Only update if values are different to avoid unnecessary re-renders
    if (form.mode !== newPrefs.mode ||
        form.primary_color !== newPrefs.primary_color ||
        form.gray_shade !== newPrefs.gray_shade ||
        form.radius !== newPrefs.radius ||
        form.card_border_style !== newPrefs.card_border_style) {

        isUpdatingFromStore = true;
        Object.assign(form, {
            mode: newPrefs.mode,
            primary_color: newPrefs.primary_color,
            gray_shade: newPrefs.gray_shade,
            radius: newPrefs.radius,
            card_border_style: newPrefs.card_border_style,
        });
        setTimeout(() => { isUpdatingFromStore = false; }, 0);
    }
}, { deep: true });

// Auto-save setup
const { isSaving, lastSaved } = useAutoSave(
    form,
    route('settings.appearance.update'),
    {
        delay: 2000,
        method: 'patch',
        preserveScroll: true,
    }
);

const submit = () => {
    form.patch(route('settings.appearance.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Toast notification could go here
        },
    });
};

const presets = [
    { name: 'Océan', color: '#0EA5E9', gradient: 'from-blue-500 to-cyan-500' },
    { name: 'Forêt', color: '#10B981', gradient: 'from-emerald-500 to-green-500' },
    { name: 'Lavande', color: '#A78BFA', gradient: 'from-violet-400 to-purple-500' },
    { name: 'Sunset', color: '#F59E0B', gradient: 'from-orange-400 to-pink-500' },
    { name: 'Rose', color: '#EC4899', gradient: 'from-pink-500 to-rose-500' },
    { name: 'Néon Mint', color: '#2DD4BF', gradient: 'from-teal-400 to-cyan-400' },
    { name: 'Indigo', color: '#6366F1', gradient: 'from-indigo-500 to-blue-500' },
    { name: 'Corail', color: '#FB7185', gradient: 'from-rose-400 to-orange-400' },
    { name: 'Monochrome', color: '#18181B', gradient: 'from-zinc-800 to-slate-900' },
];

const grays = [
    { value: 'slate', label: 'Slate (Bleuté)', class: 'bg-slate-500' },
    { value: 'gray', label: 'Cool Gray (Froid)', class: 'bg-gray-500' },
    { value: 'zinc', label: 'Zinc (Métal)', class: 'bg-zinc-500' },
    { value: 'neutral', label: 'Neutral (Neutre)', class: 'bg-neutral-500' },
    { value: 'stone', label: 'Stone (Chaud)', class: 'bg-stone-500' },
];

const radii = [
    { value: 'none', label: '0' },
    { value: 'sm', label: '2px' },
    { value: 'md', label: '6px' },
    { value: 'lg', label: '8px' },
    { value: 'xl', label: '12px' },
    { value: 'full', label: 'Full' },
];
</script>

<template>
    <Head title="Apparence" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-[hsl(var(--text-main))]">
                    Apparence & Thème
                </h2>
                <AutoSaveIndicator :isSaving="isSaving" :lastSaved="lastSaved" />
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Settings Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="liquid-glass p-8">
                            <form @submit.prevent="submit" class="space-y-8">
                                
                                <!-- Mode -->
                                <div>
                                    <h3 class="glass-label text-lg mb-4">Mode d'affichage</h3>
                                    <div class="grid grid-cols-3 gap-3">
                                        <button type="button"
                                            @click="form.mode = 'light'"
                                            class="group relative p-5 rounded-xl backdrop-blur-xl border-2 flex flex-col items-center gap-3 transition-all duration-300 hover:scale-105 overflow-hidden"
                                            :class="form.mode === 'light'
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-amber-50 to-orange-50 shadow-lg shadow-orange-500/20'
                                                : 'border-[hsla(var(--border),0.3)] bg-gradient-to-br from-white to-gray-50 hover:border-[hsla(var(--border),0.5)]'">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-bold text-sm" :class="form.mode === 'light' ? 'text-orange-900' : 'text-gray-700'">Clair</span>
                                            <div v-if="form.mode === 'light'" class="absolute top-2 right-2 w-5 h-5 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>

                                        <button type="button"
                                            @click="form.mode = 'dark'"
                                            class="group relative p-5 rounded-xl backdrop-blur-xl border-2 flex flex-col items-center gap-3 transition-all duration-300 hover:scale-105 overflow-hidden"
                                            :class="form.mode === 'dark'
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-slate-800 to-slate-900 shadow-lg shadow-blue-500/20'
                                                : 'border-[hsla(var(--border),0.3)] bg-gradient-to-br from-slate-700 to-slate-800 hover:border-[hsla(var(--border),0.5)]'">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                                </svg>
                                            </div>
                                            <span class="font-bold text-sm text-white">Sombre</span>
                                            <div v-if="form.mode === 'dark'" class="absolute top-2 right-2 w-5 h-5 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>

                                        <button type="button"
                                            @click="form.mode = 'system'"
                                            class="group relative p-5 rounded-xl backdrop-blur-xl border-2 flex flex-col items-center gap-3 transition-all duration-300 hover:scale-105 overflow-hidden"
                                            :class="form.mode === 'system'
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-cyan-50 to-blue-50 shadow-lg shadow-cyan-500/20'
                                                : 'border-[hsla(var(--border),0.3)] bg-gradient-to-br from-gray-50 to-slate-100 hover:border-[hsla(var(--border),0.5)]'">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center shadow-lg">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                                </svg>
                                            </div>
                                            <span class="font-bold text-sm" :class="form.mode === 'system' ? 'text-blue-900' : 'text-gray-700'">Système</span>
                                            <div v-if="form.mode === 'system'" class="absolute top-2 right-2 w-5 h-5 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Primary Color -->
                                <div>
                                    <h3 class="glass-label text-lg mb-4">Couleur Principale</h3>
                                    <div class="flex flex-wrap gap-3 mb-5">
                                        <button
                                            v-for="preset in presets"
                                            :key="preset.color"
                                            type="button"
                                            @click="form.primary_color = preset.color"
                                            class="group relative w-12 h-12 rounded-xl transition-all duration-300 hover:scale-110 focus:outline-none"
                                            :class="form.primary_color === preset.color ? 'ring-2 ring-offset-2 ring-[hsl(var(--primary))] scale-105 shadow-lg' : 'hover:shadow-md'"
                                            :title="preset.name"
                                        >
                                            <div
                                                class="absolute inset-0 rounded-xl bg-gradient-to-br transition-opacity"
                                                :class="preset.gradient"
                                            ></div>
                                            <div v-if="form.primary_color === preset.color" class="absolute inset-0 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-[10px] text-[hsl(var(--text-muted))] opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                {{ preset.name }}
                                            </span>
                                        </button>

                                        <!-- Custom Color Input Wrapper -->
                                        <div class="relative group">
                                            <input
                                                type="color"
                                                v-model="form.primary_color"
                                                class="w-12 h-12 rounded-xl overflow-hidden cursor-pointer border-0 p-0 absolute opacity-0"
                                            />
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-sm cursor-pointer group-hover:scale-110 transition-all duration-300 border-2 border-[hsla(var(--border),0.5)]"
                                                 :style="{ background: `conic-gradient(from 0deg, #ff0000, #ffff00, #00ff00, #00ffff, #0000ff, #ff00ff, #ff0000)` }">
                                                <div class="w-9 h-9 rounded-lg bg-[hsl(var(--bg-surface))] flex items-center justify-center">
                                                    <span class="text-lg font-bold text-[hsl(var(--text-main))]">+</span>
                                                </div>
                                            </div>
                                            <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-[10px] text-[hsl(var(--text-muted))] opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                Personnalisé
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 mt-8 p-3 rounded-lg bg-[hsla(var(--glass-bg),0.5)] border border-[hsla(var(--border),0.3)]">
                                         <span class="text-sm font-medium text-[hsl(var(--text-muted))]">Code Hex:</span>
                                         <input type="text" v-model="form.primary_color" class="glass-input flex-1 max-w-[140px] py-2 px-3 text-sm font-mono font-bold tracking-wider uppercase" />
                                         <div
                                            class="w-8 h-8 rounded-lg shadow-sm border-2 border-white/50"
                                            :style="{ backgroundColor: form.primary_color }"
                                         ></div>
                                    </div>
                                </div>

                                <!-- Gray Shade -->
                                <div>
                                    <h3 class="glass-label text-lg mb-4">Palette de Gris</h3>
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                        <button
                                            v-for="gray in grays"
                                            :key="gray.value"
                                            type="button"
                                            @click="form.gray_shade = gray.value"
                                            class="group relative p-4 rounded-xl border-2 text-sm font-semibold text-center transition-all duration-300 overflow-hidden"
                                            :class="form.gray_shade === gray.value
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-[hsl(var(--primary))]/20 to-[hsl(var(--primary))]/10 shadow-lg scale-105'
                                                : 'border-[hsla(var(--border),0.3)] bg-[hsl(var(--bg-surface))] hover:border-[hsla(var(--border),0.6)] hover:scale-105'"
                                        >
                                            <div class="flex items-center justify-center gap-1 mb-2">
                                                <div :class="gray.class" class="w-3 h-3 rounded-full border border-white/50 shadow-sm"></div>
                                                <div :class="gray.class" class="w-4 h-4 rounded-full border border-white/50 shadow-sm opacity-75"></div>
                                                <div :class="gray.class" class="w-3 h-3 rounded-full border border-white/50 shadow-sm opacity-50"></div>
                                            </div>
                                            <div class="text-[hsl(var(--text-main))]">{{ gray.label.split(' ')[0] }}</div>
                                            <div class="text-[10px] text-[hsl(var(--text-muted))] mt-0.5">{{ gray.label.split(' ')[1] }}</div>
                                            <div v-if="form.gray_shade === gray.value" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center shadow-sm">
                                                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Card Border Style -->
                                <div>
                                    <h3 class="glass-label text-lg mb-4">Bordures des cartes</h3>
                                    <div class="grid grid-cols-3 gap-3">
                                        <button
                                            type="button"
                                            @click="form.card_border_style = 'subtle'"
                                            class="group relative p-5 rounded-xl border-2 text-sm font-semibold text-center transition-all duration-300 overflow-hidden"
                                            :class="form.card_border_style === 'subtle'
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-[hsl(var(--primary))]/20 to-[hsl(var(--primary))]/10 shadow-lg scale-105'
                                                : 'border-[hsla(var(--border),0.2)] bg-[hsl(var(--bg-surface))] hover:border-[hsla(var(--border),0.4)] hover:scale-105'"
                                        >
                                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg border border-[hsla(var(--border),0.2)] bg-[hsl(var(--bg-surface))]"></div>
                                            <div class="text-[hsl(var(--text-main))]">Subtile</div>
                                            <div v-if="form.card_border_style === 'subtle'" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center shadow-sm">
                                                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>

                                        <button
                                            type="button"
                                            @click="form.card_border_style = 'medium'"
                                            class="group relative p-5 rounded-xl border-2 text-sm font-semibold text-center transition-all duration-300 overflow-hidden"
                                            :class="form.card_border_style === 'medium'
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-[hsl(var(--primary))]/20 to-[hsl(var(--primary))]/10 shadow-lg scale-105'
                                                : 'border-[hsla(var(--border),0.3)] bg-[hsl(var(--bg-surface))] hover:border-[hsla(var(--border),0.5)] hover:scale-105'"
                                        >
                                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg border-2 border-[hsla(var(--border),0.4)] bg-[hsl(var(--bg-surface))]"></div>
                                            <div class="text-[hsl(var(--text-main))]">Moyenne</div>
                                            <div v-if="form.card_border_style === 'medium'" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center shadow-sm">
                                                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>

                                        <button
                                            type="button"
                                            @click="form.card_border_style = 'strong'"
                                            class="group relative p-5 rounded-xl border-2 text-sm font-semibold text-center transition-all duration-300 overflow-hidden"
                                            :class="form.card_border_style === 'strong'
                                                ? 'border-[hsl(var(--primary))] bg-gradient-to-br from-[hsl(var(--primary))]/20 to-[hsl(var(--primary))]/10 shadow-lg scale-105'
                                                : 'border-[hsla(var(--border),0.4)] bg-[hsl(var(--bg-surface))] hover:border-[hsla(var(--border),0.6)] hover:scale-105'"
                                        >
                                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg border-2 border-[hsla(var(--border),0.6)] bg-[hsl(var(--bg-surface))]"></div>
                                            <div class="text-[hsl(var(--text-main))]">Marquée</div>
                                            <div v-if="form.card_border_style === 'strong'" class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[hsl(var(--primary))] flex items-center justify-center shadow-sm">
                                                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Radius -->
                                <div>
                                    <h3 class="glass-label text-lg mb-4">Arrondi des angles</h3>
                                    <div class="flex gap-6 items-center">
                                        <div class="flex-1">
                                            <div class="flex justify-between mb-3 text-xs font-bold text-[hsl(var(--text-muted))] uppercase tracking-wider">
                                                <span>Carré</span>
                                                <span class="text-center">Modéré</span>
                                                <span>Rond</span>
                                            </div>
                                            <div class="relative">
                                                <input
                                                    type="range"
                                                    min="0"
                                                    max="5"
                                                    step="1"
                                                    :value="radii.findIndex(r => r.value === form.radius)"
                                                    @input="form.radius = radii[$event.target.value].value"
                                                    class="w-full h-2 rounded-full appearance-none cursor-pointer bg-gray-200 dark:bg-gray-700
                                                           [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:rounded-full
                                                           [&::-webkit-slider-thumb]:bg-[hsl(var(--primary))]
                                                           [&::-webkit-slider-thumb]:shadow-lg [&::-webkit-slider-thumb]:shadow-[hsl(var(--primary))]/40 [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white dark:[&::-webkit-slider-thumb]:border-gray-800
                                                           [&::-webkit-slider-thumb]:transition-all [&::-webkit-slider-thumb]:hover:scale-110
                                                           [&::-moz-range-thumb]:w-5 [&::-moz-range-thumb]:h-5 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white dark:[&::-moz-range-thumb]:border-gray-800
                                                           [&::-moz-range-thumb]:bg-[hsl(var(--primary))]
                                                           [&::-moz-range-thumb]:shadow-lg [&::-moz-range-thumb]:shadow-[hsl(var(--primary))]/40 [&::-moz-range-thumb]:transition-all [&::-moz-range-thumb]:hover:scale-110"
                                                />
                                            </div>
                                            <div class="flex justify-between mt-2 px-1">
                                                <button
                                                    v-for="(r, index) in radii"
                                                    :key="r.value"
                                                    type="button"
                                                    @click="form.radius = r.value"
                                                    class="w-1.5 h-1.5 rounded-full transition-all"
                                                    :class="form.radius === r.value ? 'bg-[hsl(var(--primary))] scale-150' : 'bg-[hsla(var(--border),0.5)] hover:bg-[hsla(var(--border),0.8)]'"
                                                ></button>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="w-20 h-20 border-4 border-[hsl(var(--primary))] flex items-center justify-center bg-gradient-to-br from-[hsl(var(--primary))] to-[hsl(var(--primary-glow))] text-white font-bold text-xl shadow-xl transition-all duration-500"
                                                 :style="{ borderRadius: radii.find(r => r.value === form.radius)?.label === 'Full' ? '9999px' : (radii.find(r => r.value === form.radius)?.label.includes('px') ? radii.find(r => r.value === form.radius)?.label : '0.5rem') }">
                                                Aa
                                            </div>
                                            <span class="text-xs font-bold text-[hsl(var(--text-muted))]">{{ radii.find(r => r.value === form.radius)?.label }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-6 border-t border-[hsla(var(--border),0.3)]">
                                    <div class="text-xs text-[hsl(var(--text-muted))]">
                                        Les modifications sont enregistrées automatiquement
                                    </div>
                                    <PrimaryButton @click="submit" :disabled="form.processing || isSaving">
                                        <span v-if="form.processing || isSaving" class="loading loading-spinner loading-xs mr-2"></span>
                                        Enregistrer maintenant
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Live Preview Panel -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-8 space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-[hsl(var(--text-muted))]">Aperçu en direct</h3>
                                <div class="w-2 h-2 rounded-full bg-green-500 shadow-lg shadow-green-500/50 animate-pulse"></div>
                            </div>

                            <!-- Card Preview -->
                            <div class="liquid-glass p-6 space-y-5">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[hsl(var(--primary))] to-[hsl(var(--primary-glow))] flex items-center justify-center text-white shadow-lg shadow-[hsl(var(--primary))]/30">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                            </svg>
                                        </div>
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-green-500 border-2 border-[hsl(var(--bg-surface))]"></div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[hsl(var(--text-main))]">Marie Dubois</h4>
                                        <p class="text-xs text-[hsl(var(--text-muted))]">Administrateur</p>
                                    </div>
                                </div>
                                <div class="space-y-2.5">
                                    <label class="glass-label text-xs">Nom d'utilisateur</label>
                                    <input type="text" value="marie.dubois" class="glass-input text-sm" readonly />
                                </div>
                                <div class="space-y-2.5">
                                    <label class="glass-label text-xs">Statut</label>
                                    <select class="glass-select text-sm" disabled>
                                        <option>Actif</option>
                                    </select>
                                </div>
                                <div class="flex gap-2.5 pt-2">
                                    <button class="btn btn-primary flex-1 text-sm py-2">Sauvegarder</button>
                                    <button class="btn btn-secondary flex-1 text-sm py-2">Annuler</button>
                                </div>
                            </div>

                            <!-- Notification Preview -->
                            <div class="relative overflow-hidden">
                                <div class="liquid-glass p-4 border-l-[3px] border-[hsl(var(--primary))] flex gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[hsl(var(--primary))]/20 to-[hsl(var(--primary))]/10 flex items-center justify-center flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-[hsl(var(--primary))]">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-bold text-sm text-[hsl(var(--text-main))] mb-1">Thème personnalisé</p>
                                        <p class="text-xs text-[hsl(var(--text-muted))] leading-relaxed">Votre thème utilise la couleur principale sélectionnée pour créer une identité visuelle cohérente.</p>
                                    </div>
                                </div>
                                <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-[hsl(var(--primary))]/50 to-transparent"></div>
                            </div>

                            <!-- Badge Showcase -->
                            <div class="liquid-glass p-4 space-y-3">
                                <p class="text-xs font-bold text-[hsl(var(--text-muted))] uppercase tracking-wider text-center">Éléments</p>
                                <div class="flex flex-wrap gap-2 justify-center items-center">
                                    <span class="px-3 py-1.5 rounded-full bg-gradient-to-r from-[hsl(var(--primary))]/20 to-[hsl(var(--primary))]/10 text-[hsl(var(--primary))] text-xs font-bold border border-[hsl(var(--primary))]/20">
                                        Badge
                                    </span>
                                    <span class="px-3 py-1.5 rounded-full bg-[hsla(var(--bg-surface),0.5)] text-[hsl(var(--text-muted))] text-xs font-semibold border border-[hsla(var(--border),0.3)]">
                                        Tag
                                    </span>
                                    <button class="btn btn-ghost px-3 py-1 text-xs">Action</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
