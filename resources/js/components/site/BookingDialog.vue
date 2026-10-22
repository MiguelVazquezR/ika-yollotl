<script setup lang="ts">
import { CircleCheck, Clock, MessageCircle } from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import { store } from '@/actions/App/Http/Controllers/AppointmentBookingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatLongDate, formatTimeRange } from '@/lib/dates';
import type { Service, Slot } from '@/types';

const props = defineProps<{
    open: boolean;
    service: Service | null;
    date: string | null;
    slot: Slot | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    booked: [];
}>();

const form = reactive({
    patient_name: '',
    patient_phone: '',
    patient_email: '',
    notes: '',
});

const errors = ref<Record<string, string>>({});
const generalError = ref<string | null>(null);
const processing = ref(false);
const whatsappUrl = ref<string | null>(null);
const successMessage = ref<string | null>(null);

function reset(): void {
    form.patient_name = '';
    form.patient_phone = '';
    form.patient_email = '';
    form.notes = '';
    errors.value = {};
    generalError.value = null;
    whatsappUrl.value = null;
    successMessage.value = null;
    processing.value = false;
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            reset();
        }
    },
);

function close(): void {
    emit('update:open', false);
}

async function submit(): Promise<void> {
    if (!props.service || !props.date || !props.slot) {
        return;
    }

    processing.value = true;
    errors.value = {};
    generalError.value = null;

    const token =
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? '';

    try {
        const response = await fetch(store.url(), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                service_id: props.service.id,
                date: props.date,
                start_time: props.slot.start_time,
                patient_name: form.patient_name,
                patient_phone: form.patient_phone,
                patient_email: form.patient_email || null,
                notes: form.notes || null,
            }),
        });

        const payload = await response.json().catch(() => ({}));

        if (response.status === 422) {
            const validationErrors = (payload.errors ?? {}) as Record<
                string,
                string[] | string
            >;

            errors.value = Object.fromEntries(
                Object.entries(validationErrors).map(([key, value]) => [
                    key,
                    Array.isArray(value) ? (value[0] ?? '') : String(value),
                ]),
            );

            return;
        }

        if (!response.ok) {
            generalError.value =
                (payload.message as string | undefined) ??
                'No pudimos registrar tu cita. Intenta nuevamente.';

            return;
        }

        whatsappUrl.value =
            (payload.whatsapp_url as string | undefined) ?? null;
        successMessage.value = (payload.message as string | undefined) ?? null;
        emit('booked');
    } catch {
        generalError.value =
            'Revisa tu conexión a internet e intenta nuevamente.';
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-h-[92vh] overflow-y-auto border-brand/10 bg-white p-0 text-brand [--muted-foreground:hsl(329_13%_40%)] sm:max-w-xl"
        >
            <template v-if="whatsappUrl">
                <div class="p-6 sm:p-8">
                    <div
                        class="flex size-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"
                    >
                        <CircleCheck class="size-7" />
                    </div>
                    <h2 class="mt-5 text-2xl font-semibold tracking-tight">
                        ¡Solicitud recibida!
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{
                            successMessage ??
                            'Te contactaremos por WhatsApp para confirmar tu cita.'
                        }}
                    </p>

                    <div
                        class="mt-6 rounded-2xl border border-brand/10 bg-sand/70 p-4 text-sm"
                    >
                        <p class="font-semibold text-brand">
                            {{ service?.name }}
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            {{ date ? formatLongDate(date) : '' }}
                        </p>
                        <p
                            v-if="slot"
                            class="mt-1 flex items-center gap-1.5 text-muted-foreground"
                        >
                            <Clock class="size-4" />
                            {{
                                formatTimeRange(slot.start_time, slot.end_time)
                            }}
                        </p>
                    </div>

                    <div class="mt-6 grid gap-2 sm:grid-cols-2">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#1faa54] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#199447]"
                        >
                            <MessageCircle class="size-4" />
                            Confirmar por WhatsApp
                        </a>
                        <Button variant="outline" class="h-10" @click="close">
                            Agendar otra cita
                        </Button>
                    </div>
                </div>
            </template>

            <template v-else>
                <DialogHeader class="border-b border-brand/10 px-6 py-5">
                    <DialogTitle class="text-lg">Agenda tu cita</DialogTitle>
                    <DialogDescription>
                        Déjanos tus datos y te confirmamos por WhatsApp.
                    </DialogDescription>
                </DialogHeader>

                <div class="px-6 pb-6">
                    <div
                        v-if="service && date && slot"
                        class="mt-4 rounded-2xl border border-brand/10 bg-sand/70 p-4"
                    >
                        <p class="text-sm font-semibold text-brand">
                            {{ service.name }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ formatLongDate(date) }}
                        </p>
                        <p
                            class="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground"
                        >
                            <Clock class="size-4" />
                            {{
                                formatTimeRange(slot.start_time, slot.end_time)
                            }}
                            <span class="text-brand/30">·</span>
                            {{ service.duration_minutes }} min
                            <span class="text-brand/30">·</span>
                            {{ service.price_label }}
                        </p>
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="patient_name">Nombre completo</Label>
                            <Input
                                id="patient_name"
                                v-model="form.patient_name"
                                name="patient_name"
                                autocomplete="name"
                                placeholder="María Fernanda López"
                                :aria-invalid="Boolean(errors.patient_name)"
                                required
                            />
                            <InputError :message="errors.patient_name" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="patient_phone">WhatsApp</Label>
                                <Input
                                    id="patient_phone"
                                    v-model="form.patient_phone"
                                    name="patient_phone"
                                    type="tel"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    placeholder="33 1234 5678"
                                    :aria-invalid="
                                        Boolean(errors.patient_phone)
                                    "
                                    required
                                />
                                <InputError :message="errors.patient_phone" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="patient_email">
                                    Correo
                                    <span
                                        class="font-normal text-muted-foreground"
                                    >
                                        (opcional)
                                    </span>
                                </Label>
                                <Input
                                    id="patient_email"
                                    v-model="form.patient_email"
                                    name="patient_email"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="tucorreo@ejemplo.com"
                                    :aria-invalid="
                                        Boolean(errors.patient_email)
                                    "
                                />
                                <InputError :message="errors.patient_email" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="notes">
                                ¿Algo que debamos saber?
                                <span class="font-normal text-muted-foreground">
                                    (opcional)
                                </span>
                            </Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                name="notes"
                                rows="3"
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                                placeholder="Molestias, lesiones previas, indicaciones médicas..."
                            />
                            <InputError :message="errors.notes" />
                        </div>

                        <p
                            v-if="generalError"
                            class="rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive"
                        >
                            {{ generalError }}
                        </p>

                        <Button
                            type="submit"
                            size="lg"
                            class="w-full bg-brand text-white hover:bg-brand/90"
                            :disabled="processing"
                        >
                            {{ processing ? 'Enviando...' : 'Solicitar cita' }}
                        </Button>

                        <p class="text-center text-xs text-muted-foreground">
                            Al enviar aceptas que te contactemos por WhatsApp
                            para confirmar tu cita.
                        </p>
                    </form>
                </div>
            </template>
        </DialogContent>
    </Dialog>
</template>
