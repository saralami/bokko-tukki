<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import StatCard from '@/components/admin/StatCard.vue';
import Heading from '@/components/Heading.vue';
import { formatFcfa } from '@/lib/format';
import FinanceNav from './FinanceNav.vue';

type Row = { id: number; transporter_id: number; transporter: string | null; outstanding_debt: number; available_balance: number };

defineProps<{ wallets: Row[]; total: number }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Dettes', href: '/admin/finance/debts' }] } });
</script>

<template>
    <Head title="Dettes transporteurs" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Dettes transporteurs" description="Commissions cash dues à Allo Dakar." />

        <FinanceNav />

        <StatCard label="Dette totale" :value="formatFcfa(total)" :tone="total > 0 ? 'warning' : 'default'" />

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 text-right font-medium">Dette</th>
                        <th class="px-4 py-2.5 text-right font-medium">Solde disponible</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="w in wallets" :key="w.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5 font-medium">
                            <Link :href="`/admin/transporters/${w.transporter_id}`" class="text-primary hover:underline">{{ w.transporter ?? '—' }}</Link>
                        </td>
                        <td class="px-4 py-2.5 text-right font-medium text-destructive">{{ formatFcfa(w.outstanding_debt) }}</td>
                        <td class="px-4 py-2.5 text-right text-muted-foreground">{{ formatFcfa(w.available_balance) }}</td>
                    </tr>
                    <tr v-if="wallets.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">Aucune dette en cours.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
