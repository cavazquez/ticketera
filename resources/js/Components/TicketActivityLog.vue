<script setup>
defineProps({
    activities: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold text-gray-900">Historial de cambios</h3>

        <p v-if="!activities.length" class="text-sm text-gray-500">
            Todavía no hay cambios registrados.
        </p>

        <ul v-else class="space-y-3">
            <li
                v-for="activity in activities"
                :key="activity.id"
                class="border-b border-gray-100 pb-3 last:border-0 last:pb-0"
            >
                <p class="text-sm font-medium text-gray-900">
                    {{ activity.field_label }}
                </p>
                <p class="text-sm text-gray-600">{{ activity.change }}</p>
                <p class="mt-1 text-xs text-gray-500">
                    {{ activity.user_name }} ·
                    {{ new Date(activity.created_at).toLocaleString('es-AR') }}
                </p>
            </li>
        </ul>
    </div>
</template>
