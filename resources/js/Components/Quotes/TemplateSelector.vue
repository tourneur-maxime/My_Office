<script setup>
import { useQuoteStore } from '@/Stores/quoteStore';
import { storeToRefs } from 'pinia';

const store = useQuoteStore();
const { templates, template_id } = storeToRefs(store);

const selectTemplate = (id) => {
    store.setTemplateId(id);
};
</script>

<template>
    <div class="space-y-4">
        <label class="block text-sm font-medium text-gray-700"
            >Choisir un modèle</label
        >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div
                v-for="template in templates"
                :key="template.id"
                @click="selectTemplate(template.id)"
                class="card-compact card cursor-pointer border-2 bg-base-100 shadow-sm transition-all"
                :class="
                    template_id === template.id
                        ? 'border-primary'
                        : 'border-transparent hover:border-gray-300'
                "
            >
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <h3 class="card-title text-sm">{{ template.name }}</h3>
                        <div
                            v-if="template.is_default"
                            class="badge badge-ghost badge-sm text-xs"
                        >
                            Par défaut
                        </div>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <div
                            class="h-6 w-6 rounded-full border border-gray-200"
                            :style="{ backgroundColor: template.primary_color }"
                            title="Couleur primaire"
                        ></div>
                        <div
                            class="h-6 w-6 rounded-full border border-gray-200"
                            :style="{
                                backgroundColor: template.secondary_color,
                            }"
                            title="Couleur secondaire"
                        ></div>
                        <span class="flex items-center text-xs text-gray-500">{{
                            template.font
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
