<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AgentCombobox from '@/Components/AgentCombobox.vue';
import TextInput from '@/Components/TextInput.vue';
import { priorityColor, statusColor, useTicketLabels } from '@/utils/ticketLabels';
import { useTrans } from '@/composables/useTrans';
import { useFormat } from '@/composables/useFormat';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const { t } = useTrans();
const { formatDateTime } = useFormat();
const { statusLabels, priorityLabels } = useTicketLabels();

const props = defineProps({
    tickets: Object,
    filters: Object,
    departments: Array,
    selectedAgent: Object,
    statuses: Array,
    priorities: Array,
});

const localFilters = reactive({
    status: props.filters.status || '',
    department_id: props.filters.department_id || '',
    priority: props.filters.priority || '',
    assigned_to: props.filters.assigned_to || '',
    search: props.filters.search || '',
});

const hasActiveFilters = computed(() => Object.values(localFilters).some((value) => value !== ''));

const applyFilters = () => {
    router.get(route('panel.tickets.index'), localFilters, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    localFilters.status = '';
    localFilters.department_id = '';
    localFilters.priority = '';
    localFilters.assigned_to = '';
    localFilters.search = '';
    applyFilters();
};

let searchTimeout = null;

watch(
    () => [
        localFilters.status,
        localFilters.department_id,
        localFilters.priority,
        localFilters.assigned_to,
    ],
    applyFilters
);

watch(
    () => localFilters.search,
    () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 350);
    }
);
</script>

<template>
    <Head :title="t('nav.ticket_queue')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ t('nav.ticket_queue') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <TextInput
                            v-model="localFilters.search"
                            :placeholder="t('panel.tickets.search_placeholder')"
                        />
                        <select
                            v-model="localFilters.status"
                            class="rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">{{ t('panel.tickets.all_statuses') }}</option>
                            <option
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>
                        <select
                            v-model="localFilters.department_id"
                            class="rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">{{ t('panel.tickets.all_departments') }}</option>
                            <option
                                v-for="department in departments"
                                :key="department.id"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                        <select
                            v-model="localFilters.priority"
                            class="rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">{{ t('panel.tickets.all_priorities') }}</option>
                            <option
                                v-for="priority in priorities"
                                :key="priority.value"
                                :value="priority.value"
                            >
                                {{ priority.label }}
                            </option>
                        </select>
                        <AgentCombobox
                            v-model="localFilters.assigned_to"
                            :selected-agent="selectedAgent"
                        />
                        <div class="flex items-center">
                            <SecondaryButton
                                v-if="hasActiveFilters"
                                type="button"
                                @click="clearFilters"
                            >
                                {{ t('panel.tickets.clear_filters') }}
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div v-if="tickets.data.length === 0" class="p-6 text-gray-500">
                        {{ t('panel.tickets.none_with_filters') }}
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <Link
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            :href="route('panel.tickets.show', ticket.id)"
                            class="block p-6 transition hover:bg-gray-50"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">{{ ticket.number }}</p>
                                    <h3 class="mt-1 text-lg font-medium text-gray-900">
                                        {{ ticket.subject }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ t('panel.tickets.client') }}: {{ ticket.user?.name }} ·
                                        {{ ticket.department?.name }}
                                        <span v-if="ticket.assignee">
                                            · {{ t('panel.tickets.assigned') }}:
                                            {{ ticket.assignee.name }}</span
                                        >
                                        <span v-else> · {{ t('common.unassigned') }}</span>
                                        <span
                                            v-if="ticket.due_at"
                                            :class="
                                                new Date(ticket.due_at) < new Date()
                                                    ? 'text-red-600'
                                                    : ''
                                            "
                                        >
                                            · {{ t('panel.tickets.sla') }}:
                                            {{ formatDateTime(ticket.due_at) }}
                                        </span>
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

                    <div
                        v-if="tickets.links.length > 3"
                        class="flex flex-wrap items-center justify-center gap-1 border-t border-gray-200 p-4"
                    >
                        <Link
                            v-for="link in tickets.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            class="rounded px-3 py-1 text-sm"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'pointer-events-none opacity-50',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
