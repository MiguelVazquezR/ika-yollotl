<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock,
    HeartPulse,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    Sparkles,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { availability } from '@/actions/App/Http/Controllers/AppointmentBookingController';
import BookingDialog from '@/components/site/BookingDialog.vue';
import HeartPattern from '@/components/site/HeartPattern.vue';
import SiteFooter from '@/components/site/SiteFooter.vue';
import SiteHeader from '@/components/site/SiteHeader.vue';
import {
    buildMonthGrid,
    currentMonthKey,
    formatLongDate,
    formatTime,
    monthLabel,
    parseDateKey,
    shiftMonth,
    todayKey,
    weekdayLabels,
} from '@/lib/dates';
import type {
    Availability,
    Business,
    BusinessHour,
    CalendarDay,
    Service,
    Slot,
} from '@/types';

const props = defineProps<{
    business: Business;
    services: Service[];
    businessHours: BusinessHour[];
}>();

const today = todayKey();
const selectedServiceId = ref<number | null>(props.services[0]?.id ?? null);
const month = ref(currentMonthKey());
const openWeekdays = computed(
    () => new Set(props.businessHours.map((hour) => hour.day_of_week)),
);
const selectedDate = ref<string | null>(
    openWeekdays.value.has(parseDateKey(today).getDay()) ? today : null,
);
const availabilityData = ref<Availability | null>(null);
const loadingSlots = ref(false);
const slotError = ref<string | null>(null);
const selectedSlot = ref<Slot | null>(null);
const bookingOpen = ref(false);

const selectedService = computed(
    () =>
        props.services.find(
            (service) => service.id === selectedServiceId.value,
        ) ?? null,
);
const monthDays = computed(() => buildMonthGrid(month.value));
const canGoBack = computed(() => month.value > currentMonthKey());
const slots = computed(() => availabilityData.value?.slots ?? []);
const availableCount = computed(
    () => slots.value.filter((slot) => slot.available).length,
);

function isClosed(key: string): boolean {
    return !openWeekdays.value.has(parseDateKey(key).getDay());
}

function isDisabled(day: CalendarDay): boolean {
    return day.isPast || !day.inMonth || isClosed(day.key);
}

function selectService(id: number): void {
    if (selectedServiceId.value === id) {
        return;
    }

    selectedServiceId.value = id;
    selectedDate.value = null;
    selectedSlot.value = null;
}

function goToAgenda(id: number): void {
    selectService(id);
    document
        .getElementById('agenda')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function selectDay(day: CalendarDay): void {
    if (isDisabled(day)) {
        return;
    }

    selectedDate.value = day.key;
    selectedSlot.value = null;
}

function changeMonth(offset: number): void {
    const next = shiftMonth(month.value, offset);

    if (next < currentMonthKey()) {
        return;
    }

    month.value = next;
}

function openBooking(slot: Slot): void {
    if (!slot.available || !selectedDate.value) {
        return;
    }

    selectedSlot.value = slot;
    bookingOpen.value = true;
}

async function loadAvailability(): Promise<void> {
    if (!selectedServiceId.value || !selectedDate.value) {
        availabilityData.value = null;

        return;
    }

    loadingSlots.value = true;
    slotError.value = null;

    try {
        const url = availability.url({
            query: {
                service_id: selectedServiceId.value,
                date: selectedDate.value,
            },
        });

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('No fue posible consultar la agenda.');
        }

        availabilityData.value = (await response.json()) as Availability;
    } catch {
        availabilityData.value = null;
        slotError.value =
            'No pudimos cargar los horarios disponibles. Intenta nuevamente.';
    } finally {
        loadingSlots.value = false;
    }
}

function handleBooked(): void {
    selectedSlot.value = null;
    void loadAvailability();
}

watch([selectedServiceId, selectedDate], () => void loadAvailability(), {
    immediate: true,
});
</script>

