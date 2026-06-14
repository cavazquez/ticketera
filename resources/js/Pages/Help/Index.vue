<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

defineProps({
    categories: Array,
    articles: Array,
    featured: Array,
    filters: Object,
});

const { t } = useTrans();

const categoryHref = (slug) => route('help.index', slug ? { category: slug, q: undefined } : {});
</script>

<template>
    <Head :title="t('help.title')" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6"
            >
                <Link :href="route('help.index')" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-8 fill-current text-indigo-600" />
                    <span class="font-semibold text-gray-900">{{ t('help.title') }}</span>
                </Link>
                <div class="flex items-center gap-3">
                    <LanguageSwitcher />
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="text-sm text-gray-600 hover:text-indigo-600"
                    >
                        {{ t('nav.home') }}
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="text-sm text-gray-600 hover:text-indigo-600"
                    >
                        {{ t('auth.login') }}
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ t('help.title') }}</h1>
                <p class="mt-2 text-gray-600">{{ t('help.subtitle') }}</p>
            </div>

            <form :action="route('help.index')" method="get" class="mb-8">
                <input
                    type="search"
                    name="q"
                    :value="filters.q"
                    :placeholder="t('help.search_placeholder')"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </form>

            <section v-if="featured.length && !filters.q && !filters.category" class="mb-10">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ t('help.featured') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <Link
                        v-for="article in featured"
                        :key="article.id"
                        :href="route('help.show', article.slug)"
                        class="rounded-lg border border-indigo-100 bg-white p-5 shadow-sm transition hover:border-indigo-300"
                    >
                        <p class="text-xs font-medium uppercase text-indigo-600">
                            {{ article.category?.name ?? t('kb.no_category') }}
                        </p>
                        <h3 class="mt-1 font-semibold text-gray-900">{{ article.title }}</h3>
                        <p v-if="article.summary" class="mt-2 line-clamp-2 text-sm text-gray-600">
                            {{ article.summary }}
                        </p>
                    </Link>
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-4">
                <aside class="lg:col-span-1">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">
                        {{ t('help.categories') }}
                    </h2>
                    <ul class="space-y-1">
                        <li>
                            <Link
                                :href="route('help.index')"
                                class="block rounded-md px-3 py-2 text-sm"
                                :class="
                                    !filters.category
                                        ? 'bg-indigo-50 font-medium text-indigo-700'
                                        : 'text-gray-700 hover:bg-gray-100'
                                "
                            >
                                {{ t('help.all_articles') }}
                            </Link>
                        </li>
                        <li v-for="category in categories" :key="category.id">
                            <Link
                                :href="categoryHref(category.slug)"
                                class="block rounded-md px-3 py-2 text-sm"
                                :class="
                                    filters.category === category.slug
                                        ? 'bg-indigo-50 font-medium text-indigo-700'
                                        : 'text-gray-700 hover:bg-gray-100'
                                "
                            >
                                {{ category.name }}
                                <span class="text-gray-400">({{ category.published_count }})</span>
                            </Link>
                        </li>
                    </ul>
                </aside>

                <section class="lg:col-span-3">
                    <div
                        v-if="articles.length === 0"
                        class="rounded-lg bg-white p-8 text-center text-gray-500"
                    >
                        {{ t('help.no_results') }}
                    </div>
                    <ul v-else class="divide-y divide-gray-200 rounded-lg bg-white shadow-sm">
                        <li v-for="article in articles" :key="article.id">
                            <Link
                                :href="route('help.show', article.slug)"
                                class="block px-6 py-4 hover:bg-gray-50"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">
                                            {{ article.category?.name ?? t('kb.no_category') }}
                                        </p>
                                        <h3 class="font-medium text-gray-900">
                                            {{ article.title }}
                                        </h3>
                                        <p
                                            v-if="article.summary"
                                            class="mt-1 text-sm text-gray-600"
                                        >
                                            {{ article.summary }}
                                        </p>
                                    </div>
                                    <span
                                        v-if="article.is_featured"
                                        class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800"
                                    >
                                        ★
                                    </span>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>

            <p class="mt-10 text-center text-sm text-gray-600">
                {{ t('help.open_ticket') }} —
                <Link
                    :href="$page.props.auth.user ? route('client.tickets.create') : route('login')"
                    class="font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {{ t('nav.new_ticket') }}
                </Link>
            </p>
        </main>
    </div>
</template>
