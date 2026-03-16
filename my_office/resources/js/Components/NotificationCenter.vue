<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const showNotifications = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

const loadNotifications = async () => {
    loading.value = true;
    try {
        const response = await fetch(route('notifications.index'), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (error) {
        console.error('Erreur lors du chargement des notifications:', error);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (notificationId) => {
    try {
        await fetch(route('notifications.markAsRead', notificationId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        await loadNotifications();
    } catch (error) {
        console.error('Erreur lors du marquage de la notification:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await fetch(route('notifications.markAllAsRead'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        await loadNotifications();
    } catch (error) {
        console.error('Erreur lors du marquage de toutes les notifications:', error);
    }
};

const deleteNotification = async (notificationId) => {
    try {
        await fetch(route('notifications.destroy', notificationId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        await loadNotifications();
    } catch (error) {
        console.error('Erreur lors de la suppression de la notification:', error);
    }
};

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) {
        loadNotifications();
    }
};

const getNotificationIcon = (notification) => {
    const iconType = notification.data?.icon || 'bell';
    const icons = {
        'document': '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
        'document-text': '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
        'cash': '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'bell': '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
    };
    return icons[iconType] || icons['bell'];
};

const getNotificationColor = (notification) => {
    const color = notification.data?.color || 'blue';
    const colors = {
        'green': 'bg-gradient-to-br from-green-500/20 to-emerald-500/20 text-green-600 dark:text-green-400',
        'blue': 'bg-gradient-to-br from-blue-500/20 to-cyan-500/20 text-blue-600 dark:text-blue-400',
        'red': 'bg-gradient-to-br from-red-500/20 to-rose-500/20 text-red-600 dark:text-red-400',
        'orange': 'bg-gradient-to-br from-orange-500/20 to-amber-500/20 text-orange-600 dark:text-orange-400',
    };
    return colors[color] || colors['blue'];
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'À l\'instant';
    if (minutes < 60) return `Il y a ${minutes} min`;
    if (hours < 24) return `Il y a ${hours}h`;
    if (days < 7) return `Il y a ${days}j`;

    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    });
};

const handleNotificationClick = (notification) => {
    if (!notification.read_at) {
        markAsRead(notification.id);
    }

    // Navigate based on notification type
    const data = notification.data;
    if (data.type === 'pdf_generated') {
        if (data.document_type === 'devis') {
            router.visit(route('quotes.show', data.document_id));
        } else if (data.document_type === 'facture') {
            router.visit(route('invoices.show', data.document_id));
        }
    } else if (data.type === 'quote_status_changed') {
        router.visit(route('quotes.show', data.quote_id));
    } else if (data.type === 'invoice_paid') {
        router.visit(route('invoices.show', data.invoice_id));
    }

    showNotifications.value = false;
};

onMounted(() => {
    loadNotifications();
    // Poll every 30 seconds
    setInterval(loadNotifications, 30000);
});
</script>

<template>
    <div class="relative">
        <div @click="toggleNotifications">
            <button
                class="relative p-2 rounded-[var(--radius)] text-[hsl(var(--text-muted))] hover:text-[hsl(var(--text-main))] hover:bg-[hsla(var(--text-main),0.05)] transition-all duration-200"
                title="Notifications"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </button>
        </div>

        <!-- Full Screen Dropdown Overlay (teleported to escape nav stacking context) -->
        <Teleport to="body">
            <div
                v-show="showNotifications"
                class="fixed inset-0 z-[60]"
                @click="showNotifications = false"
            ></div>
        </Teleport>

        <!-- Notifications Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="showNotifications"
                class="absolute right-0 mt-2 w-96 liquid-glass shadow-xl z-[70] max-h-[32rem] flex flex-col"
            >
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-[hsl(var(--border))]">
                    <h3 class="font-semibold text-[hsl(var(--text-main))]">
                        Notifications
                    </h3>
                    <button
                        v-if="unreadCount > 0"
                        @click.stop="markAllAsRead"
                        class="text-xs text-[hsl(var(--primary))] hover:underline"
                    >
                        Tout marquer comme lu
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="overflow-y-auto flex-1">
                    <div v-if="loading" class="p-8 text-center text-[hsl(var(--text-muted))]">
                        <svg class="animate-spin h-8 w-8 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-else-if="notifications.length === 0" class="p-8 text-center text-[hsl(var(--text-muted))]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="text-sm">Aucune notification</p>
                    </div>

                    <div v-else>
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            @click="handleNotificationClick(notification)"
                            :class="[
                                'p-4 border-b border-[hsl(var(--border))] transition-colors cursor-pointer relative',
                                notification.read_at ? 'hover:bg-[hsl(var(--primary))]/5' : 'bg-[hsl(var(--primary))]/10 hover:bg-[hsl(var(--primary))]/15'
                            ]"
                        >
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" :class="getNotificationColor(notification)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" v-html="getNotificationIcon(notification)">
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-[hsl(var(--text-main))] mb-1">
                                        {{ notification.data.message }}
                                    </p>
                                    <p class="text-xs text-[hsl(var(--text-muted))]">
                                        {{ formatDate(notification.created_at) }}
                                    </p>
                                </div>
                                <button
                                    @click.stop="deleteNotification(notification.id)"
                                    class="flex-shrink-0 p-1 rounded hover:bg-red-500/10 text-[hsl(var(--text-muted))] hover:text-red-500 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div
                                v-if="!notification.read_at"
                                class="absolute top-4 right-4 w-2 h-2 rounded-full bg-[hsl(var(--primary))]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
