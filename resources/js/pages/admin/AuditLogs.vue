<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import Heading from '@/components/Heading.vue';
import { formatDateTime } from '@/lib/format';

type Row = { id: number; action: string; description: string | null; user: string | null; ip: string | null; date: string | null };

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ logs: Paginated; filters: { action?: string }; actions: string[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: "Journal d'audit", href: '/admin/audit-logs' }] } });

const form = reactive({ action: props.filters.action ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/audit-logs', { ...form }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Journal d'audit" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Journal d'audit" description="Trace immuable des opérations sensibles." />

        <div class="flex flex-wrap items-center gap-2">
            <select v-model="form.action" :class="selectClass" @change="apply">
                <option value="">Toutes les opérations</option>
                <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Opération</th>
                        <th class="px-4 py-2.5 font-medium">Détail</th>
                        <th class="px-4 py-2.5 font-medium">Administrateur</th>
                        <th class="px-4 py-2.5 font-medium">IP</th>
                        <th class="px-4 py-2.5 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="log in logs.data" :key="log.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5"><span class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">{{ log.action }}</span></td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ log.description ?? '—' }}</td>
                        <td class="px-4 py-2.5">{{ log.user ?? 'Système' }}</td>
                        <td class="px-4 py-2.5 font-mono text-xs text-muted-foreground">{{ log.ip ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ formatDateTime(log.date) }}</td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucune opération enregistrée.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="logs.links" :total="logs.total" />
    </div>
</template>
