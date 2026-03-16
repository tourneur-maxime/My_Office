<template>
    <div class="mb-6">
        <!-- Search Bar -->
        <div class="mb-4 flex gap-2">
            <input
                v-model="searchTerm"
                type="text"
                id="search-input"
                placeholder="Rechercher par nom, entreprise ou SIRET..."
                aria-label="Rechercher des clients et prospects"
                aria-describedby="search-description"
                :aria-expanded="showSuggestions && suggestions.length > 0"
                :aria-activedescendant="
                    selectedSuggestionIndex >= 0
                        ? `suggestion-${selectedSuggestionIndex}`
                        : undefined
                "
                role="combobox"
                aria-autocomplete="list"
                aria-controls="suggestions-list"
                class="glass-input flex-1"
                @input="handleSearch"
                @keydown.enter="handleEnterKey"
                @keydown.down="selectNextSuggestion"
                @keydown.up="selectPreviousSuggestion"
                @keydown.escape="closeSuggestions"
            />
            <span id="search-description" class="sr-only"
                >Tapez au moins 2 caractères pour voir les suggestions</span
            >
            <button
                v-if="searchTerm"
                @click="clearSearch"
                class="btn btn-ghost btn-sm"
            >
                Effacer
            </button>
        </div>

        <!-- Type Filter -->
        <div class="mb-4 flex gap-2">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="selectedFilter = filter.value"
                :class="[
                    'btn btn-sm',
                    selectedFilter === filter.value
                        ? 'btn-primary'
                        : 'btn-secondary',
                ]"
            >
                {{ filter.label }}
            </button>
        </div>

        <!-- Autocomplete Suggestions Dropdown -->
        <div
            v-if="showSuggestions && suggestions.length > 0"
            id="suggestions-list"
            role="listbox"
            aria-label="Suggestions de recherche"
            class="mb-4 max-h-64 overflow-y-auto rounded-lg border border-[hsl(var(--border))] bg-[hsl(var(--bg-surface))] shadow-lg"
        >
            <div
                v-for="(suggestion, index) in suggestions"
                :key="`${suggestion.id}-${index}`"
                :id="`suggestion-${index}`"
                :dusk="`suggestion-${index}`"
                role="option"
                :aria-selected="index === selectedSuggestionIndex"
                @click="selectSuggestion(suggestion)"
                :class="[
                    'suggestion-item cursor-pointer px-4 py-2 transition',
                    index === selectedSuggestionIndex
                        ? 'bg-[hsl(var(--primary))]/10'
                        : 'hover:bg-[hsl(var(--bg-surface))]/80',
                ]"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-[hsl(var(--text-main))]">{{ suggestion.name }}</p>
                        <p class="text-sm text-[hsl(var(--text-muted))]">
                            {{ suggestion.company }}
                        </p>
                    </div>
                    <span
                        :class="[
                            'rounded px-2 py-1 text-xs font-medium',
                            suggestion.status === 'client'
                                ? 'bg-blue-100 text-blue-800'
                                : 'bg-amber-100 text-amber-800',
                        ]"
                    >
                        {{
                            suggestion.status === 'client'
                                ? 'Client'
                                : 'Prospect'
                        }}
                    </span>
                </div>
            </div>
        </div>

        <!-- No Results Message -->
        <div v-if="showNoResults" class="py-4 text-center text-[hsl(var(--text-muted))]">
            Aucun résultat trouvé pour "{{ searchTerm }}"
        </div>

        <!-- Loading Indicator -->
        <div v-if="isLoading" class="flex justify-center py-2">
            <div class="animate-spin">
                <svg
                    class="h-5 w-5 text-[hsl(var(--primary))]"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    ></path>
                </svg>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    initialSearch: {
        type: String,
        default: '',
    },
    initialFilter: {
        type: String,
        default: 'all',
    },
    mode: {
        type: String,
        default: 'search', // 'search' (default) emits search event on type, 'select' only shows suggestions
    },
});

const emit = defineEmits(['search', 'filter-change']);

const searchTerm = ref(props.initialSearch);
const selectedFilter = ref(props.initialFilter);
const suggestions = ref([]);
const isLoading = ref(false);
const showSuggestions = ref(false);
const selectedSuggestionIndex = ref(-1);

const filters = [
    { label: 'Tous', value: 'all' },
    { label: 'Clients uniquement', value: 'client' },
    { label: 'Prospects uniquement', value: 'prospect' },
];

const showNoResults = computed(() => {
    return (
        searchTerm.value &&
        suggestions.value.length === 0 &&
        !isLoading.value &&
        showSuggestions.value
    );
});

// Debounced search
let debounceTimer = null;

const handleSearch = () => {
    clearTimeout(debounceTimer);

    if (searchTerm.value.length >= 2) {
        debounceTimer = setTimeout(() => {
            performSearch();
        }, 300);
    } else if (searchTerm.value.length === 0) {
        suggestions.value = [];
        showSuggestions.value = false;
        emit('search', { q: '', type: selectedFilter.value });
    } else {
        suggestions.value = [];
        showSuggestions.value = false;
    }
};

const performSearch = async () => {
    // Enforce minimum 2 characters
    if (searchTerm.value.length > 0 && searchTerm.value.length < 2) {
        return;
    }

    isLoading.value = true;
    showSuggestions.value = true;
    selectedSuggestionIndex.value = -1;

    try {
        const type = selectedFilter.value === 'all' ? '' : selectedFilter.value;
        const response = await fetch(
            `/clients/search?q=${encodeURIComponent(searchTerm.value)}&type=${type}&limit=10`,
        );
        const data = await response.json();
        console.log('SearchClients response:', data); // DEBUG

        if (data.success) {
            suggestions.value = data.data.items;
            if (props.mode === 'search') {
                emit('search', {
                    q: searchTerm.value,
                    type: selectedFilter.value,
                    results: suggestions.value,
                });
            }
        }
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        isLoading.value = false;
    }
};

const selectSuggestion = (suggestion) => {
    searchTerm.value = suggestion.name;
    suggestions.value = [];
    showSuggestions.value = false;
    emit('search', {
        q: suggestion.name,
        type: selectedFilter.value,
        results: [suggestion],
    });
};

const clearSearch = () => {
    searchTerm.value = '';
    suggestions.value = [];
    showSuggestions.value = false;
    emit('search', { q: '', type: selectedFilter.value });
};

const closeSuggestions = () => {
    showSuggestions.value = false;
};

const selectNextSuggestion = (event) => {
    event.preventDefault();
    if (suggestions.value.length > 0) {
        selectedSuggestionIndex.value = Math.min(
            selectedSuggestionIndex.value + 1,
            suggestions.value.length - 1,
        );
    }
};

const selectPreviousSuggestion = (event) => {
    event.preventDefault();
    if (suggestions.value.length > 0) {
        selectedSuggestionIndex.value = Math.max(
            selectedSuggestionIndex.value - 1,
            -1,
        );
    }
};

const handleEnterKey = (event) => {
    event.preventDefault();

    if (
        showSuggestions.value &&
        selectedSuggestionIndex.value >= 0 &&
        suggestions.value[selectedSuggestionIndex.value]
    ) {
        // Select the highlighted suggestion
        selectSuggestion(suggestions.value[selectedSuggestionIndex.value]);
    } else {
        // Just perform search with current term
        performSearch();
    }
};

watch(selectedFilter, (newValue) => {
    emit('filter-change', newValue);
    // Always perform search to update URL even if searchTerm is empty
    performSearch();
});
</script>
