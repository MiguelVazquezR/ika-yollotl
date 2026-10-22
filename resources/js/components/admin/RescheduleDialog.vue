<script setup lang="ts">
import { CalendarClock } from '@lucide/vue';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatLongDate, formatTime, todayKey } from '@/lib/dates';
import { reschedule } from '@/routes/admin/appointments';
import { availability } from '@/routes/appointments';
import type { Appointment, Slot } from '@/types';

const props = defineProps<{
    open: boolean;
    appointment: Appointment | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    rescheduled: [appointment: Appointment];
}>();

const date = ref('');
const startTime = ref('');
const slots = ref<Slot[]>([]);
const loadingSlots = ref(false);
const slotError = ref<string | null>(null);
const errors = ref<Record<string, string>>({});
const generalError = ref<string | null>(null);
const processing = ref(false);
const whatsappUrl = ref<string | null>(null);
const successMessage = ref<string | null>(null);

const minDate = todayKey();

function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

function close(): void {
    emit('update:open', false);
}

function reset(): void {
    date.value = props.appointment?.date ?? minDate;
    startTime.value = '';
    slots.value = [];
    slotError.value = null;
    errors.value = {};
    generalError.value = null;
    processing.value = false;
    whatsappUrl.value = null;
    successMessage.value = null;
}

async function loadSlots(): Promise<void> {
    if (!props.appointment || !date.value) {
        return;
    }

    loadingSlots.value = true;
    slotError.value = null;
    slots.value = [];

    try {
        const response = await fetch(
            availability.url({
                query: {
                    service_id: props.appointment.service.id,
                    date: date.value,
                },
            }),
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            throw new Error('availability');
        }

        const payload = (await response.json()) as {
            is_open: boolean;
            slots: Slot[];
        };

        slots.value = payload.slots ?? [];

        if (!payload.is_open) {
            slotError.value =
                'El centro está cerrado ese día. Elige otra fecha.';
        }
    } catch {
        slotError.value = 'No pudimos cargar los horarios disponibles.';
    } finally {
        loadingSlots.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            reset();
            void loadSlots();
        }
    },
);

watch(date, () => {
    if (!props.open) {
        return;
    }

    startTime.value = '';
    void loadSlots();
});

function pickSlot(slot: Slot): void {
    if (!slot.available) {
        return;
    }

    startTime.value = slot.start_time;
    errors.value = {};
}

function normalizeErrors(payload: unknown): Record<string, string> {
    const raw = (payload as { errors?: Record<string, string[] | string> })
        .errors;

    if (!raw) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(raw).map(([key, value]) => [
            key,
            Array.isArray(value) ? (value[0] ?? '') : String(value),
        ]),
    );
}

async function submit(): Promise<void> {
    if (!props.appointment) {
        return;
    }

    if (!startTime.value) {
        errors.value = { start_time: 'Selecciona el nuevo horario.' };

        return;
    }

    processing.value = true;
    errors.value = {};
    generalError.value = null;

    try {
        const response = await fetch(
            reschedule.url({ appointment: props.appointment.id }),
            {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    date: date.value,
                    start_time: startTime.value,
                }),
            },
        );

        const payload = await response.json().catch(() => ({}));

        if (response.status === 422) {
            errors.value = normalizeErrors(payload);

            return;
        }

        if (!response.ok) {
            generalError.value =
                'No pudimos reagendar la cita. Inténtalo de nuevo.';

            return;
        }

        const data = payload as {
            message?: string;
            appointment?: Appointment;
            whatsapp_url?: string | null;
        };

        successMessage.value = data.message ?? 'Cita reagendada.';
        whatsappUrl.value = data.whatsapp_url ?? null;

        if (data.appointment) {
            emit('rescheduled', data.appointment);
        }

        toast.success(successMessage.value);
    } catch {
        generalError.value = 'Revisa tu conexión e inténtalo de nuevo.';
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <CalendarClock class="size-5 text-primary" />
                    Reagendar cita
                </DialogTitle>
                <DialogDescription v-if="appointment">
                    {{ appointment.patient.name }} ·
                    {{ appointment.service.name }}. Actualmente
                    {{ formatLongDate(appointment.date) }} a las
                    {{ formatTime(appointment.start_time) }}.
                </DialogDescription>
            </DialogHeader>

            <div v-if="successMessage" class="space-y-3">
                <p
                    class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200"
                >
                    {{ successMessage }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Comparte el nuevo horario con tu paciente por WhatsApp.
                </p>
            </div>

            <form v-else class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="reschedule-date">Nuevo día</Label>
                    <Input
                        id="reschedule-date"
                        v-model="date"
                        type="date"
                        :min="minDate"
                    />
                    <InputError :message="errors.date" />
                </div>

                <div class="space-y-2">
                    <Label>Horario</Label>
                    <p
                        v-if="loadingSlots"
                        class="text-sm text-muted-foreground"
                    >
                        Cargando horarios…
                    </p>
                    <p
                        v-else-if="slotError"
                        class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200"
                    >
                        {{ slotError }}
                    </p>
                    <p
                        v-else-if="slots.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No hay horarios configurados para este día.
                    </p>
                    <div
                        v-else
                        class="grid max-h-56 grid-cols-3 gap-2 overflow-y-auto pr-1 sm:grid-cols-4"
                    >
                        <button
                            v-for="slot in slots"
                            :key="slot.start_time"
                            type="button"
                            :disabled="!slot.available"
                            class="rounded-md border px-2 py-1.5 text-sm transition-colors"
                            :class="
                                !slot.available
                                    ? 'cursor-not-allowed border-dashed border-border text-muted-foreground/60 line-through'
                                    : startTime === slot.start_time
                                      ? 'border-primary bg-primary font-medium text-primary-foreground'
                                      : 'border-input bg-background hover:border-primary/60 hover:bg-primary/5'
                            "
                            @click="pickSlot(slot)"
                        >
                            {{ formatTime(slot.start_time) }}
                        </button>
                    </div>
                    <InputError :message="errors.start_time" />
                </div>

                <p
                    v-if="generalError"
                    class="rounded-lg bg-destructive/10 px-3 py-2 text-sm text-destructive"
                >
                    {{ generalError }}
                </p>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="outline" @click="close">
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        class="bg-primary text-primary-foreground hover:bg-primary/90"
                        :disabled="processing || loadingSlots"
                    >
                        {{ processing ? 'Guardando…' : 'Confirmar cambio' }}
                    </Button>
                </DialogFooter>
            </form>

            <DialogFooter v-if="successMessage" class="gap-2">
                <Button variant="outline" @click="close">Cerrar</Button>
                <Button
                    v-if="whatsappUrl"
                    as-child
                    class="bg-[#1faa54] text-white hover:bg-[#199447]"
                >
                    <a :href="whatsappUrl" target="_blank" rel="noreferrer">
                        Enviar aviso por WhatsApp
                    </a>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
