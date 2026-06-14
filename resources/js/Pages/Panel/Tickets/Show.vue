<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TicketActivityLog from '@/Components/TicketActivityLog.vue';
import TicketAttachmentInput from '@/Components/TicketAttachmentInput.vue';
import TicketAttachmentList from '@/Components/TicketAttachmentList.vue';
import TicketReplyList from '@/Components/TicketReplyList.vue';
import { priorityColor, statusColor, useTicketLabels } from '@/utils/ticketLabels';
import { useTrans } from '@/composables/useTrans';
import { useFormat } from '@/composables/useFormat';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const { t } = useTrans();
const { formatDateTime } = useFormat();
const { statusLabels, priorityLabels } = useTicketLabels();

const props = defineProps({
    ticket: Object,
    replies: Object,
    activities: Array,
    agents: Array,
    statuses: Array,
    priorities: Array,
    cannedResponses: Array,
});

const replyForm = useForm({
    body: '',
    is_internal: false,
    attachments: [],
});

const updateForm = useForm({
    status: props.ticket.status,
    priority: props.ticket.priority,
    assigned_to: props.ticket.assigned_to || '',
});

const submitReply = () => {
    replyForm.post(route('panel.tickets.reply', props.ticket.id), {
        forceFormData: true,
        onSuccess: () => replyForm.reset('body', 'attachments'),
    });
};

const updateTicket = () => {
    updateForm.patch(route('panel.tickets.update', props.ticket.id));
};

const page = usePage();

const assignToMe = () => {
    updateForm.assigned_to = page.props.auth.user.id;
    updateTicket();
};

const applyCannedResponse = (event) => {
    const id = event.target.value;
    if (!id) {
        return;
    }

    const canned = props.cannedResponses.find((item) => String(item.id) === id);
    if (!canned) {
        return;
    }

    let body = canned.body;
    body = body.replaceAll('{cliente}', props.ticket.user?.name ?? '');
    body = body.replaceAll('{numero}', props.ticket.number ?? '');
    body = body.replaceAll('{asunto}', props.ticket.subject ?? '');
    replyForm.body = body;
    event.target.value = '';
};
</script>

<template>
    <Head :title="ticket.subject" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link
                    :href="route('panel.tickets.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    ← {{ t('ticket.back_to_queue') }}
                </Link>
                <h2 class="mt-2 text-xl font-semibold leading-tight text-gray-800">
                    {{ ticket.number }} · {{ ticket.subject }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="space-y-4 lg:col-span-2">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="font-medium">
                                    {{ ticket.user?.name }} ({{ t('user.role.cliente') }})
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ formatDateTime(ticket.created_at) }}
                                </p>
                            </div>
                            <p class="whitespace-pre-wrap text-gray-700">{{ ticket.body }}</p>
                            <TicketAttachmentList :attachments="ticket.attachments" />
                        </div>

                        <TicketReplyList :replies="replies" show-internal />

                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <form @submit.prevent="submitReply" class="space-y-4">
                                <div v-if="cannedResponses.length">
                                    <InputLabel
                                        for="canned_response"
                                        :value="t('ticket.canned_response')"
                                    />
                                    <select
                                        id="canned_response"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        @change="applyCannedResponse"
                                    >
                                        <option value="">{{ t('ticket.insert_macro') }}</option>
                                        <option
                                            v-for="canned in cannedResponses"
                                            :key="canned.id"
                                            :value="canned.id"
                                        >
                                            {{ canned.title }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="body" :value="t('ticket.reply')" />
                                    <textarea
                                        id="body"
                                        v-model="replyForm.body"
                                        rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                        required
                                    />
                                    <InputError class="mt-2" :message="replyForm.errors.body" />
                                </div>
                                <TicketAttachmentInput
                                    v-model="replyForm.attachments"
                                    :error="
                                        replyForm.errors.attachments ||
                                        replyForm.errors['attachments.0']
                                    "
                                />
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        v-model="replyForm.is_internal"
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                    />
                                    {{ t('ticket.internal_note_hint') }}
                                </label>
                                <PrimaryButton :disabled="replyForm.processing">{{
                                    t('common.send')
                                }}</PrimaryButton>
                            </form>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3 class="mb-4 font-semibold text-gray-900">
                                {{ t('ticket.details') }}
                            </h3>
                            <div class="mb-4 flex flex-wrap gap-2">
                                <Badge
                                    :label="statusLabels[ticket.status]"
                                    :color="statusColor(ticket.status)"
                                />
                                <Badge
                                    :label="priorityLabels[ticket.priority]"
                                    :color="priorityColor(ticket.priority)"
                                />
                            </div>
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">{{ t('common.client') }}</dt>
                                    <dd class="font-medium">{{ ticket.user?.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ t('common.email') }}</dt>
                                    <dd>{{ ticket.user?.email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ t('common.department') }}</dt>
                                    <dd>{{ ticket.department?.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">{{ t('ticket.assigned_to') }}</dt>
                                    <dd>{{ ticket.assignee?.name || t('common.unassigned') }}</dd>
                                </div>
                                <div v-if="ticket.due_at">
                                    <dt class="text-gray-500">{{ t('ticket.sla_due') }}</dt>
                                    <dd
                                        :class="
                                            new Date(ticket.due_at) < new Date()
                                                ? 'font-medium text-red-600'
                                                : ''
                                        "
                                    >
                                        {{ formatDateTime(ticket.due_at) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-sm">
                            <h3 class="mb-4 font-semibold text-gray-900">
                                {{ t('ticket.manage') }}
                            </h3>
                            <form @submit.prevent="updateTicket" class="space-y-4">
                                <div>
                                    <InputLabel for="status" :value="t('common.status')" />
                                    <select
                                        id="status"
                                        v-model="updateForm.status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option
                                            v-for="status in statuses"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="priority" :value="t('common.priority')" />
                                    <select
                                        id="priority"
                                        v-model="updateForm.priority"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option
                                            v-for="priority in priorities"
                                            :key="priority.value"
                                            :value="priority.value"
                                        >
                                            {{ priority.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="assigned_to" :value="t('ticket.assign_to')" />
                                    <select
                                        id="assigned_to"
                                        v-model="updateForm.assigned_to"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option value="">{{ t('common.unassigned') }}</option>
                                        <option
                                            v-for="agent in agents"
                                            :key="agent.id"
                                            :value="agent.id"
                                        >
                                            {{ agent.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <PrimaryButton :disabled="updateForm.processing">{{
                                        t('ticket.save_changes')
                                    }}</PrimaryButton>
                                    <SecondaryButton type="button" @click="assignToMe">
                                        {{ t('ticket.assign_to_me') }}
                                    </SecondaryButton>
                                </div>
                            </form>
                        </div>

                        <TicketActivityLog :activities="activities" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
