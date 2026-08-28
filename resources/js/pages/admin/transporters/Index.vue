<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { formatFcfa } from '@/lib/format';

type Row = {
    id: number;
    company_name: string;
    status: string;
    status_label: string;
    available_balance: number;
    outstanding_debt: number;
    drivers: number;
    vehicles: number;
    trips: number;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ transporters: Paginated; filters: { search?: string; status?: string }; statuses: string[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Transporteurs', href: '/admin/transporters' }] } });

const form = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';

const apply = () => router.get('/admin/transporters', { ...form }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Transporteurs" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Transporteurs" description="Compagnies et portefeuilles." />

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="form.search" placeholder="Compagnie…" class="h-10 max-w-xs" @keyup.enter="apply" />
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Compagnie</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                        <th class="px-4 py-2.5 text-right font-medium">Solde</th>
                        <th class="px-4 py-2.5 text-right font-medium">Dette</th>
                        <th class="px-4 py-2.5 text-right font-medium">Flotte</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="t in transporters.data" :key="t.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5">
                            <Link :href="`/admin/transporters/${t.id}`" class="font-medium text-primary hover:underline">{{ t.company_name }}</Link>
                        </td>
                        <td class="px-4 py-2.5"><StatusBadge :status="t.status" :label="t.status_label" /></td>
                        <td class="px-4 py-2.5 text-right">{{ formatFcfa(t.available_balance) }}</td>
                        <td class="px-4 py-2.5 text-right" :class="{ 'text-destructive': t.outstanding_debt > 0 }">{{ formatFcfa(t.outstanding_debt) }}</td>
                        <td class="px-4 py-2.5 text-right text-muted-foreground">{{ t.drivers }}👤 · {{ t.vehicles }}🚌 · {{ t.trips }}🛣️</td>
                    </tr>
                    <tr v-if="transporters.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucun transporteur.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="transporters.links" :total="transporters.total" />
    </div>
</template>
