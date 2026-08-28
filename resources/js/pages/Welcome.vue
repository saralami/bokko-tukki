<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Bus, Calendar, MapPin, ScanLine, Search, ShieldCheck, Ticket } from '@lucide/vue';
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
        <div class="flex justify-center px-4 pt-8 pb-2">
            <BrandLogo class="h-40 w-auto md:h-48" />
        </div>

        <!-- Nav flottante (effet verre) -->
        <header class="sticky top-4 z-30 px-4">
            <nav
                class="mx-auto flex w-full max-w-3xl items-center justify-center gap-3 rounded-full border border-border/60 bg-background/70 p-1.5 shadow-lg shadow-primary/5 backdrop-blur-xl sm:justify-between sm:pl-6"
            >
                <div class="hidden items-center gap-6 text-sm font-medium sm:flex">
                    <Link href="/" class="text-foreground/80 transition-colors hover:text-primary">Accueil</Link>
                    <Link :href="login()" class="text-foreground/80 transition-colors hover:text-primary">Trajets</Link>
                    <a href="#comment" class="text-foreground/80 transition-colors hover:text-primary">Comment ça marche</a>
                </div>
                <div class="flex items-center gap-2">
                    <template v-if="isAuthenticated">
                        <Link href="/dashboard" class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">Mon espace</Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="rounded-full px-4 py-2 text-sm font-medium transition hover:bg-muted">Se connecter</Link>
                        <Link :href="register()" class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90">Créer un compte</Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero -->
        <section class="relative mx-auto w-full max-w-5xl px-4 pt-14 pb-20 text-center md:pt-20">
            <!-- Halo soleil décoratif -->
            <div
                class="pointer-events-none absolute inset-x-0 -top-8 -z-10 mx-auto h-72 max-w-2xl rounded-full bg-gradient-to-b from-accent via-accent/40 to-transparent blur-3xl"
            />

            <span class="inline-flex items-center gap-2 rounded-full border border-accent-foreground/15 bg-accent px-4 py-1.5 text-sm font-semibold text-accent-foreground">
                ☀️ De ville en ville, en toute confiance
            </span>
            <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-extrabold tracking-tight md:text-6xl">
                Voyagez entre les villes du
                <span class="bg-gradient-to-r from-primary to-brand-orange bg-clip-text text-transparent">Sénégal</span>,
                simplement.
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-muted-foreground">
                Réservez votre place sur les trajets interurbains, payez en espèces ou par Mobile Money, et voyagez l’esprit tranquille.
            </p>

            <!-- Barre de recherche -->
            <div class="mx-auto mt-10 max-w-3xl rounded-3xl border border-border/70 bg-card p-3 text-left shadow-xl shadow-primary/5">
                <div class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <label class="flex items-center gap-2 rounded-2xl bg-muted/60 px-4 py-3">
                        <MapPin class="h-5 w-5 shrink-0 text-primary" />
                        <span class="flex flex-col">
                            <span class="text-xs font-medium text-muted-foreground">Départ</span>
                            <input type="text" placeholder="Dakar" class="w-full bg-transparent text-sm font-semibold outline-none placeholder:font-normal placeholder:text-muted-foreground/60" />
                        </span>
                    </label>
                    <label class="flex items-center gap-2 rounded-2xl bg-muted/60 px-4 py-3">
                        <MapPin class="h-5 w-5 shrink-0 text-brand-orange" />
                        <span class="flex flex-col">
                            <span class="text-xs font-medium text-muted-foreground">Arrivée</span>
                            <input type="text" placeholder="Saint-Louis" class="w-full bg-transparent text-sm font-semibold outline-none placeholder:font-normal placeholder:text-muted-foreground/60" />
                        </span>
                    </label>
                    <label class="flex items-center gap-2 rounded-2xl bg-muted/60 px-4 py-3">
                        <Calendar class="h-5 w-5 shrink-0 text-primary" />
                        <span class="flex flex-col">
                            <span class="text-xs font-medium text-muted-foreground">Date</span>
                            <input type="text" placeholder="Aujourd’hui" class="w-full bg-transparent text-sm font-semibold outline-none placeholder:font-normal placeholder:text-muted-foreground/60" />
                        </span>
                    </label>
                    <Link
                        :href="register()"
                        class="flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3 text-base font-semibold text-primary-foreground transition hover:bg-primary/90"
                    >
                        <Search class="h-5 w-5" />
                        <span class="sm:hidden">Rechercher</span>
                    </Link>
                </div>
            </div>

            <p class="mt-4 text-sm text-muted-foreground">
                Déjà un compte&nbsp;?
                <Link :href="login()" class="font-semibold text-primary hover:underline">Se connecter</Link>
            </p>
        </section>

        <!-- How it works -->
        <section id="comment" class="mx-auto w-full max-w-6xl scroll-mt-24 px-4 py-12">
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
