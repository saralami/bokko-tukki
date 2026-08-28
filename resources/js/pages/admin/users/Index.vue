<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Paginator from '@/components/admin/Paginator.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/format';

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    suspended: boolean;
    created_at: string | null;
};

type Paginated = { data: UserRow[]; links: { url: string | null; label: string; active: boolean }[]; total: number };

const props = defineProps<{
    users: Paginated;
    filters: { search?: string; role?: string; status?: string };
    roles: string[];
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Utilisateurs', href: '/admin/users' }] } });

const form = reactive({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});

const selectClass = 'h-10 rounded-md border border-input bg-transparent px-3 text-sm';

const apply = () => {
    router.get('/admin/users', { ...form }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Utilisateurs" />

    <div class="flex flex-col gap-5 p-4">
        <Heading title="Utilisateurs" description="Comptes de la plateforme." />

        <div class="flex flex-wrap items-center gap-2">
            <Input v-model="form.search" placeholder="Nom ou e-mail…" class="h-10 max-w-xs" @keyup.enter="apply" />
            <select v-model="form.role" :class="selectClass" @change="apply">
                <option value="">Tous les rôles</option>
                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
            </select>
            <select v-model="form.status" :class="selectClass" @change="apply">
                <option value="">Tous statuts</option>
                <option value="active">Actifs</option>
                <option value="suspended">Suspendus</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Nom</th>
                        <th class="px-4 py-2.5 font-medium">E-mail</th>
                        <th class="px-4 py-2.5 font-medium">Rôle</th>
                        <th class="px-4 py-2.5 font-medium">Statut</th>
                        <th class="px-4 py-2.5 font-medium">Inscrit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/40">
                        <td class="px-4 py-2.5">
                            <Link :href="`/admin/users/${user.id}`" class="font-medium text-primary hover:underline">{{ user.name }}</Link>
                        </td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ user.email }}</td>
                        <td class="px-4 py-2.5">
                            <span v-for="role in user.roles" :key="role" class="mr-1 rounded bg-muted px-1.5 py-0.5 text-xs">{{ role }}</span>
                        </td>
                        <td class="px-4 py-2.5">
                            <StatusBadge :status="user.suspended ? 'suspended' : 'active'" :label="user.suspended ? 'Suspendu' : 'Actif'" />
                        </td>
                        <td class="px-4 py-2.5 text-muted-foreground">{{ user.created_at ? formatDate(user.created_at.slice(0, 10)) : '—' }}</td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucun utilisateur.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Paginator :links="users.links" :total="users.total" />
    </div>
</template>
