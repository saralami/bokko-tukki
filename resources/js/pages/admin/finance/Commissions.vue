<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StatCard from '@/components/admin/StatCard.vue';
import Heading from '@/components/Heading.vue';
import { formatFcfa } from '@/lib/format';
import FinanceNav from './FinanceNav.vue';

type Row = { method: string; method_label: string; count: number; total: number };

defineProps<{ byMethod: Row[]; total: number }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Commissions', href: '/admin/finance/commissions' }] } });
</script>

<template>
    <Head title="Commissions" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Commissions Allo Dakar" description="Revenus de la plateforme par mode de paiement." />

        <FinanceNav />

        <StatCard label="Total des commissions" :value="formatFcfa(total)" />

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Mode de paiement</th>
                        <th class="px-4 py-2.5 text-right font-medium">Transactions</th>
                        <th class="px-4 py-2.5 text-right font-medium">Commissions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="row in byMethod" :key="row.method" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5 font-medium">{{ row.method_label }}</td>
                        <td class="px-4 py-2.5 text-right">{{ row.count }}</td>
                        <td class="px-4 py-2.5 text-right font-medium">{{ formatFcfa(row.total) }}</td>
                    </tr>
                    <tr v-if="byMethod.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">Aucune commission.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
