<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Paginator from '@/components/admin/Paginator.vue';
import Heading from '@/components/Heading.vue';
import { formatDateTime, formatFcfa } from '@/lib/format';
import FinanceNav from './FinanceNav.vue';

type Row = {
    id: number;
    reference: string | null;
    transporter: string | null;
    amount: number;
    date: string | null;
    reversal: string | null;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

defineProps<{ refunds: Paginated }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Remboursements', href: '/admin/finance/refunds' }] } });
</script>

<template>
    <Head title="Remboursements" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Remboursements" description="Chaque remboursement est une écriture compensatoire justifiée — la transaction d'origine reste intacte." />

        <FinanceNav />

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Référence</th>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 text-right font-medium">Montant</th>
                        <th class="px-4 py-2.5 font-medium">Justification (écriture compensatoire)</th>
                        <th class="px-4 py-2.5 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="r in refunds.data" :key="r.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5 font-mono">{{ r.reference ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ r.transporter ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right">{{ formatFcfa(r.amount) }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ r.reversal ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ formatDateTime(r.date) }}</td>
                    </tr>
                    <tr v-if="refunds.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucun remboursement.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="refunds.links" :total="refunds.total" />
    </div>
</template>
