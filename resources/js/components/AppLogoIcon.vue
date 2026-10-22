<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/images/logo.png"
        alt=""
        aria-hidden="true"
        :class="['rounded-full object-cover', className]"
        v-bind="$attrs"
    />
</template>
