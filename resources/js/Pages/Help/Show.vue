<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

defineProps({
    article: Object,
    related: Array,
});

const { t } = useTrans();

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="article.title" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <Link :href="route('help.index')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-8 fill-current text-indigo-600" />
                    <span class="font-semibold text-gray-900">{{ t('help.title') }}</span>
                </Link>
                <LanguageSwitcher />
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <Link :href="route('help.index')" class="text-sm text-indigo-600 hover:text-indigo-500">
                ← {{ t('common.back') }}
            </Link>

            <article class="mt-6 rounded-lg bg-white p-8 shadow-sm">
                <p class="text-sm text-gray-500">
                    {{ article.category?.name ?? t('kb.no_category') }}
                </p>
                <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ article.title }}</h1>
                <p class="mt-2 text-sm text-gray-500">
                    {{ t('help.updated', { date: formatDate(article.published_at) }) }}
                    · {{ t('help.views', { count: article.view_count }) }}
                </p>

                <div class="prose prose-indigo mt-8 max-w-none whitespace-pre-wrap text-gray-800">
                    {{ article.body }}
                </div>
            </article>

            <section v-if="related.length" class="mt-10">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ t('help.related') }}</h2>
                <ul class="space-y-2">
                    <li v-for="item in related" :key="item.id">
                        <Link
                            :href="route('help.show', item.slug)"
                            class="block rounded-md bg-white px-4 py-3 shadow-sm hover:bg-indigo-50"
                        >
                            <span class="font-medium text-gray-900">{{ item.title }}</span>
                            <p v-if="item.summary" class="text-sm text-gray-600">
                                {{ item.summary }}
                            </p>
                        </Link>
                    </li>
                </ul>
            </section>
        </main>
    </div>
</template>
