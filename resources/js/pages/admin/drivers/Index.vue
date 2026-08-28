<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';

type Row = {
    id: number;
    name: string;
    phone: string | null;
    transporter: string | null;
    status: string;
    status_label: string;
    trips: number;
    linked: boolean;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ drivers: Paginated; filters: { search?: string; status?: string } }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Chauffeurs', href: '/admin/drivers' }] } });

const form = reactive({ search: props.filters.search ?? '', status: props.filters.status ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/drivers', { ...form }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Chauffeurs" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Chauffeurs" description="Vue d'ensemble des chauffeurs de la plateforme." />

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="form.search" placeholder="Nom…" class="h-10 max-w-xs" @keyup.enter="apply" />
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option value="active">Actif</option>
                <option value="suspended">Suspendu</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Nom</th>
                        <th class="px-4 py-2.5 font-medium">Téléphone</th>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                        <th class="px-4 py-2.5 text-right font-medium">Trajets</th>
                        <th class="px-4 py-2.5 font-medium">Compte</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="d in drivers.data" :key="d.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5 font-medium">{{ d.name }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ d.phone ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ d.transporter ?? '—' }}</td>
                        <td class="px-4 py-2.5"><StatusBadge :status="d.status" :label="d.status_label" /></td>
                        <td class="px-4 py-2.5 text-right">{{ d.trips }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ d.linked ? 'Lié' : 'Non lié' }}</td>
                    </tr>
                    <tr v-if="drivers.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Aucun chauffeur.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="drivers.links" :total="drivers.total" />
    </div>
</template>
