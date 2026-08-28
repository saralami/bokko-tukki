<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Bus, MapPin, ShieldCheck } from '@lucide/vue';
import BrandLogo from '@/components/BrandLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const highlights = [
    {
        icon: MapPin,
        title: 'Tout le Sénégal',
        text: 'Trajets interurbains entre les grandes villes du pays.',
    },
    {
        icon: ShieldCheck,
        title: 'Paiement sécurisé',
        text: 'Espèces à l’embarquement ou Mobile Money (Wave, Orange Money).',
    },
    {
        icon: Bus,
        title: 'Embarquement simple',
        text: 'Présentez votre référence ou votre QR code au chauffeur.',
    },
];
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-2">
        <!-- Panneau de marque -->
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-secondary via-background to-accent p-10 text-foreground lg:flex"
        >
            <div
                class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary/10 blur-2xl"
            />
            <div
                class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-chart-3/20 blur-3xl"
            />

            <div class="relative z-10 space-y-6">
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background/70 px-4 py-2 text-sm font-medium text-foreground/80 backdrop-blur transition hover:text-primary"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Retour à l’accueil
                </Link>
                <Link
                    :href="home()"
                    class="flex items-center gap-3 text-lg font-bold text-primary"
                >
                    <BrandLogo class="size-16" />
                    Bokko Tuki
                </Link>
            </div>

            <div class="relative z-10 max-w-md space-y-8">
                <h2 class="text-3xl font-extrabold leading-tight tracking-tight">
                    Voyagez entre les villes du Sénégal,
                    <span class="text-primary">simplement.</span>
                </h2>
                <ul class="space-y-5">
                    <li
                        v-for="item in highlights"
                        :key="item.title"
                        class="flex items-start gap-4"
                    >
                        <span
                            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="font-semibold">{{ item.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ item.text }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-sm text-muted-foreground">
                © {{ new Date().getFullYear() }} Bokko Tuki
            </p>
        </div>

        <!-- Panneau du formulaire -->
        <div class="relative flex flex-col items-center justify-center bg-background p-6 md:p-10">
            <!-- Retour accueil (mobile / tablette) -->
            <Link
                :href="home()"
                class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full border border-border/60 px-3 py-1.5 text-sm font-medium text-foreground/70 transition hover:bg-muted hover:text-primary lg:hidden"
            >
                <ArrowLeft class="h-4 w-4" />
                Accueil
            </Link>

            <div class="w-full max-w-sm space-y-8">
                <div class="flex flex-col items-center gap-2 text-center lg:items-start lg:text-left">
                    <Link :href="home()" class="mb-2 lg:hidden">
                        <BrandLogo class="h-20 w-auto" />
                    </Link>
                    <h1 v-if="title" class="text-2xl font-bold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
