<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, Banknote, Bus, CalendarCheck, Landmark, Ticket, TrendingUp, Truck, Users, Wallet } from '@lucide/vue';
import StatCard from '@/components/admin/StatCard.vue';
import Heading from '@/components/Heading.vue';
import { formatDateTime, formatFcfa } from '@/lib/format';

type Stats = {
    users: number;
    activeTransporters: number;
    activeDrivers: number;
    trips: number;
    bookings: number;
    financialVolume: number;
    commissions: number;
    transporterDebt: number;
    pendingWithdrawals: number;
    pendingWithdrawalsAmount: number;
};

type AuditEntry = { id: number; action: string; description: string | null; user: string | null; date: string | null };

defineProps<{ stats: Stats; recentAudit: AuditEntry[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }] } });

const sections = [
    { label: 'Utilisateurs', href: '/admin/users', icon: Users },
    { label: 'Transporteurs', href: '/admin/transporters', icon: Truck },
    { label: 'Chauffeurs', href: '/admin/drivers', icon: Users },
    { label: 'Véhicules', href: '/admin/vehicles', icon: Bus },
    { label: 'Destinations', href: '/admin/destinations', icon: Landmark },
    { label: 'Trajets', href: '/admin/trips', icon: Bus },
    { label: 'Réservations', href: '/admin/bookings', icon: Ticket },
    { label: 'Transactions', href: '/admin/finance/transactions', icon: Banknote },
    { label: 'Commissions', href: '/admin/finance/commissions', icon: TrendingUp },
    { label: 'Dettes', href: '/admin/finance/debts', icon: AlertTriangle },
    { label: 'Ledger', href: '/admin/finance/ledger', icon: Landmark },
    { label: 'Retraits', href: '/admin/withdrawals', icon: Wallet },
    { label: 'Paramètres', href: '/admin/settings', icon: CalendarCheck },
    { label: "Journal d'audit", href: '/admin/audit-logs', icon: AlertTriangle },
];
</script>

<template>
    <Head title="Administration" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Administration" description="Supervision de la plateforme Allo Dakar." />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <StatCard label="Utilisateurs" :value="stats.users" :icon="Users" />
            <StatCard label="Transporteurs actifs" :value="stats.activeTransporters" :icon="Truck" />
            <StatCard label="Chauffeurs actifs" :value="stats.activeDrivers" :icon="Users" />
            <StatCard label="Trajets" :value="stats.trips" :icon="Bus" />
            <StatCard label="Réservations" :value="stats.bookings" :icon="Ticket" />
            <StatCard label="Volume financier" :value="formatFcfa(stats.financialVolume)" :icon="Banknote" />
            <StatCard label="Commissions Allo Dakar" :value="formatFcfa(stats.commissions)" :icon="TrendingUp" />
            <StatCard label="Dettes transporteurs" :value="formatFcfa(stats.transporterDebt)" :icon="AlertTriangle" :tone="stats.transporterDebt > 0 ? 'warning' : 'default'" />
            <StatCard label="Retraits en attente" :value="stats.pendingWithdrawals" :hint="formatFcfa(stats.pendingWithdrawalsAmount)" :icon="Wallet" :tone="stats.pendingWithdrawals > 0 ? 'warning' : 'default'" />
        </div>

        <div>
            <h2 class="mb-3 text-base font-semibold">Gestion</h2>
            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <Link
                    v-for="section in sections"
                    :key="section.href"
                    :href="section.href"
                    class="flex items-center gap-3 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                >
                    <component :is="section.icon" class="h-5 w-5 text-muted-foreground" />
                    <span class="font-medium">{{ section.label }}</span>
                </Link>
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold">Activité récente</h2>
                <Link href="/admin/audit-logs" class="text-sm text-primary">Tout le journal</Link>
            </div>
            <div
                v-if="recentAudit.length === 0"
                class="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-muted-foreground dark:border-sidebar-border"
            >
                Aucune opération sensible enregistrée.
            </div>
            <div v-else class="flex flex-col divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border">
                <div v-for="entry in recentAudit" :key="entry.id" class="flex items-start justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium">{{ entry.description ?? entry.action }}</p>
                        <p class="text-xs text-muted-foreground">{{ entry.action }} · {{ entry.user ?? 'Système' }}</p>
                    </div>
                    <span class="whitespace-nowrap text-xs text-muted-foreground">{{ formatDateTime(entry.date) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
