<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Camera, CheckCircle2, ScanLine, X } from '@lucide/vue';
import { onBeforeUnmount, ref, useTemplateRef } from 'vue';
import BoardingController from '@/actions/App/Http/Controllers/Driver/BoardingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({ layout: { breadcrumbs: [] } });

const form = useForm<{ reference: string; trip_id: number | null }>({ reference: '', trip_id: null });

const video = useTemplateRef<HTMLVideoElement>('video');
const scanning = ref(false);
const scanError = ref<string | null>(null);

// The native BarcodeDetector (Chrome / Android) lets us read a QR with no
// external dependency. When unavailable, the driver simply types the reference.
const scanSupported = typeof window !== 'undefined' && 'BarcodeDetector' in window;

let stream: MediaStream | null = null;
let frame = 0;
let detector: { detect: (source: HTMLVideoElement) => Promise<{ rawValue: string }[]> } | null = null;

const stopScan = () => {
    scanning.value = false;

    if (frame) {
        cancelAnimationFrame(frame);
        frame = 0;
    }

    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
};

const submit = () => {
    stopScan();
    form.post(BoardingController.store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset('reference'),
    });
};

const tick = async () => {
    if (!scanning.value || !video.value || !detector) {
        return;
    }

    try {
        const codes = await detector.detect(video.value);

        if (codes.length > 0 && codes[0].rawValue) {
            form.reference = codes[0].rawValue;
            submit();

            return;
        }
    } catch {
        // Ignore transient detection errors and keep scanning.
    }

    frame = requestAnimationFrame(tick);
};

const startScan = async () => {
    scanError.value = null;

    if (!scanSupported) {
        scanError.value = "Le scan n'est pas disponible sur cet appareil. Saisissez la référence.";

        return;
    }

    try {
        const BarcodeDetector = (window as unknown as { BarcodeDetector: new (options: { formats: string[] }) => typeof detector }).BarcodeDetector;
        detector = new BarcodeDetector({ formats: ['qr_code', 'code_128'] });
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });

        if (video.value) {
            video.value.srcObject = stream;
            await video.value.play();
        }

        scanning.value = true;
        frame = requestAnimationFrame(tick);
    } catch {
        scanError.value = "Impossible d'accéder à la caméra. Saisissez la référence.";
        stopScan();
    }
};

onBeforeUnmount(stopScan);
</script>

<template>
    <Head title="Embarquement" />

    <div class="flex flex-col gap-5">
        <div>
            <h1 class="text-xl font-bold">Valider un embarquement</h1>
            <p class="mt-1 text-sm text-muted-foreground">Scannez le QR code du passager ou saisissez sa référence.</p>
        </div>

        <!-- Scanner -->
        <div class="overflow-hidden rounded-2xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div class="relative flex aspect-square items-center justify-center bg-black/90">
                <video ref="video" class="h-full w-full object-cover" :class="{ hidden: !scanning }" playsinline muted />
                <div v-if="!scanning" class="flex flex-col items-center gap-3 text-center text-white/80">
                    <ScanLine class="h-12 w-12" />
                    <p class="px-6 text-sm">Positionnez le QR code dans le cadre</p>
                </div>
                <div v-if="scanning" class="pointer-events-none absolute inset-8 rounded-xl border-2 border-white/80" />
            </div>
            <div class="flex items-center justify-center gap-2 p-3">
                <Button v-if="!scanning" variant="outline" class="w-full" @click="startScan">
                    <Camera class="h-4 w-4" /> Démarrer le scan
                </Button>
                <Button v-else variant="outline" class="w-full" @click="stopScan">
                    <X class="h-4 w-4" /> Arrêter
                </Button>
            </div>
            <p v-if="scanError" class="px-4 pb-3 text-sm text-amber-600">{{ scanError }}</p>
        </div>

        <!-- Manual reference entry -->
        <form class="flex flex-col gap-3 rounded-2xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="submit">
            <label for="reference" class="text-sm font-medium">Référence de réservation</label>
            <Input
                id="reference"
                v-model="form.reference"
                placeholder="AD-XXXXXXXX"
                autocapitalize="characters"
                autocomplete="off"
                class="h-12 text-center font-mono text-lg tracking-wider uppercase"
            />
            <InputError :message="form.errors.reference" />
            <Button type="submit" size="lg" class="h-12 w-full text-base" :disabled="form.processing || !form.reference">
                <CheckCircle2 class="h-5 w-5" /> Confirmer l'embarquement
            </Button>
        </form>
    </div>
</template>
