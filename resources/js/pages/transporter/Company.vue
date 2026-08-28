<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TransporterController from '@/actions/App/Http/Controllers/Transporter/TransporterController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    transporter: {
        id: number;
        company_name: string;
        email: string | null;
        phone: string | null;
        address: string | null;
        status: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mon entreprise', href: '/transporter/company' }],
    },
});
</script>

<template>
    <Head title="Mon entreprise" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Mon entreprise"
                description="Gérez les informations de votre société de transport."
            />
            <Badge variant="secondary">Statut : {{ props.transporter.status }}</Badge>
        </div>

        <Form
            v-bind="TransporterController.update.form(props.transporter.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="company_name">Nom de l'entreprise</Label>
                <Input
                    id="company_name"
                    name="company_name"
                    :default-value="props.transporter.company_name"
                    required
                />
                <InputError :message="errors.company_name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="props.transporter.email ?? ''"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Téléphone</Label>
                <Input
                    id="phone"
                    name="phone"
                    :default-value="props.transporter.phone ?? ''"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="address">Adresse</Label>
                <Input
                    id="address"
                    name="address"
                    :default-value="props.transporter.address ?? ''"
                />
                <InputError :message="errors.address" />
            </div>

            <Button :disabled="processing">Enregistrer</Button>
        </Form>
    </div>
</template>
