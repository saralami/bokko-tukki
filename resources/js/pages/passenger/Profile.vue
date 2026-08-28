<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, LogOut, Palette, ShieldCheck, UserCog } from '@lucide/vue';
import { computed } from 'vue';
import { logout } from '@/routes';
import { edit as appearanceEdit } from '@/routes/appearance';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';

defineOptions({ layout: { breadcrumbs: [] } });

const page = usePage();
const user = computed(() => page.props.auth.user);

const initials = computed(() =>
    (user.value?.name ?? '')
        .split(' ')
        .map((part: string) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const links = [
    { label: 'Modifier mon profil', description: 'Nom, e-mail, téléphone', icon: UserCog, href: profileEdit().url },
    { label: 'Sécurité', description: 'Mot de passe, double authentification', icon: ShieldCheck, href: securityEdit().url },
    { label: 'Apparence', description: 'Thème clair ou sombre', icon: Palette, href: appearanceEdit().url },
];

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <Head title="Profil" />

    <div class="flex flex-col gap-5">
        <h1 class="text-xl font-bold">Profil</h1>

        <div class="flex items-center gap-4 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground">
                {{ initials || 'AD' }}
            </span>
            <div class="min-w-0">
                <p class="truncate text-lg font-semibold">{{ user?.name }}</p>
                <p class="truncate text-sm text-muted-foreground">{{ user?.email }}</p>
                <p v-if="user?.phone" class="truncate text-sm text-muted-foreground">{{ user.phone }}</p>
            </div>
        </div>

        <div class="flex flex-col divide-y divide-sidebar-border/70 rounded-2xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                class="flex items-center gap-3 px-4 py-3.5 transition-colors hover:bg-muted/50"
            >
                <component :is="link.icon" class="h-5 w-5 shrink-0 text-muted-foreground" />
                <div class="flex-1">
                    <p class="font-medium">{{ link.label }}</p>
                    <p class="text-sm text-muted-foreground">{{ link.description }}</p>
                </div>
                <ChevronRight class="h-5 w-5 text-muted-foreground" />
            </Link>
        </div>

        <Link
            :href="logout()"
            as="button"
            class="flex items-center justify-center gap-2 rounded-2xl border border-destructive/40 p-4 font-medium text-destructive transition-colors hover:bg-destructive/5"
            @click="handleLogout"
        >
            <LogOut class="h-5 w-5" /> Se déconnecter
        </Link>
    </div>
</template>
