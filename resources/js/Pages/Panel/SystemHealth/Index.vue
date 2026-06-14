<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useTrans } from '@/composables/useTrans';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { t } = useTrans();

const props = defineProps({
    report: Object,
});

const refreshing = ref(false);

const summary = computed(() => props.report?.summary ?? {});

const refreshReport = () => {
    refreshing.value = true;
    router.reload({
        only: ['report'],
        preserveScroll: true,
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

const statusClasses = {
    ok: 'bg-green-100 text-green-800 ring-green-600/20',
    warning: 'bg-amber-100 text-amber-800 ring-amber-600/20',
    error: 'bg-red-100 text-red-800 ring-red-600/20',
};

const statusLabels = computed(() => ({
    ok: t('sh.status_ok'),
    warning: t('sh.status_warning'),
    error: t('sh.status_error'),
}));

const summaryBannerClass = computed(() => {
    if (summary.value.error > 0) {
        return 'border-red-200 bg-red-50 text-red-900';
    }

    if (summary.value.warning > 0) {
        return 'border-amber-200 bg-amber-50 text-amber-900';
    }

    return 'border-green-200 bg-green-50 text-green-900';
});
</script>

<template>
    <Head :title="t('sh.title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ t('sh.title') }}
                </h2>
                <SecondaryButton :disabled="refreshing" @click="refreshReport">
                    {{ refreshing ? t('sh.running') : t('sh.run') }}
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-lg border p-5 shadow-sm" :class="summaryBannerClass">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold">
                                {{
                                    summary.error > 0
                                        ? t('sh.has_problems')
                                        : summary.warning > 0
                                          ? t('sh.operational_warnings')
                                          : t('sh.all_good')
                                }}
                            </p>
                            <p class="mt-1 text-sm opacity-80">
                                {{ t('sh.generated') }} {{ report.generated_at }} · PHP
                                {{ report.environment.php_version }} · Laravel
                                {{ report.environment.laravel_version }} ·
                                {{ report.environment.app_env }}
                            </p>
                            <p class="mt-1 text-xs opacity-70">
                                {{ t('sh.runs_on_open') }}
                            </p>
                        </div>
                        <div class="flex gap-3 text-sm">
                            <span class="rounded-full bg-white/70 px-3 py-1 font-medium">
                                {{ t('sh.oks', { count: summary.ok }) }}
                            </span>
                            <span class="rounded-full bg-white/70 px-3 py-1 font-medium">
                                {{ t('sh.warnings', { count: summary.warning }) }}
                            </span>
                            <span class="rounded-full bg-white/70 px-3 py-1 font-medium">
                                {{ t('sh.errors', { count: summary.error }) }}
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    v-for="group in report.groups"
                    :key="group.name"
                    class="overflow-hidden rounded-lg bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ group.label }}</h3>
                    </div>

                    <ul class="divide-y divide-gray-100">
                        <li
                            v-for="check in group.checks"
                            :key="check.key"
                            class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <p class="font-medium text-gray-900">{{ check.label }}</p>
                                <p class="mt-1 text-sm text-gray-600">{{ check.message }}</p>
                                <p v-if="check.detail" class="mt-1 text-xs text-gray-500">
                                    {{ check.detail }}
                                </p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                :class="statusClasses[check.status]"
                            >
                                {{ statusLabels[check.status] }}
                            </span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
