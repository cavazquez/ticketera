<script setup>
defineProps({
    attachments: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ul v-if="attachments.length" class="mt-3 space-y-1">
        <li v-for="attachment in attachments" :key="attachment.id">
            <a
                :href="attachment.download_url"
                class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800"
            >
                <span>{{ attachment.original_name }}</span>
                <span class="text-xs text-gray-500">({{ attachment.human_size }})</span>
            </a>
        </li>
    </ul>
</template>
