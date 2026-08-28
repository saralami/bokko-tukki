<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import WalletController from '@/actions/App/Http/Controllers/Transporter/WalletController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LedgerEntry = {
    id: number;
    type: string;
    amount: number;
    balance_delta: number;
    debt_delta: number;
    balance_after: number;
    debt_after: number;
    description: string | null;
    date: string | null;
};

type Withdrawal = { id: number; amount: number; status: string; date: string | null };

const props = defineProps<{
    wallet: { available_balance: number; outstanding_debt: number };
    ledger: LedgerEntry[];
    withdrawals: Withdrawal[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Portefeuille', href: '/transporter/wallet' }],
    },
});

const signed = (value: number): string => (value > 0 ? `+${value}` : `${value}`);
</script>

<template>
    <Head title="Portefeuille" />

    <div class="flex flex-col gap-6 p-4">
        <Heading title="Portefeuille" description="Solde disponible, dettes et historique financier." />

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-xs text-muted-foreground">Solde disponible</span>
                <p class="text-2xl font-semibold">{{ props.wallet.available_balance }} FCFA</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <span class="text-xs text-muted-foreground">Dette de commission</span>
                <p class="text-2xl font-semibold text-destructive">{{ props.wallet.outstanding_debt }} FCFA</p>
            </div>
        </div>

        <Form
            v-bind="WalletController.requestWithdrawal.form()"
            class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-end dark:border-sidebar-border"
            v-slot="{ errors, processing }"
        >
            <div class="grid flex-1 gap-2">
                <Label for="amount">Demander un retrait (FCFA)</Label>
                <Input id="amount" type="number" name="amount" min="1" :max="props.wallet.available_balance" required />
                <InputError :message="errors.amount" />
            </div>
            <Button type="submit" :disabled="processing || props.wallet.available_balance < 1">Demander</Button>
        </Form>

        <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div class="border-b p-3 font-medium">Demandes de retrait</div>
            <div v-if="withdrawals.length === 0" class="p-6 text-center text-muted-foreground">Aucun retrait.</div>
            <ul v-else class="divide-y">
                <li v-for="withdrawal in withdrawals" :key="withdrawal.id" class="flex items-center justify-between p-3">
                    <span>{{ withdrawal.amount }} FCFA</span>
                    <Badge variant="secondary">{{ withdrawal.status }}</Badge>
                </li>
            </ul>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div class="border-b p-3 font-medium">Grand livre</div>
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="p-3 font-medium">Opération</th>
                        <th class="p-3 font-medium">Solde</th>
                        <th class="p-3 font-medium">Dette</th>
                        <th class="p-3 font-medium">Solde après</th>
                        <th class="p-3 font-medium">Dette après</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="ledger.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">Aucune écriture.</td>
                    </tr>
                    <tr v-for="entry in ledger" :key="entry.id" class="border-b last:border-0">
                        <td class="p-3">{{ entry.type }}</td>
                        <td class="p-3">{{ signed(entry.balance_delta) }}</td>
                        <td class="p-3">{{ signed(entry.debt_delta) }}</td>
                        <td class="p-3">{{ entry.balance_after }}</td>
                        <td class="p-3">{{ entry.debt_after }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
