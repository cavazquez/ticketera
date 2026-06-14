<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { priorityColor, statusColor, useTicketLabels } from '@/utils/ticketLabels';
import { Head, Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

defineProps({
    tickets: Object,
});

const { t } = useTrans();
const { statusLabels, priorityLabels } = useTicketLabels();
</script>

<template>
    <Head :title="t('client.tickets.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ t('client.tickets.title') }}
                </h2>
                <Link :href="route('client.tickets.create')">
                    <PrimaryButton>{{ t('client.tickets.new') }}</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div v-if="tickets.data.length === 0" class="p-6 text-gray-500">
                        {{ t('client.tickets.empty') }}
                        <Link
                            :href="route('client.tickets.create')"
                            class="ms-2 font-medium text-indigo-600"
                        >
                            {{ t('client.tickets.create_first') }}
                        </Link>
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <Link
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            :href="route('client.tickets.show', ticket.id)"
                            class="block p-6 transition hover:bg-gray-50"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">{{ ticket.number }}</p>
                                    <h3 class="mt-1 text-lg font-medium text-gray-900">
                                        {{ ticket.subject }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ ticket.department?.name }}
                                        <span v-if="ticket.assignee">
                                            · Agente: {{ ticket.assignee.name }}</span
                                        >
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <Badge
                                        :label="statusLabels[ticket.status]"
                                        :color="statusColor(ticket.status)"
                                    />
                                    <Badge
                                        :label="priorityLabels[ticket.priority]"
                                        :color="priorityColor(ticket.priority)"
                                    />
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
