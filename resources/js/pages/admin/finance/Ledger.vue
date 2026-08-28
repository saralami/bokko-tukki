<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import Heading from '@/components/Heading.vue';
import { formatDateTime, formatFcfa } from '@/lib/format';
import FinanceNav from './FinanceNav.vue';

type Row = {
    id: number;
    type: string;
    type_label: string;
    transporter: string | null;
    amount: number;
    balance_delta: number;
    debt_delta: number;
    balance_after: number;
    debt_after: number;
    description: string | null;
    date: string | null;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{ entries: Paginated; filters: { type?: string }; types: { value: string; label: string }[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Ledger', href: '/admin/finance/ledger' }] } });

const form = reactive({ type: props.filters.type ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/finance/ledger', { ...form }, { preserveState: true, replace: true });

const signed = (value: number) => (value > 0 ? `+${formatFcfa(value)}` : formatFcfa(value));
</script>

<template>
    <Head title="Ledger" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Grand livre (ledger)" description="Journal comptable immuable — chaque écriture est définitive." />

        <FinanceNav />

        <div class="flex items-center gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 px-3 py-2 text-sm text-muted-foreground dark:border-sidebar-border">
            <Lock class="h-4 w-4" /> Les écritures du ledger ne peuvent jamais être modifiées ni supprimées. Les corrections se font par écriture compensatoire.
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select v-model="form.type" :class="selectClass" @change="apply">
                <option value="">Tous les types</option>
                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Type</th>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 text-right font-medium">Solde Δ</th>
                        <th class="px-4 py-2.5 text-right font-medium">Dette Δ</th>
                        <th class="px-4 py-2.5 text-right font-medium">Solde après</th>
                        <th class="px-4 py-2.5 text-right font-medium">Dette après</th>
                        <th class="px-4 py-2.5 font-medium">Libellé</th>
                        <th class="px-4 py-2.5 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="e in entries.data" :key="e.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5"><span class="rounded bg-muted px-1.5 py-0.5 text-xs">{{ e.type_label }}</span></td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ e.transporter ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right" :class="e.balance_delta >= 0 ? 'text-green-600' : 'text-destructive'">{{ signed(e.balance_delta) }}</td>
                        <td class="px-4 py-2.5 text-right" :class="e.debt_delta > 0 ? 'text-destructive' : 'text-green-600'">{{ signed(e.debt_delta) }}</td>
                        <td class="px-4 py-2.5 text-right text-muted-foreground">{{ formatFcfa(e.balance_after) }}</td>
                        <td class="px-4 py-2.5 text-right text-muted-foreground">{{ formatFcfa(e.debt_after) }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ e.description ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ formatDateTime(e.date) }}</td>
                    </tr>
                    <tr v-if="entries.data.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Aucune écriture.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="entries.links" :total="entries.total" />
    </div>
</template>
