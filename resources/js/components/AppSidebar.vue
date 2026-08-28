<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Banknote, Bus, LayoutGrid, Settings, Ticket, Truck, Users, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const roles = computed<string[]>(() => (page.props.auth as { roles?: string[] })?.roles ?? []);
const isAdmin = computed(() => roles.value.includes('admin'));

const adminNavItems: NavItem[] = [
    { title: 'Administration', href: '/admin/dashboard', icon: LayoutGrid },
    { title: 'Utilisateurs', href: '/admin/users', icon: Users },
    { title: 'Transporteurs', href: '/admin/transporters', icon: Truck },
    { title: 'Chauffeurs', href: '/admin/drivers', icon: Users },
    { title: 'Véhicules', href: '/admin/vehicles', icon: Bus },
    { title: 'Trajets', href: '/admin/trips', icon: Bus },
    { title: 'Réservations', href: '/admin/bookings', icon: Ticket },
    { title: 'Transactions', href: '/admin/finance/transactions', icon: Banknote },
    { title: 'Dettes', href: '/admin/finance/debts', icon: AlertTriangle },
    { title: 'Ledger', href: '/admin/finance/ledger', icon: Banknote },
    { title: 'Retraits', href: '/admin/withdrawals', icon: Wallet },
    { title: 'Paramètres', href: '/admin/settings', icon: Settings },
    { title: "Journal d'audit", href: '/admin/audit-logs', icon: AlertTriangle },
];

const mainNavItems = computed<NavItem[]>(() =>
    isAdmin.value ? adminNavItems : [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }],
);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
