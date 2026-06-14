<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import { priorityColor, statusColor, useTicketLabels } from '@/utils/ticketLabels';
import { useTrans } from '@/composables/useTrans';
import { useFormat } from '@/composables/useFormat';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    metrics: Object,
    statuses: Array,
});

const { t } = useTrans();
const { formatDateTime } = useFormat();
const { priorityLabels } = useTicketLabels();

const user = computed(() => usePage().props.auth.user);
const isClient = computed(() => user.value?.role === 'cliente');
const isStaff = computed(() => ['agente', 'admin'].includes(user.value?.role));

const statusCount = (value) => props.metrics?.by_status?.[value] ?? 0;
</script>

<template>
    <Head :title="t('nav.home')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('nav.home') }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="isClient" class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-gray-900">{{ t('dashboard.greeting', { name: user.name }) }}</p>
                    <p class="mt-2 text-sm text-gray-600">
                        <Link
                            :href="route('client.tickets.index')"
                            class="text-indigo-600 underline hover:text-indigo-800"
                        >
                            {{ t('dashboard.go_to_my_tickets') }}
                        </Link>
                        ·
                        <Link
                            :href="route('client.tickets.create')"
                            class="text-indigo-600 underline hover:text-indigo-800"
                        >
                            {{ t('dashboard.create_ticket') }}
                        </Link>
                    </p>
                </div>

                <template v-if="isStaff && metrics">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <p class="text-sm text-gray-500">{{ t('dashboard.open_tickets') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">
                                {{ metrics.open_count }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <p class="text-sm text-gray-500">{{ t('dashboard.sla_overdue') }}</p>
                            <p
                                class="mt-1 text-3xl font-semibold"
                                :class="
                                    metrics.sla_overdue_count > 0 ? 'text-red-600' : 'text-gray-900'
                                "
                            >
                                {{ metrics.sla_overdue_count }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <p class="text-sm text-gray-500">{{ t('dashboard.assigned_to_me') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">
                                {{ metrics.my_assigned_count }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-white p-5 shadow-sm">
                            <Link
                                :href="route('panel.tickets.index')"
                                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                {{ t('dashboard.go_to_queue') }} →
                            </Link>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3 class="font-semibold text-gray-900">
                                {{ t('dashboard.by_status') }}
                            </h3>
                            <ul class="mt-4 space-y-2">
                                <li
                                    v-for="status in statuses"
                                    :key="status.value"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="flex items-center gap-2">
                                        <Badge
                                            :label="status.label"
                                            :color="statusColor(status.value)"
                                        />
                                    </span>
                                    <span class="font-medium">{{ statusCount(status.value) }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3 class="font-semibold text-gray-900">
                                {{ t('dashboard.by_department') }}
                            </h3>
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500">
                                            <th class="pb-2">{{ t('dashboard.department') }}</th>
                                            <th class="pb-2 text-right">
                                                {{ t('dashboard.open') }}
                                            </th>
                                            <th class="pb-2 text-right">
                                                {{ t('dashboard.overdue') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="dept in metrics.by_department" :key="dept.id">
                                            <td class="py-2 font-medium text-gray-900">
                                                {{ dept.name }}
                                            </td>
                                            <td class="py-2 text-right">{{ dept.open_count }}</td>
                                            <td
                                                class="py-2 text-right"
                                                :class="
                                                    dept.overdue_count > 0
                                                        ? 'font-medium text-red-600'
                                                        : ''
                                                "
                                            >
                                                {{ dept.overdue_count }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="metrics.overdue_tickets.length"
                        class="rounded-lg bg-white p-6 shadow-sm"
                    >
                        <h3 class="font-semibold text-gray-900">
                            {{ t('dashboard.overdue_tickets') }}
                        </h3>
                        <ul class="mt-4 divide-y divide-gray-100">
                            <li
                                v-for="ticket in metrics.overdue_tickets"
                                :key="ticket.id"
                                class="flex flex-wrap items-center justify-between gap-2 py-3"
                            >
                                <div>
                                    <Link
                                        :href="route('panel.tickets.show', ticket.id)"
                                        class="font-medium text-indigo-600 hover:text-indigo-800"
                                    >
                                        {{ ticket.number }} · {{ ticket.subject }}
                                    </Link>
                                    <p class="text-xs text-gray-500">
                                        {{ ticket.department?.name }} ·
                                        {{ ticket.assignee?.name || t('common.unassigned') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge
                                        :label="priorityLabels[ticket.priority]"
                                        :color="priorityColor(ticket.priority)"
                                    />
                                    <span class="text-xs text-red-600">
                                        {{ formatDateTime(ticket.due_at) }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
