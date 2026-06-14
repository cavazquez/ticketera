<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const message = computed(() => page.props.flash?.success || page.props.flash?.error);
const type = computed(() => (page.props.flash?.error ? 'error' : 'success'));
</script>

<template>
    <div
        v-if="message"
        class="mb-4 rounded-md px-4 py-3 text-sm"
        :class="type === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'"
    >
        {{ message }}
    </div>
</template>
