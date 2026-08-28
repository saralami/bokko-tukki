<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type PaginatorLink = { url: string | null; label: string; active: boolean };

defineProps<{
    links: PaginatorLink[];
    total?: number;
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <span v-if="total !== undefined" class="text-sm text-muted-foreground">{{ total }} résultat(s)</span>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                    :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-sidebar-border/70 hover:bg-muted/50 dark:border-sidebar-border'"
                    preserve-scroll
                >
                    <span v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="rounded-md border border-sidebar-border/70 px-3 py-1.5 text-sm text-muted-foreground/50 dark:border-sidebar-border"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
