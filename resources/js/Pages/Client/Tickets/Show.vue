<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Badge.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TicketAttachmentInput from '@/Components/TicketAttachmentInput.vue';
import TicketAttachmentList from '@/Components/TicketAttachmentList.vue';
import TicketReplyList from '@/Components/TicketReplyList.vue';
import { priorityColor, priorityLabels, statusColor, statusLabels } from '@/utils/ticketLabels';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    ticket: Object,
    replies: Object,
});

const form = useForm({
    body: '',
    attachments: [],
});

const submit = () => {
    form.post(route('client.tickets.reply', props.ticket.id), {
        forceFormData: true,
        onSuccess: () => form.reset('body', 'attachments'),
    });
};
</script>

<template>
    <Head :title="ticket.subject" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link
                    :href="route('client.tickets.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    ← Volver a mis tickets
                </Link>
                <h2 class="mt-2 text-xl font-semibold leading-tight text-gray-800">
                    {{ ticket.number }} · {{ ticket.subject }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="mb-6 flex flex-wrap gap-2">
                    <Badge
                        :label="statusLabels[ticket.status]"
                        :color="statusColor(ticket.status)"
                    />
                    <Badge
                        :label="priorityLabels[ticket.priority]"
                        :color="priorityColor(ticket.priority)"
                    />
                    <span class="text-sm text-gray-500">{{ ticket.department?.name }}</span>
                    <span v-if="ticket.assignee" class="text-sm text-gray-500">
                        · Agente: {{ ticket.assignee.name }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="font-medium text-gray-900">Mensaje inicial</p>
                            <p class="text-xs text-gray-500">
                                {{ new Date(ticket.created_at).toLocaleString('es-AR') }}
                            </p>
                        </div>
                        <p class="whitespace-pre-wrap text-gray-700">{{ ticket.body }}</p>
                        <TicketAttachmentList :attachments="ticket.attachments" />
                    </div>

                    <TicketReplyList :replies="replies" />
                </div>

                <div class="mt-8 overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <InputLabel for="body" value="Tu respuesta" />
                            <textarea
                                id="body"
                                v-model="form.body"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.body" />
                        </div>
                        <TicketAttachmentInput
                            v-model="form.attachments"
                            :error="form.errors.attachments || form.errors['attachments.0']"
                        />
                        <PrimaryButton :disabled="form.processing">Enviar respuesta</PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