<template>
    <Head :title="`${business.name} · ${business.tagline}`">
        <meta
            name="description"
            :content="`${business.name} — ${business.tagline} en ${business.address.short}. ${business.philosophy}`"
        />
    </Head>

    <div
        class="min-h-screen bg-sand text-brand selection:bg-brand selection:text-white"
    >
        <SiteHeader :business="business" />

        <main>
            <!-- Hero -->
            <section id="inicio" class="relative overflow-hidden">
                <div
                    class="pointer-events-none absolute -top-32 -right-24 size-[28rem] rounded-full bg-brand/10 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -bottom-40 -left-32 size-[26rem] rounded-full bg-bronze/25 blur-3xl"
                />
                <HeartPattern
                    class="pointer-events-none absolute inset-0 text-brand/[0.06]"
                    style="
                        mask-image: linear-gradient(
                            160deg,
                            black,
                            transparent 78%
                        );
                        -webkit-mask-image: linear-gradient(
                            160deg,
                            black,
                            transparent 78%
                        );
                    "
                />

                <div
                    class="relative mx-auto grid max-w-6xl gap-12 px-4 pt-16 pb-20 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:px-8 lg:pt-24"
                >
                    <div>
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-brand/15 bg-white/70 px-3 py-1.5 text-xs font-semibold tracking-wide text-brand/80 uppercase"
                        >
                            <Sparkles class="size-3.5" />
                            Fisioterapia y bienestar en Zapopan
                        </span>

                        <h1
                            class="mt-6 text-4xl leading-[1.1] font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                        >
                            Muévete sin dolor,
                            <span class="text-brand/60"
                                >vive con bienestar.</span
                            >
                        </h1>

                        <p
                            class="mt-6 max-w-xl text-base leading-relaxed text-brand/70 sm:text-lg"
                        >
                            {{ business.philosophy }} En {{ business.name }}
                            combinamos evaluación profesional, terapia manual y
                            ejercicio terapéutico para que recuperes tu
                            movimiento con seguridad.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a
                                href="#agenda"
                                class="inline-flex h-12 items-center gap-2 rounded-full bg-brand px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand/90"
                            >
                                <CalendarDays class="size-4" />
                                Agendar mi cita
                            </a>
                            <a
                                href="#servicios"
                                class="inline-flex h-12 items-center gap-2 rounded-full border border-brand/20 px-6 text-sm font-semibold text-brand transition-colors hover:bg-brand/5"
                            >
                                Ver servicios
                                <ArrowRight class="size-4" />
                            </a>
                        </div>

                        <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4">
                            <div>
                                <dt
                                    class="text-xs font-medium tracking-wide text-brand/50 uppercase"
                                >
                                    Horario
                                </dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    {{ formatTime(business.hours.open) }} –
                                    {{ formatTime(business.hours.close) }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs font-medium tracking-wide text-brand/50 uppercase"
                                >
                                    Días
                                </dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    {{ business.hours.days }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-xs font-medium tracking-wide text-brand/50 uppercase"
                                >
                                    Modalidad
                                </dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    Previa cita
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="relative">
                        <div
                            class="absolute -inset-3 rounded-[2rem] bg-brand/5"
                            aria-hidden="true"
                        />
                        <div
                            class="relative overflow-hidden rounded-[1.75rem] border border-brand/10 bg-white p-6 shadow-[0_24px_70px_-40px_rgba(20,60,50,0.55)] sm:p-8"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex size-12 items-center justify-center rounded-2xl bg-brand text-white"
                                >
                                    <HeartPulse class="size-6" />
                                </span>
                                <div>
                                    <p class="text-sm font-semibold">
                                        Tu primera valoración
                                    </p>
                                    <p class="text-xs text-brand/60">
                                        Incluye exploración y plan de
                                        tratamiento
                                    </p>
                                </div>
                            </div>

                            <ul class="mt-6 space-y-3 text-sm text-brand/75">
                                <li class="flex items-start gap-2.5">
                                    <Check
                                        class="mt-0.5 size-4 shrink-0 text-brand"
                                    />
                                    Terapia manual y agentes físicos
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <Check
                                        class="mt-0.5 size-4 shrink-0 text-brand"
                                    />
                                    Ejercicio terapéutico progresivo
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <Check
                                        class="mt-0.5 size-4 shrink-0 text-brand"
                                    />
                                    Seguimiento y ajustes en cada sesión
                                </li>
                            </ul>

                            <div class="mt-7 space-y-2">
                                <a
                                    :href="`tel:${business.phone.replace(/\s/g, '')}`"
                                    class="flex items-center gap-3 rounded-xl bg-sand/80 px-3 py-2.5 text-sm font-medium transition-colors hover:bg-sand"
                                >
                                    <Phone class="size-4 text-brand/60" />
                                    {{ business.phone }}
                                </a>
                                <a
                                    :href="`https://wa.me/${business.whatsapp}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 rounded-xl bg-sand/80 px-3 py-2.5 text-sm font-medium transition-colors hover:bg-sand"
                                >
                                    <MessageCircle
                                        class="size-4 text-brand/60"
                                    />
                                    WhatsApp directo
                                </a>
                                <a
                                    :href="business.maps.directions_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 rounded-xl bg-sand/80 px-3 py-2.5 text-sm font-medium transition-colors hover:bg-sand"
                                >
                                    <MapPin class="size-4 text-brand/60" />
                                    {{ business.address.short }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Servicios -->
            <section
                id="servicios"
                class="border-y border-brand/10 bg-white/60"
            >
                <div
                    class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
                >
                    <div class="max-w-2xl">
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-brand/50 uppercase"
                        >
                            Servicios
                        </p>
                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                        >
                            Tratamientos diseñados para tu recuperación
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-brand/70">
                            Elige el servicio que necesitas. Cada sesión se
                            adapta a tu valoración inicial, tu ritmo y tus
                            objetivos.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="service in services"
                            :key="service.id"
                            class="group flex flex-col rounded-2xl border border-brand/10 bg-white p-6 transition-shadow hover:shadow-[0_18px_45px_-30px_rgba(20,60,50,0.5)]"
                        >
                            <span
                                class="flex size-11 items-center justify-center rounded-xl bg-brand/10 text-brand"
                            >
                                <HeartPulse class="size-5" />
                            </span>
                            <h3 class="mt-4 text-lg font-semibold">
                                {{ service.name }}
                            </h3>
                            <p
                                class="mt-2 flex-1 text-sm leading-relaxed text-brand/65"
                            >
                                {{ service.description }}
                            </p>

                            <div
                                class="mt-5 flex items-center justify-between border-t border-brand/10 pt-4"
                            >
                                <div class="flex items-center gap-3 text-sm">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-brand/60"
                                    >
                                        <Clock class="size-4" />
                                        {{ service.duration_minutes }} min
                                    </span>
                                    <span class="text-brand/20">|</span>
                                    <span class="font-semibold text-brand">
                                        {{ service.price_label }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-brand transition-colors hover:text-brand/70"
                                    @click="goToAgenda(service.id)"
                                >
                                    Agendar
                                    <ArrowRight class="size-4" />
                                </button>
                            </div>
                        </article>
                    </div>

                    <p
                        v-if="services.length === 0"
                        class="mt-8 rounded-2xl border border-dashed border-brand/20 px-6 py-10 text-center text-sm text-brand/60"
                    >
                        Estamos actualizando nuestro catálogo de servicios.
                        Escríbenos por WhatsApp para más información.
                    </p>
                </div>
            </section>

            <!-- Agenda -->
            <section
                id="agenda"
                class="relative overflow-hidden bg-brand text-white"
            >
                <div
                    class="pointer-events-none absolute top-10 -left-24 size-80 rounded-full bg-white/5 blur-3xl"
                />
                <div
                    class="pointer-events-none absolute -right-20 bottom-0 size-96 rounded-full bg-bronze/10 blur-3xl"
                />

                <div
                    class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20"
                >
                    <div class="max-w-2xl">
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-white/50 uppercase"
                        >
                            Agenda en línea
                        </p>
                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                        >
                            Reserva tu cita en tres pasos
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-white/70">
                            Elige el servicio, el día y el horario que mejor te
                            acomode. Confirmaremos tu cita por WhatsApp.
                        </p>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-2">
                        <button
                            v-for="service in services"
                            :key="service.id"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-colors"
                            :class="
                                selectedServiceId === service.id
                                    ? 'border-white bg-white text-brand'
                                    : 'border-white/25 text-white/80 hover:border-white/50 hover:text-white'
                            "
                            @click="selectService(service.id)"
                        >
                            {{ service.name }}
                            <span
                                class="text-xs"
                                :class="
                                    selectedServiceId === service.id
                                        ? 'text-brand/50'
                                        : 'text-white/40'
                                "
                            >
                                {{ service.duration_minutes }} min
                            </span>
                        </button>
                    </div>

                    <div
                        class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]"
                    >
                        <div
                            class="rounded-3xl bg-white p-5 text-brand shadow-[0_30px_80px_-50px_rgba(0,0,0,0.7)] sm:p-7"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-[11px] font-semibold tracking-[0.18em] text-brand/40 uppercase"
                                    >
                                        Paso 2 · Fecha
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
                                        {{ monthLabel(month) }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="inline-flex size-9 items-center justify-center rounded-full border border-brand/15 transition-colors hover:bg-brand/5 disabled:cursor-not-allowed disabled:opacity-30"
                                        :disabled="!canGoBack"
                                        aria-label="Mes anterior"
                                        @click="changeMonth(-1)"
                                    >
                                        <ChevronLeft class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex size-9 items-center justify-center rounded-full border border-brand/15 transition-colors hover:bg-brand/5"
                                        aria-label="Mes siguiente"
                                        @click="changeMonth(1)"
                                    >
                                        <ChevronRight class="size-4" />
                                    </button>
                                </div>
                            </div>

                            <div
                                class="mt-5 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold tracking-wide text-brand/40 uppercase"
                            >
                                <span
                                    v-for="label in weekdayLabels"
                                    :key="label"
                                >
                                    {{ label }}
                                </span>
                            </div>

                            <div class="mt-2 grid grid-cols-7 gap-1.5">
                                <button
                                    v-for="day in monthDays"
                                    :key="day.key"
                                    type="button"
                                    :disabled="isDisabled(day)"
                                    class="relative flex aspect-square flex-col items-center justify-center rounded-xl text-sm font-medium transition-colors"
                                    :class="[
                                        selectedDate === day.key
                                            ? 'bg-brand text-white shadow-sm'
                                            : isDisabled(day)
                                              ? 'cursor-not-allowed text-brand/25'
                                              : 'text-brand/80 hover:bg-brand/10',
                                        day.isToday && selectedDate !== day.key
                                            ? 'ring-1 ring-brand/30'
                                            : '',
                                    ]"
                                    @click="selectDay(day)"
                                >
                                    {{ day.day }}
                                    <span
                                        v-if="day.isToday"
                                        class="absolute bottom-1.5 size-1 rounded-full"
                                        :class="
                                            selectedDate === day.key
                                                ? 'bg-white'
                                                : 'bg-brand'
                                        "
                                    />
                                </button>
                            </div>

                            <p
                                class="mt-5 flex flex-wrap items-center gap-2 text-xs text-brand/55"
                            >
                                <CalendarDays class="size-4" />
                                {{ business.hours.days }} ·
                                {{ formatTime(business.hours.open) }} a
                                {{ formatTime(business.hours.close) }} ·
                                {{ business.hours.note }}
                            </p>
                        </div>

                        <!-- Horarios -->
                        <aside
                            class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur sm:p-6"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-[0.18em] text-white/50 uppercase"
                            >
                                Paso 3 · Horario
                            </p>
                            <p class="mt-1 text-base font-semibold">
                                {{
                                    selectedDate
                                        ? formatLongDate(selectedDate)
                                        : 'Elige un día'
                                }}
                            </p>
                            <p
                                v-if="selectedService"
                                class="mt-1 text-xs text-white/60"
                            >
                                {{ selectedService.name }} ·
                                {{ selectedService.duration_minutes }} min ·
                                {{ selectedService.price_label }}
                            </p>

                            <div
                                v-if="loadingSlots"
                                class="mt-6 grid grid-cols-3 gap-2"
                            >
                                <div
                                    v-for="index in 6"
                                    :key="index"
                                    class="h-9 animate-pulse rounded-lg bg-white/10"
                                />
                            </div>

                            <p
                                v-else-if="slotError"
                                class="mt-6 rounded-xl bg-white/10 px-3 py-3 text-xs text-white/80"
                            >
                                {{ slotError }}
                            </p>

                            <p
                                v-else-if="!selectedDate"
                                class="mt-6 rounded-xl bg-white/10 px-3 py-3 text-xs text-white/70"
                            >
                                Selecciona un día disponible en el calendario
                                para ver los horarios.
                            </p>

                            <p
                                v-else-if="slots.length === 0"
                                class="mt-6 rounded-xl bg-white/10 px-3 py-3 text-xs text-white/70"
                            >
                                No hay horarios configurados para este día.
                                Elige otra fecha.
                            </p>

                            <template v-else>
                                <div class="mt-5 grid grid-cols-3 gap-2">
                                    <button
                                        v-for="slot in slots"
                                        :key="slot.start_time"
                                        type="button"
                                        :disabled="!slot.available"
                                        class="rounded-lg border px-2 py-2 text-xs font-semibold transition-colors"
                                        :class="
                                            slot.available
                                                ? 'border-white/25 bg-white/5 text-white hover:border-white hover:bg-white hover:text-brand'
                                                : 'cursor-not-allowed border-white/10 text-white/25 line-through'
                                        "
                                        @click="openBooking(slot)"
                                    >
                                        {{ formatTime(slot.start_time) }}
                                    </button>
                                </div>

                                <p class="mt-4 text-xs text-white/60">
                                    {{ availableCount }} horarios disponibles
                                </p>
                            </template>

                            <p
                                class="mt-6 border-t border-white/15 pt-4 text-xs text-white/60"
                            >
                                ¿Prefieres hablar con nosotros?
                                <a
                                    :href="`https://wa.me/${business.whatsapp}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="font-semibold text-white underline decoration-white/30 underline-offset-2 hover:decoration-white"
                                >
                                    Escríbenos por WhatsApp
                                </a>
                            </p>
                        </aside>
                    </div>
                </div>
            </section>

            <!-- Horarios -->
            <section id="horarios">
                <div
                    class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-20"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-brand/50 uppercase"
                        >
                            Horarios
                        </p>
                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                        >
                            Atención personalizada, siempre con cita
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-brand/70">
                            Trabajamos con agenda cerrada para dedicarte el
                            tiempo completo de tu sesión. Reserva en línea y
                            confirma por WhatsApp; si necesitas otro horario,
                            escríbenos y buscamos la mejor opción.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a
                                href="#agenda"
                                class="inline-flex h-11 items-center gap-2 rounded-full bg-brand px-5 text-sm font-semibold text-white transition-colors hover:bg-brand/90"
                            >
                                <CalendarDays class="size-4" />
                                Reservar ahora
                            </a>
                            <a
                                :href="`https://wa.me/${business.whatsapp}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex h-11 items-center gap-2 rounded-full border border-brand/20 px-5 text-sm font-semibold text-brand transition-colors hover:bg-brand/5"
                            >
                                <MessageCircle class="size-4" />
                                Preguntar por WhatsApp
                            </a>
                        </div>
                    </div>

                    <div
                        class="rounded-3xl border border-brand/10 bg-white p-6 shadow-sm sm:p-8"
                    >
                        <p class="text-sm font-semibold">
                            {{ business.hours.days }}
                        </p>
                        <p class="mt-1 text-xs text-brand/60">
                            {{ business.hours.closed }}
                        </p>

                        <ul class="mt-5 divide-y divide-brand/10">
                            <li
                                v-for="hour in businessHours"
                                :key="hour.day_of_week"
                                class="flex items-center justify-between py-3 text-sm"
                            >
                                <span class="font-medium text-brand/80">
                                    {{ hour.day_name }}
                                </span>
                                <span class="text-brand/60">
                                    {{ formatTime(hour.open_time) }} –
                                    {{ formatTime(hour.close_time) }}
                                </span>
                            </li>
                        </ul>

                        <p class="mt-4 text-xs text-brand/50">
                            Sesiones en bloques de
                            {{ businessHours[0]?.slot_interval_minutes ?? 30 }}
                            minutos · {{ business.hours.note }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Ubicación -->
            <section
                id="ubicacion"
                class="border-t border-brand/10 bg-white/60"
            >
                <div
                    class="mx-auto grid max-w-6xl gap-8 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:px-8 lg:py-20"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-brand/50 uppercase"
                        >
                            Ubicación
                        </p>
                        <h2
                            class="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                        >
                            Encuéntranos en Zapopan
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-brand/70">
                            {{ business.address.full }}
                        </p>

                        <div class="mt-6 space-y-2 text-sm">
                            <a
                                :href="`tel:${business.phone.replace(/\s/g, '')}`"
                                class="flex items-center gap-3 rounded-xl bg-sand px-4 py-3 font-medium transition-colors hover:bg-sand/70"
                            >
                                <Phone class="size-4 text-brand/60" />
                                {{ business.phone }}
                            </a>
                            <a
                                :href="`mailto:${business.email}`"
                                class="flex items-center gap-3 rounded-xl bg-sand px-4 py-3 font-medium transition-colors hover:bg-sand/70"
                            >
                                <Mail class="size-4 text-brand/60" />
                                {{ business.email }}
                            </a>
                        </div>

                        <a
                            :href="business.maps.directions_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 inline-flex h-11 items-center gap-2 rounded-full bg-brand px-5 text-sm font-semibold text-white transition-colors hover:bg-brand/90"
                        >
                            <MapPin class="size-4" />
                            Cómo llegar
                        </a>
                    </div>

                    <div
                        class="overflow-hidden rounded-3xl border border-brand/10 bg-white shadow-sm"
                    >
                        <iframe
                            :src="business.maps.embed_url"
                            :title="`Ubicación de ${business.name}`"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            class="h-[22rem] w-full border-0 lg:h-[26rem]"
                        />
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter :business="business" />

        <BookingDialog
            v-model:open="bookingOpen"
            :service="selectedService"
            :date="selectedDate"
            :slot="selectedSlot"
            @booked="handleBooked"
        />
    </div>
</template>
