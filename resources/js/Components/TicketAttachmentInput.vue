<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { useTrans } from '@/composables/useTrans';

defineProps({
    error: String,
    maxFiles: {
        type: Number,
        default: 5,
    },
});

const { t } = useTrans();

const model = defineModel({
    type: Array,
    default: () => [],
});

const onFilesSelected = (event) => {
    model.value = Array.from(event.target.files ?? []);
};
</script>

<template>
    <div>
        <InputLabel for="attachments" :value="t('attachments.label')" />
        <input
            id="attachments"
            type="file"
            multiple
            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
            @change="onFilesSelected"
        />
        <p class="mt-1 text-xs text-gray-500">
            {{ t('attachments.hint', { max: maxFiles }) }}
        </p>
        <InputError class="mt-2" :message="error" />
    </div>
</template>
