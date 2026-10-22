<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, MapPin, MessageCircle, Phone } from '@lucide/vue';
import { computed } from 'vue';
import { formatTime } from '@/lib/dates';
import { index as appointmentsIndex } from '@/routes/admin/appointments';
import type { Business } from '@/types';

const props = defineProps<{
    business: Business;
}>();

const page = usePage();
const isAdmin = computed(() => Boolean(page.props.auth.user));
const year = new Date().getFullYear();

const whatsappUrl = computed(
    () =>
        `https://wa.me/${props.business.whatsapp}?text=${encodeURIComponent(
            `Hola ${props.business.name}, me gustaría agendar una cita.`,
        )}`,
);
</script>

<template>
    <footer class="bg-brand text-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full ring-1 ring-white/20"
                        >
                            <img
                                src="/images/logo.png"
                                :alt="business.name"
                                class="size-full object-cover"
                            />
                        </span>
                        <div class="leading-tight">
                            <p class="text-lg font-semibold">
                                {{ business.name }}
                            </p>
                            <p
                                class="text-xs font-medium tracking-wide text-white/60 uppercase"
                            >
                                {{ business.tagline }}
                            </p>
                        </div>
                    </div>

                    <p
                        class="mt-5 max-w-md text-sm leading-relaxed text-white/70"
                    >
                        {{ business.philosophy }} Cada tratamiento se diseña
                        contigo: escuchamos tu historia, evaluamos tu movimiento
                        y te acompañamos hasta recuperar tu bienestar.
                    </p>

                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-6 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-brand transition-colors hover:bg-white/90"
                    >
                        <MessageCircle class="size-4" />
                        Escríbenos por WhatsApp
                    </a>
                </div>

                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-white/50 uppercase"
                    >
                        Navegación
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-white/75">
                        <li>
                            <a class="hover:text-white" href="#servicios"
                                >Servicios</a
                            >
                        </li>
                        <li>
                            <a class="hover:text-white" href="#agenda"
                                >Agendar cita</a
                            >
                        </li>
                        <li>
                            <a class="hover:text-white" href="#horarios"
                                >Horarios</a
                            >
                        </li>
                        <li>
                            <a class="hover:text-white" href="#ubicacion"
                                >Ubicación</a
                            >
                        </li>
                        <li>
                            <Link
                                v-if="isAdmin"
                                class="hover:text-white"
                                :href="appointmentsIndex()"
                            >
                                Agenda
                            </Link>
                        </li>
                    </ul>
                </div>

                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-white/50 uppercase"
                    >
                        Contacto
                    </p>
                    <ul class="mt-4 space-y-3 text-sm text-white/75">
                        <li class="flex gap-2">
                            <MapPin
                                class="mt-0.5 size-4 shrink-0 text-white/50"
                            />
                            <span>{{ business.address.full }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <Phone class="size-4 shrink-0 text-white/50" />
                            <a
                                class="hover:text-white"
                                :href="`tel:${business.phone.replace(/\s/g, '')}`"
                            >
                                {{ business.phone }}
                            </a>
                        </li>
                        <li class="flex items-center gap-2">
                            <Mail class="size-4 shrink-0 text-white/50" />
                            <a
                                class="hover:text-white"
                                :href="`mailto:${business.email}`"
                            >
                                {{ business.email }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    © {{ year }} {{ business.name }}. Todos los derechos
                    reservados.
                </p>
                <p>
                    {{ business.hours.days }} ·
                    {{ formatTime(business.hours.open) }} a
                    {{ formatTime(business.hours.close) }} ·
                    {{ business.hours.note }}
                </p>
            </div>
        </div>
    </footer>
</template>
