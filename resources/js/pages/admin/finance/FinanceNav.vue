<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const current = computed(() => page.url.split('?')[0]);

const tabs = [
    { label: 'Transactions', href: '/admin/finance/transactions' },
    { label: 'Commissions', href: '/admin/finance/commissions' },
    { label: 'Dettes', href: '/admin/finance/debts' },
    { label: 'Remboursements', href: '/admin/finance/refunds' },
    { label: 'Retraits', href: '/admin/withdrawals' },
    { label: 'Ledger', href: '/admin/finance/ledger' },
];
</script>

<template>
    <nav class="flex flex-wrap gap-1 rounded-lg border border-sidebar-border/70 p-1 dark:border-sidebar-border">
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
            :class="current.startsWith(tab.href) ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted/50'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>
