<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import WithdrawalController from '@/actions/App/Http/Controllers/Admin/WithdrawalController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Withdrawal = {
    id: number;
    transporter: string;
    amount: number;
    status: string;
    date: string | null;
};

defineProps<{ withdrawals: Withdrawal[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Retraits', href: '/admin/withdrawals' }],
    },
});
</script>

<template>
    <Head title="Retraits" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Retraits" description="Traitez les demandes de retrait des transporteurs." />

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Transporteur</th>
                        <th class="p-3 font-medium">Montant</th>
                        <th class="p-3 font-medium">Statut</th>
                        <th class="p-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="withdrawals.length === 0">
                        <td colspan="4" class="p-6 text-center text-muted-foreground">Aucune demande.</td>
                    </tr>
                    <tr v-for="withdrawal in withdrawals" :key="withdrawal.id" class="border-b last:border-0">
                        <td class="p-3 font-medium">{{ withdrawal.transporter }}</td>
                        <td class="p-3">{{ withdrawal.amount }} FCFA</td>
                        <td class="p-3"><Badge variant="secondary">{{ withdrawal.status }}</Badge></td>
                        <td class="p-3">
                            <div class="flex items-center justify-end gap-2">
                                <template v-if="['requested', 'approved'].includes(withdrawal.status)">
                                    <Form
                                        v-if="withdrawal.status === 'requested'"
                                        v-bind="WithdrawalController.approve.form(withdrawal.id)"
                                    >
                                        <Button type="submit" size="sm" variant="outline">Approuver</Button>
                                    </Form>
                                    <Form v-bind="WithdrawalController.pay.form(withdrawal.id)">
                                        <Button type="submit" size="sm">Payer</Button>
                                    </Form>
                                    <Form v-bind="WithdrawalController.reject.form(withdrawal.id)">
                                        <Button type="submit" size="sm" variant="destructive">Rejeter</Button>
                                    </Form>
                                </template>
                                <span v-else class="text-muted-foreground">—</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
