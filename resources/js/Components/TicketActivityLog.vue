<script setup>
import { useTrans } from '@/composables/useTrans';
import { useFormat } from '@/composables/useFormat';

defineProps({
    activities: {
        type: Array,
        default: () => [],
    },
});

const { t } = useTrans();
const { formatDateTime } = useFormat();
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold text-gray-900">{{ t('activity.title') }}</h3>

        <p v-if="!activities.length" class="text-sm text-gray-500">
            {{ t('activity.empty') }}
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
                    {{ formatDateTime(activity.created_at) }}
                </p>
            </li>
        </ul>
    </div>
</template>
