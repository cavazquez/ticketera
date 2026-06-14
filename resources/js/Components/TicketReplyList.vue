<script setup>
import Badge from '@/Components/Badge.vue';
import TicketAttachmentList from '@/Components/TicketAttachmentList.vue';
import { roleLabels } from '@/utils/ticketLabels';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    replies: Object,
    showInternal: {
        type: Boolean,
        default: false,
    },
});

const orderedReplies = computed(() => [...props.replies.data].reverse());
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="replies.next_page_url"
            class="flex justify-center"
        >
            <Link
                :href="replies.next_page_url"
                preserve-scroll
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                ← Mensajes anteriores
            </Link>
        </div>

        <p
            v-if="replies.total > replies.per_page"
            class="text-center text-xs text-gray-500"
        >
            Página {{ replies.current_page }} de {{ replies.last_page }}
            ({{ replies.total }} respuestas)
        </p>

        <div
            v-for="reply in orderedReplies"
            :key="reply.id"
            class="rounded-lg p-6 shadow-sm"
            :class="
                showInternal && reply.is_internal
                    ? 'border border-yellow-200 bg-yellow-50'
                    : 'bg-white'
            "
        >
            <div class="mb-2 flex items-center justify-between">
                <p class="font-medium">
                    {{ reply.user?.name }}
                    <span v-if="showInternal" class="text-sm text-gray-500">
                        ({{ roleLabels[reply.user?.role] || reply.user?.role }})
                    </span>
                    <Badge
                        v-if="showInternal && reply.is_internal"
                        label="Nota interna"
                        color="yellow"
                        class="ml-2"
                    />
                </p>
                <p class="text-xs text-gray-500">
                    {{ new Date(reply.created_at).toLocaleString('es-AR') }}
                </p>
            </div>
            <p class="whitespace-pre-wrap text-gray-700">{{ reply.body }}</p>
            <TicketAttachmentList :attachments="reply.attachments" />
        </div>

        <div
            v-if="replies.prev_page_url"
            class="flex justify-center"
        >
            <Link
                :href="replies.prev_page_url"
                preserve-scroll
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                Mensajes más recientes →
            </Link>
        </div>
    </div>
</template>
