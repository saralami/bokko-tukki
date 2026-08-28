<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bus, Home, ScanLine, User } from '@lucide/vue';
import { computed } from 'vue';
import { Toaster } from '@/components/ui/sonner';

defineProps<{ breadcrumbs?: unknown[] }>();

const page = usePage();

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (path: string): boolean =>
    path === '/driver/dashboard'
        ? currentPath.value === path
        : currentPath.value.startsWith(path);

const tabs = [
    { label: 'Accueil', href: '/driver/dashboard', icon: Home },
    { label: 'Trajets', href: '/driver/trips', icon: Bus },
    { label: 'Embarquement', href: '/driver/boarding', icon: ScanLine },
    { label: 'Profil', href: '/driver/profile', icon: User },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="sticky top-0 z-20 border-b border-sidebar-border/70 bg-background/95 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto flex w-full max-w-xl items-center justify-between px-4 py-3">
                <Link href="/driver/dashboard" class="flex items-center gap-2 font-semibold">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground">
                        AD
                    </span>
                    <span>Chauffeur</span>
                </Link>
                <Link
                    href="/driver/boarding"
                    class="flex items-center gap-1.5 rounded-full bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground"
                >
                    <ScanLine class="h-4 w-4" /> Scanner
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-xl flex-1 px-4 pb-24 pt-4">
            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-sidebar-border/70 bg-background/95 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto flex w-full max-w-xl items-stretch justify-around">
                <Link
                    v-for="tab in tabs"
                    :key="tab.href"
                    :href="tab.href"
                    class="flex flex-1 flex-col items-center gap-1 py-2 text-[11px] font-medium transition-colors"
                    :class="isActive(tab.href) ? 'text-primary' : 'text-muted-foreground hover:text-foreground'"
                >
                    <component :is="tab.icon" class="h-5 w-5" />
                    <span>{{ tab.label }}</span>
                </Link>
            </div>
        </nav>

        <Toaster />
    </div>
</template>
