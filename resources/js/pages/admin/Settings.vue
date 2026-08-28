<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type SettingItem = {
    key: string;
    label: string;
    help: string;
    type: string;
    value: number;
    min: number;
    max: number;
    step: number;
};

const props = defineProps<{ settings: SettingItem[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Administration', href: '/admin/dashboard' }, { title: 'Paramètres', href: '/admin/settings' }] } });

const initial: Record<string, number> = {};
props.settings.forEach((s) => {
    initial[s.key] = s.value;
});

const form = useForm<{ settings: Record<string, number> }>({ settings: initial });

const save = () => form.patch('/admin/settings', { preserveScroll: true });

const errorFor = (key: string): string | undefined => (form.errors as Record<string, string>)[`settings.${key}`];
</script>

<template>
    <Head title="Paramètres" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-5 p-4">
        <Heading title="Paramètres métier" description="Règles financières et opérationnelles de la plateforme. Chaque changement est tracé dans le journal d'audit." />

        <form class="flex flex-col gap-5 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="save">
            <div v-for="setting in settings" :key="setting.key" class="grid gap-1.5">
                <Label :for="setting.key">{{ setting.label }}</Label>
                <Input
                    :id="setting.key"
                    v-model.number="form.settings[setting.key]"
                    type="number"
                    :min="setting.min"
                    :max="setting.max"
                    :step="setting.step"
                    class="max-w-xs"
                />
                <p class="text-xs text-muted-foreground">{{ setting.help }}</p>
                <p v-if="errorFor(setting.key)" class="text-xs text-destructive">{{ errorFor(setting.key) }}</p>
            </div>

            <div>
                <Button type="submit" :disabled="form.processing">Enregistrer</Button>
            </div>
        </form>
    </div>
</template>
