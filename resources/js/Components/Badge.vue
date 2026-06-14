<script setup>
defineProps({
    label: String,
    color: {
        type: String,
        default: 'gray',
    },
});

const colors = {
    blue: 'bg-blue-100 text-blue-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    green: 'bg-green-100 text-green-800',
    gray: 'bg-gray-100 text-gray-800',
    orange: 'bg-orange-100 text-orange-800',
    red: 'bg-red-100 text-red-800',
};
</script>

<template>
    <span
        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="colors[color] || colors.gray"
    >
        {{ label }}
    </span>
</template>
