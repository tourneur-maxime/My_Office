<script setup>
import axios from 'axios';
import { debounce } from 'lodash'; // You might need to install lodash: npm install lodash
import { defineEmits, defineProps, ref, watch } from 'vue';

const props = defineProps({
    modelValue: String,
    searchRoute: String,
    placeholder: {
        type: String,
        default: 'Rechercher...',
    },
    debounceTime: {
        type: Number,
        default: 300,
    },
});

const emit = defineEmits(['update:modelValue', 'selected']);

const searchQuery = ref(props.modelValue);
const suggestions = ref([]);
const showSuggestions = ref(false);
const highlightIndex = ref(-1);

const fetchSuggestions = debounce(async (query) => {
    if (!query || query.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    try {
        const response = await axios.get(props.searchRoute, {
            params: { search: query },
        });
        suggestions.value = response.data;
        showSuggestions.value = true;
        highlightIndex.value = -1; // Reset highlight on new suggestions
    } catch (error) {
        console.error('Error fetching suggestions:', error);
        suggestions.value = [];
        showSuggestions.value = false;
    }
}, props.debounceTime);

watch(searchQuery, (newValue) => {
    emit('update:modelValue', newValue);
    fetchSuggestions(newValue);
});

const selectSuggestion = (suggestion) => {
    searchQuery.value = suggestion.name; // Or whatever field you want to display
    emit('selected', suggestion);
    suggestions.value = [];
    showSuggestions.value = false;
};

const handleKeyDown = (event) => {
    if (showSuggestions.value && suggestions.value.length > 0) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            highlightIndex.value =
                (highlightIndex.value + 1) % suggestions.value.length;
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlightIndex.value =
                (highlightIndex.value - 1 + suggestions.value.length) %
                suggestions.value.length;
        } else if (event.key === 'Enter' && highlightIndex.value !== -1) {
            event.preventDefault();
            selectSuggestion(suggestions.value[highlightIndex.value]);
        }
    }
};

const handleBlur = () => {
    // Delay hiding suggestions to allow click event on suggestion
    setTimeout(() => {
        showSuggestions.value = false;
    }, 100);
};
</script>

<template>
    <div class="relative">
        <input
            type="text"
            v-model="searchQuery"
            @keydown="handleKeyDown"
            @focus="showSuggestions = true"
            @blur="handleBlur"
            :placeholder="placeholder"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
        <ul
            v-if="showSuggestions && suggestions.length"
            class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg"
        >
            <li
                v-for="(suggestion, index) in suggestions"
                :key="suggestion.id"
                @mousedown.prevent="selectSuggestion(suggestion)"
                :class="{
                    'cursor-pointer px-4 py-2 hover:bg-indigo-50': true,
                    'bg-indigo-100': index === highlightIndex,
                }"
            >
                {{ suggestion.name }} ({{
                    suggestion.company || suggestion.email
                }})
            </li>
        </ul>
        <div
            v-else-if="
                showSuggestions &&
                searchQuery.length >= 2 &&
                !suggestions.length
            "
            class="absolute z-10 mt-1 w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-500 shadow-lg"
        >
            Aucun résultat.
        </div>
    </div>
</template>
