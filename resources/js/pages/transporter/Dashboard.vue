<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookingController from '@/actions/App/Http/Controllers/Transporter/BookingController';
import DriverController from '@/actions/App/Http/Controllers/Transporter/DriverController';
import TransporterController from '@/actions/App/Http/Controllers/Transporter/TransporterController';
import TripController from '@/actions/App/Http/Controllers/Transporter/TripController';
import VehicleController from '@/actions/App/Http/Controllers/Transporter/VehicleController';
import WalletController from '@/actions/App/Http/Controllers/Transporter/WalletController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';

type Stats = {
    revenue: number;
    commissions: number;
    trips: number;
    reservations: number;
    seats_sold: number;
    seats_remaining: number;
    debt: number;
    available_balance: number;
    withdrawals: number;
};

type Payment = {
    id: number;
    reference: string;
    amount: number;
    commission: number;
    method: string;
    status: string;
    date: string | null;
};

const props = defineProps<{
    hasCompany: boolean;
    status: string | null;
    stats: Stats;
    recentPayments: Payment[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Espace transporteur', href: '/transporter/dashboard' }],
    },
});

const money = (value: number): string => `${value.toLocaleString('fr-FR')} FCFA`;

const kpis = [
    { label: "Chiffre d'affaires", value: money(props.stats.revenue) },
    { label: 'Commissions', value: money(props.stats.commissions) },
    { label: 'Solde disponible', value: money(props.stats.available_balance), tone: 'text-emerald-600' },
    { label: 'Dette actuelle', value: money(props.stats.debt), tone: 'text-destructive' },
    { label: 'Retraits', value: money(props.stats.withdrawals) },
    { label: 'Trajets', value: props.stats.trips.toString() },
    { label: 'Réservations', value: props.stats.reservations.toString() },
    { label: 'Places vendues', value: props.stats.seats_sold.toString() },
    { label: 'Places restantes', value: props.stats.seats_remaining.toString() },
];

const links = [
    { label: 'Trajets', href: TripController.index.url() },
    { label: 'Réservations', href: BookingController.index.url() },
    { label: 'Chauffeurs', href: DriverController.index.url() },
    { label: 'Véhicules', href: VehicleController.index.url() },
    { label: 'Gestion financière', href: WalletController.index.url() },
    { label: 'Mon entreprise', href: TransporterController.edit.url() },
];

const statusVariant = (status: string): 'default' | 'secondary' | 'destructive' => {
    if (status === 'completed') {
        return 'default';
    }

    if (status === 'refunded') {
        return 'destructive';
    }

    return 'secondary';
};
</script>

<template>
    <Head title="Espace transporteur" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Espace transporteur" description="Vue d'ensemble de votre activité." />

        <div
            v-if="!hasCompany"
            class="rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center text-muted-foreground dark:border-sidebar-border"
        >
            Aucune entreprise n'est encore associée à votre compte. Contactez l'administrateur pour finaliser votre inscription.
        </div>

        <template v-else>
            <div
                v-if="status === 'pending'"
                class="rounded-xl border border-amber-500/40 bg-amber-500/5 p-4 text-sm text-amber-700 dark:text-amber-400"
            >
                ⏳ Votre compte transporteur est <strong>en attente de validation</strong> par un administrateur. Vous pouvez préparer vos véhicules, chauffeurs et trajets (brouillons), mais la <strong>publication des trajets</strong> sera possible une fois votre compte validé.
            </div>
            <div
                v-else-if="status === 'suspended'"
                class="rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive"
            >
                🚫 Votre compte transporteur est <strong>suspendu</strong>. Contactez l'administrateur.
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <span class="text-xs text-muted-foreground">{{ kpi.label }}</span>
                    <p class="mt-1 text-lg font-semibold" :class="kpi.tone">{{ kpi.value }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-xl border border-sidebar-border/70 p-4 text-center font-medium transition-colors hover:bg-muted/50 dark:border-sidebar-border"
                >
                    {{ link.label }}
                </Link>
            </div>

            <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <div class="border-b p-3 font-medium">Derniers paiements</div>
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left">
                        <tr>
                            <th class="p-3 font-medium">Référence</th>
                            <th class="p-3 font-medium">Montant</th>
                            <th class="p-3 font-medium">Commission</th>
                            <th class="p-3 font-medium">Mode</th>
                            <th class="p-3 font-medium">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentPayments.length === 0">
                            <td colspan="5" class="p-6 text-center text-muted-foreground">Aucun paiement.</td>
                        </tr>
                        <tr v-for="payment in recentPayments" :key="payment.id" class="border-b last:border-0">
                            <td class="p-3 font-mono">{{ payment.reference }}</td>
                            <td class="p-3">{{ money(payment.amount) }}</td>
                            <td class="p-3">{{ money(payment.commission) }}</td>
                            <td class="p-3">{{ payment.method }}</td>
                            <td class="p-3"><Badge :variant="statusVariant(payment.status)">{{ payment.status }}</Badge></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
