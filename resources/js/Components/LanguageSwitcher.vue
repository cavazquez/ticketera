<script setup>
import { useForm } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

const { t, locale, locales } = useTrans();

const form = useForm({
    locale: locale(),
});

const switchLocale = (nextLocale) => {
    if (nextLocale === locale()) {
        return;
    }

    form.locale = nextLocale;
    form.post(route('locale.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div
        class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white p-0.5 text-xs"
    >
        <span class="sr-only">{{ t('nav.language') }}</span>
        <button
            v-for="(label, code) in locales()"
            :key="code"
            type="button"
            class="rounded px-2 py-1 font-medium transition"
            :class="
                code === locale() ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'
            "
            :disabled="form.processing"
            @click="switchLocale(code)"
        >
            {{ code.toUpperCase() }}
        </button>
    </div>
</template>
