<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Mail, MapPin, MessageCircle, Phone } from '@lucide/vue';
import { computed } from 'vue';
import HeartPattern from '@/components/site/HeartPattern.vue';
import { home } from '@/routes';
import type { Business } from '@/types';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const business = computed(() => page.props.business as Business);
const supportWhatsapp = '523315314045';
const supportWhatsappLabel = '+52 33 1531 4045';
const whatsappUrl = computed(() => `https://wa.me/${supportWhatsapp}`);
</script>

<template>
    <div class="flex min-h-svh bg-background text-foreground">
        <!-- Panel de marca -->
        <aside
            class="relative hidden w-[46%] shrink-0 flex-col justify-between overflow-hidden bg-brand p-10 text-white lg:flex xl:p-14"
        >
            <HeartPattern
                class="pointer-events-none absolute inset-0 text-white/[0.08]"
                style="
                    mask-image: linear-gradient(200deg, black, transparent 85%);
                    -webkit-mask-image: linear-gradient(
                        200deg,
                        black,
                        transparent 85%
                    );
                "
            />
            <div
                class="pointer-events-none absolute -top-24 -right-20 size-80 rounded-full bg-bronze/25 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-28 -left-24 size-72 rounded-full bg-bronze/15 blur-3xl"
            />

            <Link :href="home()" class="relative flex items-center gap-3">
                <span
                    class="size-14 shrink-0 overflow-hidden rounded-full ring-1 ring-white/30"
                >
                    <img
                        src="/images/logo.png"
                        :alt="business.name"
                        class="size-full object-cover"
                    />
                </span>
                <span>
                    <span
                        class="block text-sm font-semibold tracking-[0.22em] uppercase"
                    >
                        {{ business.name }}
                    </span>
                    <span class="block text-xs text-white/70">
                        {{ business.tagline }}
                    </span>
                </span>
            </Link>

            <div class="relative max-w-md">
                <p
                    class="text-xs font-semibold tracking-[0.3em] text-bronze uppercase"
                >
                    Bienvenida de nuevo
                </p>
                <p
                    class="mt-5 text-3xl leading-snug font-semibold text-balance xl:text-4xl"
                >
                    {{ business.philosophy }}
                </p>
                <p class="mt-4 text-sm leading-relaxed text-white/70">
                    Administra la agenda, confirma citas y da seguimiento a cada
                    paciente desde un solo lugar.
                </p>
            </div>

            <div class="relative space-y-3 text-sm text-white/75">
                <a
                    :href="`tel:${business.phone.replace(/\s/g, '')}`"
                    class="flex items-center gap-3 transition hover:text-white"
                >
                    <Phone class="size-4 shrink-0 text-bronze" />
                    {{ business.phone }}
                </a>
                <a
                    :href="`mailto:${business.email}`"
                    class="flex items-center gap-3 transition hover:text-white"
                >
                    <Mail class="size-4 shrink-0 text-bronze" />
                    {{ business.email }}
                </a>
                <p class="flex items-start gap-3">
                    <MapPin class="mt-0.5 size-4 shrink-0 text-bronze" />
                    <span>{{ business.address.full }}</span>
                </p>
                <Link
                    :href="home()"
                    class="mt-6 inline-flex items-center gap-2 rounded-full border border-white/25 px-4 py-2 text-xs font-semibold tracking-widest uppercase transition hover:border-bronze hover:text-bronze"
                >
                    <ArrowLeft class="size-4" />
                    Volver al sitio
                </Link>
            </div>
        </aside>

        <!-- Formulario -->
        <div class="flex w-full min-w-0 flex-1 flex-col">
            <div
                class="flex items-center justify-between gap-4 border-b border-border/70 px-6 py-4 lg:hidden"
            >
                <Link :href="home()" class="flex items-center gap-2.5">
                    <span
                        class="size-10 shrink-0 overflow-hidden rounded-full ring-1 ring-brand/15"
                    >
                        <img
                            src="/images/logo.png"
                            :alt="business.name"
                            class="size-full object-cover"
                        />
                    </span>
                    <span>
                        <span class="block text-sm font-semibold">
                            {{ business.name }}
                        </span>
                        <span class="block text-[11px] text-muted-foreground">
                            {{ business.tagline }}
                        </span>
                    </span>
                </Link>
                <Link
                    :href="home()"
                    class="text-xs font-semibold text-primary/80 hover:text-primary"
                >
                    Volver al sitio
                </Link>
            </div>

            <div
                class="flex flex-1 items-center justify-center px-6 py-12 sm:px-10"
            >
                <div class="w-full max-w-sm">
                    <div class="space-y-2">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <div class="mt-8">
                        <slot />
                    </div>
                </div>
            </div>

            <div
                class="border-t border-border/70 px-6 py-4 text-center text-xs text-muted-foreground sm:px-10"
            >
                <a
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 font-medium text-primary/80 transition hover:text-primary"
                >
                    <MessageCircle class="size-3.5" />
                    ¿Problemas para iniciar sesión? Escríbenos por WhatsApp al
                    {{ supportWhatsappLabel }}
                </a>
            </div>
        </div>
    </div>
</template>
