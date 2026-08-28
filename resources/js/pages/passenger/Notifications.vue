<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BellOff, CheckCheck } from '@lucide/vue';
import { computed } from 'vue';
import NotificationController from '@/actions/App/Http/Controllers/Passenger/NotificationController';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/format';

type NotificationItem = {
    id: string;
    title: string;
    body: string;
    read: boolean;
    created_at: string | null;
};

const props = defineProps<{ notifications: NotificationItem[] }>();

defineOptions({ layout: { breadcrumbs: [] } });

const hasUnread = computed(() => props.notifications.some((n) => !n.read));

const markAsRead = (notification: NotificationItem) => {
    if (notification.read) {
        return;
    }

    router.patch(NotificationController.markAsRead.url(notification.id), {}, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.patch(NotificationController.markAllAsRead.url(), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Notifications" />

    <div class="flex flex-col gap-5">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Notifications</h1>
            <Button v-if="hasUnread" variant="ghost" size="sm" @click="markAllAsRead">
                <CheckCheck class="h-4 w-4" /> Tout marquer lu
            </Button>
        </div>

        <div
            v-if="notifications.length === 0"
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-sidebar-border/70 p-10 text-center dark:border-sidebar-border"
        >
            <BellOff class="h-10 w-10 text-muted-foreground" />
            <p class="text-muted-foreground">Vous n'avez aucune notification.</p>
        </div>

        <button
            v-for="notification in notifications"
            :key="notification.id"
            type="button"
            class="flex items-start gap-3 rounded-xl border p-4 text-left transition-colors"
            :class="notification.read ? 'border-sidebar-border/70 dark:border-sidebar-border' : 'border-primary/40 bg-primary/5'"
            @click="markAsRead(notification)"
        >
            <span
                class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                :class="notification.read ? 'bg-transparent' : 'bg-primary'"
            />
            <div class="flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-medium">{{ notification.title }}</p>
                    <span class="whitespace-nowrap text-xs text-muted-foreground">{{ formatDateTime(notification.created_at) }}</span>
                </div>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ notification.body }}</p>
            </div>
        </button>
    </div>
</template>
