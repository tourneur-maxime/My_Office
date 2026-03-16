<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    kpiPreferences: Array,
    availableKpis: Array,
});

const emit = defineEmits(['close']);

// Local state for preferences
const localPreferences = ref([...props.kpiPreferences]);

// Initialize preferences if empty
if (localPreferences.value.length === 0 && props.availableKpis.length > 0) {
    localPreferences.value = props.availableKpis.map(kpi => ({
        id: kpi.id,
        visible: true,
    }));
}

const toggleKpi = (kpiId) => {
    const pref = localPreferences.value.find(p => p.id === kpiId);
    if (pref) {
        pref.visible = !pref.visible;
    } else {
        localPreferences.value.push({ id: kpiId, visible: true });
    }
};

const isKpiVisible = (kpiId) => {
    const pref = localPreferences.value.find(p => p.id === kpiId);
    return pref ? pref.visible : true;
};

const savePreferences = () => {
    router.patch(route('dashboard.updateKpiPreferences'), {
        preferences: localPreferences.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        class="liquid-glass max-w-md w-full mx-4 p-6"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-[hsl(var(--text-main))]">
                                Configurer les KPIs
                            </h3>
                            <button
                                @click="emit('close')"
                                class="p-2 rounded-lg hover:bg-[hsl(var(--primary))]/10 transition-colors text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))]"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-3 mb-6">
                            <p class="text-sm text-[hsl(var(--text-muted))] mb-4">
                                Choisissez les KPIs à afficher sur votre tableau de bord
                            </p>
                            <div
                                v-for="kpi in availableKpis"
                                :key="kpi.id"
                                class="flex items-center justify-between p-3 rounded-lg border border-[hsl(var(--border))] hover:bg-[hsl(var(--primary))]/5 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-10 h-10 rounded-lg flex items-center justify-center',
                                            kpi.color
                                        ]"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" v-html="kpi.icon">
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-[hsl(var(--text-main))]">
                                            {{ kpi.label }}
                                        </div>
                                        <div class="text-xs text-[hsl(var(--text-muted))]">
                                            {{ kpi.description }}
                                        </div>
                                    </div>
                                </div>
                                <button
                                    @click="toggleKpi(kpi.id)"
                                    :class="[
                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                        isKpiVisible(kpi.id) ? 'bg-[hsl(var(--primary))]' : 'bg-[hsl(var(--border))]'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                            isKpiVisible(kpi.id) ? 'translate-x-6' : 'translate-x-1'
                                        ]"
                                    />
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="emit('close')"
                                class="flex-1 btn btn-outline"
                            >
                                Annuler
                            </button>
                            <button
                                @click="savePreferences"
                                class="flex-1 btn btn-primary"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
