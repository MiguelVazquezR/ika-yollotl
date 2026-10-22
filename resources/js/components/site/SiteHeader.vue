<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, MessageCircle, Phone, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import { login } from '@/routes';
import { index as appointmentsIndex } from '@/routes/admin/appointments';
import type { Business } from '@/types';

defineProps<{
    business: Business;
}>();

const page = usePage();
const isAdmin = computed(() => Boolean(page.props.auth.user));

const menuOpen = ref(false);

const navigation = [
    { label: 'Inicio', href: '#inicio' },
    { label: 'Servicios', href: '#servicios' },
    { label: 'Agenda', href: '#agenda' },
    { label: 'Horarios', href: '#horarios' },
    { label: 'Ubicación', href: '#ubicacion' },
];

function closeMenu(): void {
    menuOpen.value = false;
}
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-brand/10 bg-sand/90 backdrop-blur-md"
    >
        <div
            class="mx-auto flex h-18 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
        >
            <a
                href="#inicio"
                class="flex items-center gap-3"
                @click="closeMenu"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-full shadow-sm ring-1 ring-brand/15"
                >
                    <img
                        src="/images/logo.png"
                        :alt="business.name"
                        class="size-full object-cover"
                    />
                </span>
                <span class="leading-tight">
                    <span class="block text-base font-semibold tracking-tight">
                        {{ business.name }}
                    </span>
                    <span
                        class="block text-xs font-medium tracking-wide text-brand/70 uppercase"
                    >
                        {{ business.tagline }}
                    </span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 lg:flex">
                <a
                    v-for="item in navigation"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-full px-3 py-2 text-sm font-medium text-brand/80 transition-colors hover:bg-brand/5 hover:text-brand"
                >
                    {{ item.label }}
                </a>
            </nav>

            <div class="hidden items-center gap-2 sm:flex">
                <a
                    :href="`tel:${business.phone.replace(/\s/g, '')}`"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-brand/80 transition-colors hover:bg-brand/5 hover:text-brand"
                >
                    <Phone class="size-4" />
                    <span class="hidden md:inline">{{ business.phone }}</span>
                </a>

                <Link
                    v-if="isAdmin"
                    :href="appointmentsIndex()"
                    :class="buttonVariants({ variant: 'outline', size: 'sm' })"
                >
                    Agenda
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="text-xs font-medium text-brand/60 transition-colors hover:text-brand"
                >
                    Acceso equipo
                </Link>

                <a
                    href="#agenda"
                    :class="buttonVariants({ size: 'sm' })"
                    class="bg-brand text-white hover:bg-brand/90"
                >
                    <MessageCircle class="size-4" />
                    Agendar cita
                </a>
            </div>

            <button
                type="button"
                class="inline-flex size-10 items-center justify-center rounded-xl border border-brand/15 text-brand lg:hidden"
                :aria-label="menuOpen ? 'Cerrar menú' : 'Abrir menú'"
                @click="menuOpen = !menuOpen"
            >
                <X v-if="menuOpen" class="size-5" />
                <Menu v-else class="size-5" />
            </button>
        </div>

        <div
            v-if="menuOpen"
            class="border-t border-brand/10 bg-sand px-4 pt-3 pb-5 lg:hidden"
        >
            <nav class="flex flex-col gap-1">
                <a
                    v-for="item in navigation"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-lg px-3 py-2.5 text-sm font-medium text-brand/80 hover:bg-brand/5"
                    @click="closeMenu"
                >
                    {{ item.label }}
                </a>
                <Link
                    v-if="isAdmin"
                    :href="appointmentsIndex()"
                    class="rounded-lg px-3 py-2.5 text-sm font-medium text-brand/80 hover:bg-brand/5"
                    @click="closeMenu"
                >
                    Agenda
                </Link>
                <Link
                    v-else
                    :href="login()"
                    class="rounded-lg px-3 py-2.5 text-sm font-medium text-brand/80 hover:bg-brand/5"
                    @click="closeMenu"
                >
                    Acceso equipo
                </Link>
            </nav>

            <div class="mt-4 grid gap-2">
                <a
                    href="#agenda"
                    :class="buttonVariants({ size: 'lg' })"
                    class="w-full bg-brand text-white hover:bg-brand/90"
                    @click="closeMenu"
                >
                    Agendar cita
                </a>
                <a
                    :href="`tel:${business.phone.replace(/\s/g, '')}`"
                    :class="buttonVariants({ variant: 'outline', size: 'lg' })"
                    class="w-full"
                >
                    <Phone class="size-4" />
                    {{ business.phone }}
                </a>
            </div>
        </div>
    </header>
</template>
