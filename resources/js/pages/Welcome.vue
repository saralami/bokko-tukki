<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bus, MapPin, ScanLine, Search, ShieldCheck, Ticket } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/BrandLogo.vue';
import { login, register } from '@/routes';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth.user);

const steps = [
    { icon: Search, title: 'Cherchez', text: 'Choisissez votre ville de départ, votre destination et la date.' },
    { icon: Ticket, title: 'Réservez', text: 'Réservez vos places en quelques secondes et recevez votre référence.' },
    { icon: Bus, title: 'Voyagez', text: 'Payez en espèces ou par Mobile Money, présentez votre référence à l’embarquement.' },
];

const trust = [
    { icon: ShieldCheck, title: 'Paiement sécurisé', text: 'Espèces à l’embarquement ou Mobile Money (Wave, Orange Money).' },
    { icon: MapPin, title: 'Tout le Sénégal', text: 'Départs interurbains entre les grandes villes du pays.' },
    { icon: ScanLine, title: 'Embarquement simple', text: 'Validation par référence ou QR code par le chauffeur.' },
];
</script>

<template>
    <Head title="Bokko Tuki — Réservez vos trajets interurbains" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Bandeau logo -->
        <div class="flex justify-center px-4 pt-8 pb-4">
            <BrandLogo class="h-40 w-auto md:h-48" />
        </div>

        <!-- Top bar -->
        <header class="sticky top-0 z-20 border-b border-sidebar-border/70 bg-background/90 backdrop-blur dark:border-sidebar-border">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2 font-bold">
                    <span class="text-lg">Bokko Tuki</span>
                </div>
                <nav class="flex items-center gap-2">
                    <template v-if="isAuthenticated">
                        <Link href="/dashboard" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">Mon espace</Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="rounded-md px-4 py-2 text-sm font-medium hover:bg-muted">Se connecter</Link>
                        <Link :href="register()" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">Créer un compte</Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="mx-auto flex w-full max-w-6xl flex-col items-center gap-8 px-4 py-16 text-center md:py-24">
            <span class="inline-flex items-center gap-2 rounded-full bg-accent px-4 py-1.5 text-sm font-semibold text-accent-foreground">
                De ville en ville, en toute confiance
            </span>
            <h1 class="max-w-3xl text-4xl font-extrabold tracking-tight md:text-5xl">
                Voyagez entre les villes du Sénégal, simplement.
            </h1>
            <p class="max-w-2xl text-lg text-muted-foreground">
                Réservez votre place sur les trajets interurbains, payez en espèces ou par Mobile Money, et voyagez l’esprit tranquille.
            </p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <Link :href="register()" class="flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 text-base font-semibold text-primary-foreground hover:bg-primary/90">
                    <Search class="h-5 w-5" /> Créer un compte &amp; rechercher un trajet
                </Link>
                <Link :href="login()" class="flex items-center justify-center rounded-lg border border-sidebar-border/70 px-6 py-3 text-base font-semibold hover:bg-muted dark:border-sidebar-border">
                    J’ai déjà un compte
                </Link>
            </div>
        </section>

        <!-- How it works -->
        <section class="mx-auto w-full max-w-6xl px-4 py-12">
            <h2 class="mb-8 text-center text-2xl font-bold">Comment ça marche</h2>
            <div class="grid gap-6 md:grid-cols-3">
                <div v-for="(step, i) in steps" :key="step.title" class="flex flex-col gap-3 rounded-2xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <component :is="step.icon" class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-semibold text-muted-foreground">Étape {{ i + 1 }}</span>
                    </div>
                    <h3 class="text-lg font-semibold">{{ step.title }}</h3>
                    <p class="text-sm text-muted-foreground">{{ step.text }}</p>
                </div>
            </div>
        </section>

        <!-- Trust -->
        <section class="mx-auto w-full max-w-6xl px-4 py-12">
            <div class="grid gap-6 md:grid-cols-3">
                <div v-for="item in trust" :key="item.title" class="flex items-start gap-3">
                    <component :is="item.icon" class="mt-0.5 h-6 w-6 shrink-0 text-primary" />
                    <div>
                        <h3 class="font-semibold">{{ item.title }}</h3>
                        <p class="text-sm text-muted-foreground">{{ item.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Transporter CTA -->
        <section class="mx-auto w-full max-w-6xl px-4 py-12">
            <div class="flex flex-col items-center gap-4 rounded-2xl bg-gradient-to-br from-primary to-primary/80 p-8 text-center text-primary-foreground md:p-12">
                <h2 class="text-2xl font-bold">Vous êtes transporteur&nbsp;?</h2>
                <p class="max-w-2xl text-primary-foreground/90">
                    Inscrivez votre compagnie, gérez vos véhicules, chauffeurs et trajets, et recevez vos paiements. Votre compte est activé après validation par notre équipe.
                </p>
                <Link href="/transporter/register" class="rounded-lg bg-white px-6 py-3 font-semibold text-primary hover:bg-white/90">
                    Inscrire ma compagnie
                </Link>
            </div>
        </section>

        <footer class="mt-auto border-t border-sidebar-border/70 py-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
            © {{ new Date().getFullYear() }} Bokko Tuki — Transport interurbain au Sénégal.
        </footer>
    </div>
</template>
