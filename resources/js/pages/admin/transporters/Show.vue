<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import TransporterController from '@/actions/App/Http/Controllers/Admin/TransporterController';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatFcfa } from '@/lib/format';

type Transporter = {
    id: number;
    company_name: string;
    status: string;
    status_label: string;
    available_balance: number;
    outstanding_debt: number;
    drivers: number;
    vehicles: number;
    trips: number;
    owner: { name: string; email: string; phone: string | null } | null;
};

const props = defineProps<{ transporter: Transporter; statuses: string[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Transporteurs', href: '/admin/transporters' }, { title: 'Détail', href: '#' }] } });

const statusForm = useForm({ status: props.transporter.status });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';

const changeStatus = () => statusForm.patch(TransporterController.updateStatus.url(props.transporter.id), { preserveScroll: true });

const rows = [
    { label: 'Propriétaire', value: props.transporter.owner?.name ?? '—' },
    { label: 'E-mail', value: props.transporter.owner?.email ?? '—' },
    { label: 'Téléphone', value: props.transporter.owner?.phone ?? '—' },
    { label: 'Chauffeurs', value: `${props.transporter.drivers}` },
    { label: 'Véhicules', value: `${props.transporter.vehicles}` },
    { label: 'Trajets', value: `${props.transporter.trips}` },
];
</script>

<template>
    <Head :title="transporter.company_name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-5 p-4">
        <Link href="/admin/transporters" class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="h-4 w-4" /> Transporteurs
        </Link>

        <div class="flex items-center justify-between gap-2">
            <Heading :title="transporter.company_name" description="Compagnie de transport." />
            <StatusBadge :status="transporter.status" :label="transporter.status_label" />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-xs text-muted-foreground">Solde disponible</span>
                <p class="text-xl font-bold">{{ formatFcfa(transporter.available_balance) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-xs text-muted-foreground">Dette en cours</span>
                <p class="text-xl font-bold" :class="{ 'text-destructive': transporter.outstanding_debt > 0 }">{{ formatFcfa(transporter.outstanding_debt) }}</p>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
            <div v-for="row in rows" :key="row.label" class="grid gap-0.5">
                <span class="text-xs text-muted-foreground">{{ row.label }}</span>
                <span class="font-medium">{{ row.value }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="grid gap-1.5">
                <label class="text-xs text-muted-foreground">Statut</label>
                <select v-model="statusForm.status" :class="selectClass">
                    <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                </select>
            </div>
            <Button :disabled="statusForm.processing" @click="changeStatus">Mettre à jour le statut</Button>
        </div>
    </div>
</template>
