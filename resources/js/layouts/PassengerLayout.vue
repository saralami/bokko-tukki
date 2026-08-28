<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Home, Search, Ticket, User } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/BrandLogo.vue';
import { Toaster } from '@/components/ui/sonner';

// Breadcrumbs are passed by pages via defineOptions but are not used by the
// mobile shell; declared so Inertia layout props resolve without warnings.
defineProps<{ breadcrumbs?: unknown[] }>();

const page = usePage();

const unread = computed<number>(() => (page.props.unreadNotifications as number | undefined) ?? 0);

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (path: string): boolean =>
    path === '/passenger/dashboard'
        ? currentPath.value === path
        : currentPath.value.startsWith(path);

const tabs = [
    { label: 'Accueil', href: '/passenger/dashboard', icon: Home },
    { label: 'Recherche', href: '/passenger/search', icon: Search },
    { label: 'Réservations', href: '/passenger/bookings', icon: Ticket },
    { label: 'Notifications', href: '/passenger/notifications', icon: Bell },
    { label: 'Profil', href: '/passenger/profile', icon: User },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="sticky top-0 z-20 border-b border-sidebar-border/70 bg-background/95 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto flex w-full max-w-xl items-center justify-between px-4 py-3">
                <Link href="/passenger/dashboard" class="flex items-center gap-2 font-semibold">
                    <BrandLogo class="size-11" />
                    <span>Bokko Tuki</span>
                </Link>

                <Link
                    href="/passenger/notifications"
                    class="relative flex h-9 w-9 items-center justify-center rounded-full hover:bg-muted"
                    aria-label="Notifications"
                >
                    <Bell class="h-5 w-5" />
                    <span
                        v-if="unread > 0"
                        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-white"
                    >
                        {{ unread > 9 ? '9+' : unread }}
                    </span>
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-xl flex-1 px-4 pb-24 pt-4">
            <slot />
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-20 border-t border-sidebar-border/70 bg-background/95 backdrop-blur dark:border-sidebar-border"
        >
            <div class="mx-auto flex w-full max-w-xl items-stretch justify-around">
                <Link
                    v-for="tab in tabs"
                    :key="tab.href"
                    :href="tab.href"
                    class="relative flex flex-1 flex-col items-center gap-1 py-2 text-[11px] font-medium transition-colors"
                    :class="isActive(tab.href) ? 'text-primary' : 'text-muted-foreground hover:text-foreground'"
                >
                    <component :is="tab.icon" class="h-5 w-5" />
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.label === 'Notifications' && unread > 0"
                        class="absolute right-1/2 top-1 translate-x-3 rounded-full bg-destructive px-1 text-[9px] font-bold text-white"
                    >
                        {{ unread > 9 ? '9+' : unread }}
                    </span>
                </Link>
            </div>
        </nav>

        <Toaster />
    </div>
</template>
