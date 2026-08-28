<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import FinanceController from '@/actions/App/Http/Controllers/Admin/FinanceController';
import Paginator from '@/components/admin/Paginator.vue';
import StatCard from '@/components/admin/StatCard.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime, formatFcfa } from '@/lib/format';
import FinanceNav from './FinanceNav.vue';

type Row = {
    id: number;
    reference: string | null;
    transporter: string | null;
    method: string;
    method_label: string;
    amount: number;
    commission: number;
    status: string;
    status_label: string;
    date: string | null;
};

type Paginated = { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{
    payments: Paginated;
    filters: { method?: string; status?: string };
    methods: string[];
    statuses: string[];
    totals: { volume: number; commissions: number };
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Transactions', href: '/admin/finance/transactions' }] } });

const form = reactive({ method: props.filters.method ?? '', status: props.filters.status ?? '' });
const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';
const apply = () => router.get('/admin/finance/transactions', { ...form }, { preserveState: true, replace: true });

const refundingId = ref<number | null>(null);
const refundForm = useForm({ reason: '' });

const openRefund = (id: number) => {
    refundingId.value = id;
    refundForm.reset();
    refundForm.clearErrors();
};

const submitRefund = (id: number) => {
    refundForm.post(FinanceController.refund.url(id), {
        preserveScroll: true,
        onSuccess: () => {
            refundingId.value = null;
        },
    });
};
</script>

<template>
    <Head title="Transactions" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Transactions financières" description="Paiements auditables. Aucune modification directe : les corrections passent par un remboursement compensatoire justifié." />

        <FinanceNav />

        <div class="grid gap-4 sm:grid-cols-2">
            <StatCard label="Volume réglé" :value="formatFcfa(totals.volume)" />
            <StatCard label="Commissions Allo Dakar" :value="formatFcfa(totals.commissions)" />
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select v-model="form.method" :class="selectClass" @change="apply">
                <option value="">Tous modes</option>
                <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
            </select>
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Référence</th>
                        <th class="px-4 py-2.5 font-medium">Transporteur</th>
                        <th class="px-4 py-2.5 font-medium">Mode</th>
                        <th class="px-4 py-2.5 text-right font-medium">Montant</th>
                        <th class="px-4 py-2.5 text-right font-medium">Commission</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                        <th class="px-4 py-2.5 font-medium">Date</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <template v-for="p in payments.data" :key="p.id">
                        <tr class="hover:bg-muted/40">
                            <td class="px-4 py-2.5 font-mono">{{ p.reference ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-muted-foreground">{{ p.transporter ?? '—' }}</td>
                            <td class="px-4 py-2.5">{{ p.method_label }}</td>
                            <td class="px-4 py-2.5 text-right">{{ formatFcfa(p.amount) }}</td>
                            <td class="px-4 py-2.5 text-right text-muted-foreground">{{ formatFcfa(p.commission) }}</td>
                            <td class="px-4 py-2.5"><StatusBadge :status="p.status" :label="p.status_label" /></td>
                            <td class="px-4 py-2.5 text-muted-foreground">{{ formatDateTime(p.date) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <Button v-if="p.status === 'completed'" size="sm" variant="outline" @click="openRefund(p.id)">Rembourser</Button>
                            </td>
                        </tr>
                        <tr v-if="refundingId === p.id" class="bg-muted/30">
                            <td colspan="8" class="px-4 py-3">
                                <form class="flex flex-col gap-2 sm:flex-row sm:items-start" @submit.prevent="submitRefund(p.id)">
                                    <div class="flex-1">
                                        <textarea
                                            v-model="refundForm.reason"
                                            rows="2"
                                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                                            placeholder="Justification obligatoire du remboursement (écriture compensatoire)…"
                                        />
                                        <p v-if="refundForm.errors.reason" class="mt-1 text-xs text-destructive">{{ refundForm.errors.reason }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button type="submit" size="sm" variant="destructive" :disabled="refundForm.processing">Confirmer</Button>
                                        <Button type="button" size="sm" variant="ghost" @click="refundingId = null">Annuler</Button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="payments.data.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">Aucune transaction.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="payments.links" :total="payments.total" />
    </div>
</template>
