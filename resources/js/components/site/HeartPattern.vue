<script setup lang="ts">
import { useId } from 'vue';

/**
 * Tiled heart pattern used as a decorative background.
 * The hearts inherit `currentColor`, so the layer is tinted
 * through Tailwind text-* utilities by the parent element.
 */
const patternId = `ika-hearts-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`;

const heartPath =
    'M12 21s-7.2-4.6-9.3-9A5.6 5.6 0 0 1 12 5.4a5.6 5.6 0 0 1 9.3 6.6c-2.1 4.4-9.3 9-9.3 9Z';
</script>

<template>
    <svg
        aria-hidden="true"
        focusable="false"
        preserveAspectRatio="xMidYMid slice"
        xmlns="http://www.w3.org/2000/svg"
        class="h-full w-full"
    >
        <defs>
            <pattern
                :id="patternId"
                width="72"
                height="72"
                patternUnits="userSpaceOnUse"
                patternTransform="rotate(-6)"
            >
                <g fill="currentColor">
                    <path :d="heartPath" transform="translate(6 8)" />
                    <path
                        :d="heartPath"
                        transform="translate(42 40) scale(0.62)"
                        opacity="0.65"
                    />
                    <circle cx="62" cy="14" r="1.6" opacity="0.5" />
                </g>
            </pattern>
        </defs>
        <rect width="100%" height="100%" :fill="`url(#${patternId})`" />
    </svg>
</template>
