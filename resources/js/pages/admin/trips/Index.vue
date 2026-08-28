<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';

type Row = {
    id: number;
    route: string;
    transporter: string | null;
    date: string;
    time: string;
    capacity: number;
    available_seats: number;
    reservations: number;
    status: string;
    status_label: string;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ trips: Paginated; filters: { search?: string; status?: string }; statuses: string[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Trajets', href: '/admin/trips' }] } });

const form = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/trips', { ...form }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Trajets" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Trajets" description="Tous les trajets de la plateforme." />

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="form.search" placeholder="Ville…" class="h-10 max-w-xs" @keyup.enter="apply" />
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Trajet</th>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 font-medium">Départ</th>
                        <th class="px-4 py-2.5 text-right font-medium">Places</th>
                        <th class="px-4 py-2.5 text-right font-medium">Résa</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="t in trips.data" :key="t.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5">
                            <Link :href="`/admin/trips/${t.id}`" class="font-medium text-primary hover:underline">{{ t.route }}</Link>
                        </td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ t.transporter ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ formatDate(t.date) }} · {{ t.time }}</td>
                        <td class="px-4 py-2.5 text-right">{{ t.available_seats }}/{{ t.capacity }}</td>
                        <td class="px-4 py-2.5 text-right">{{ t.reservations }}</td>
                        <td class="px-4 py-2.5"><StatusBadge :status="t.status" :label="t.status_label" /></td>
                    </tr>
                    <tr v-if="trips.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Aucun trajet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="trips.links" :total="trips.total" />
    </div>
</template>
