<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TransporterRegisterController from '@/actions/App/Http/Controllers/Auth/TransporterRegisterController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { register } from '@/routes';

defineProps<{ passwordRules: string }>();

defineOptions({
    layout: {
        title: 'Créer un compte transporteur',
        description: 'Inscrivez votre compagnie de transport. Votre compte sera activé après validation par un administrateur.',
    },
});
</script>

<template>
    <Head title="Inscription transporteur" />

    <Form
        v-bind="TransporterRegisterController.store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="company_name">Nom de la compagnie</Label>
                <Input id="company_name" type="text" required autofocus :tabindex="1" name="company_name" placeholder="Ex. Dakar Express" />
                <InputError :message="errors.company_name" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nom du responsable</Label>
                <Input id="name" type="text" required :tabindex="2" autocomplete="name" name="name" placeholder="Nom complet" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Téléphone (optionnel)</Label>
                <Input id="phone" type="tel" :tabindex="3" autocomplete="tel" name="phone" placeholder="+221 77 000 00 00" />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Adresse e-mail</Label>
                <Input id="email" type="email" required :tabindex="4" autocomplete="email" name="email" placeholder="contact@compagnie.sn" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Mot de passe</Label>
                <PasswordInput id="password" required :tabindex="5" autocomplete="new-password" name="password" placeholder="Mot de passe" />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmer le mot de passe</Label>
                <PasswordInput id="password_confirmation" required :tabindex="6" autocomplete="new-password" name="password_confirmation" placeholder="Confirmer le mot de passe" />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button type="submit" class="mt-2 w-full" :tabindex="7" :disabled="processing">
                <Spinner v-if="processing" />
                Créer le compte transporteur
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Vous voyagez ? <TextLink :href="register()" :tabindex="8">Créer un compte passager</TextLink>
            <br />
            Déjà inscrit ? <TextLink :href="login()" :tabindex="9">Se connecter</TextLink>
        </div>
    </Form>
</template>
