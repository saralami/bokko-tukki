<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';

type Row = {
    id: number;
    registration: string;
    name: string;
    capacity: number;
    transporter: string | null;
    status: string;
    status_label: string;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ vehicles: Paginated; filters: { search?: string; status?: string } }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Véhicules', href: '/admin/vehicles' }] } });

const form = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/vehicles', { ...form }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Véhicules" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Véhicules" description="Flotte de la plateforme." />

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="form.search" placeholder="Immatriculation, marque…" class="h-10 max-w-xs" @keyup.enter="apply" />
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option value="active">Actif</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactif</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Immatriculation</th>
                        <th class="px-4 py-2.5 font-medium">Véhicule</th>
                        <th class="px-4 py-2.5 text-right font-medium">Capacité</th>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="v in vehicles.data" :key="v.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5 font-mono">{{ v.registration }}</td>
                        <td class="px-4 py-2.5 font-medium">{{ v.name }}</td>
                        <td class="px-4 py-2.5 text-right">{{ v.capacity }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ v.transporter ?? '—' }}</td>
                        <td class="px-4 py-2.5"><StatusBadge :status="v.status" :label="v.status_label" /></td>
                    </tr>
                    <tr v-if="vehicles.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucun véhicule.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="vehicles.links" :total="vehicles.total" />
    </div>
</template>
