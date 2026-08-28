<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { formatDate, formatFcfa } from '@/lib/format';

type Row = {
    id: number;
    reference: string;
    passenger: string;
    route: string;
    date: string;
    seats: number;
    amount: number;
    payment_method: string;
    payment_status: string;
    status: string;
    status_label: string;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ bookings: Paginated; filters: { search?: string; status?: string }; statuses: string[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Réservations', href: '/admin/bookings' }] } });

const form = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/bookings', { ...form }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Réservations" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Réservations" description="Toutes les réservations." />

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="form.search" placeholder="Référence, passager…" class="h-10 max-w-xs" @keyup.enter="apply" />
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Référence</th>
                        <th class="px-4 py-2.5 font-medium">Passager</th>
                        <th class="px-4 py-2.5 font-medium">Trajet</th>
                        <th class="px-4 py-2.5 text-right font-medium">Montant</th>
                        <th class="px-4 py-2.5 font-medium">Paiement</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="b in bookings.data" :key="b.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5">
                            <Link :href="`/admin/bookings/${b.id}`" class="font-mono text-primary hover:underline">{{ b.reference }}</Link>
                        </td>
                        <td class="px-4 py-2.5">{{ b.passenger }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ b.route }}<br /><span class="text-xs">{{ formatDate(b.date) }}</span></td>
                        <td class="px-4 py-2.5 text-right">{{ formatFcfa(b.amount) }}</td>
                        <td class="px-4 py-2.5"><StatusBadge :status="b.payment_status" :label="b.payment_status" /></td>
                        <td class="px-4 py-2.5"><StatusBadge :status="b.status" :label="b.status_label" /></td>
                    </tr>
                    <tr v-if="bookings.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Aucune réservation.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="bookings.links" :total="bookings.total" />
    </div>
</template>
