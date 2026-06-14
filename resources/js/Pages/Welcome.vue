<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTrans } from '@/composables/useTrans';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

const { t } = useTrans();
const appName = usePage().props.appName ?? 'Ticketera';

const features = computed(() => [
    {
        title: t('welcome.feature_portal_title'),
        description: t('welcome.feature_portal_desc'),
        icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
    },
    {
        title: t('welcome.feature_queue_title'),
        description: t('welcome.feature_queue_desc'),
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    },
    {
        title: t('welcome.feature_teams_title'),
        description: t('welcome.feature_teams_desc'),
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
]);
</script>

<template>
    <Head :title="appName" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50 text-gray-900">
        <header class="border-b border-white/60 bg-white/70 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-10 w-10 fill-current text-indigo-600" />
                    <span class="text-xl font-bold tracking-tight">{{ appName }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <LanguageSwitcher />
                    <Link
                        :href="route('help.index')"
                        class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
                    >
                        {{ t('welcome.view_help') }}
                    </Link>
                    <template v-if="canLogin">
                        <Link
                            :href="route('login')"
                            class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
                        >
                            {{ t('auth.login') }}
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700"
                        >
                            {{ t('auth.register') }}
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-7xl px-6 py-20 lg:py-28">
                <div class="mx-auto max-w-3xl text-center">
                    <p class="mb-4 text-sm font-semibold uppercase tracking-widest text-indigo-600">
                        {{ t('welcome.tagline') }}
                    </p>
                    <h1
                        class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl"
                    >
                        {{ t('welcome.title') }}
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600">
                        {{ appName }} {{ t('welcome.subtitle') }}
                    </p>

                    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                        >
                            {{ t('welcome.get_started') }}
                        </Link>
                        <Link
                            :href="route('help.index')"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-800 transition hover:border-indigo-300 hover:text-indigo-600"
                        >
                            {{ t('welcome.view_help') }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-6 pb-20">
                <div class="grid gap-6 md:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow-md"
                    >
                        <div class="mb-4 inline-flex rounded-lg bg-indigo-50 p-3 text-indigo-600">
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="feature.icon"
                                />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ feature.title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            {{ feature.description }}
                        </p>
                    </article>
                </div>
            </section>

            <section class="border-t border-gray-200 bg-white/80">
                <div class="mx-auto max-w-7xl px-6 py-12">
                    <div
                        class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/50 p-6 sm:p-8"
                    >
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t('welcome.demo_title') }}
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            password:
                            <code class="rounded bg-white px-1.5 py-0.5 text-indigo-700"
                                >password</code
                            >
                        </p>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-lg bg-white p-3 shadow-sm">
                                <dt class="font-medium text-gray-500">
                                    {{ t('user.role.admin') }}
                                </dt>
                                <dd class="mt-1 font-mono text-gray-800">admin@ticketera.test</dd>
                            </div>
                            <div class="rounded-lg bg-white p-3 shadow-sm">
                                <dt class="font-medium text-gray-500">
                                    {{ t('user.role.agente') }}
                                </dt>
                                <dd class="mt-1 font-mono text-gray-800">maria@ticketera.test</dd>
                            </div>
                            <div class="rounded-lg bg-white p-3 shadow-sm">
                                <dt class="font-medium text-gray-500">
                                    {{ t('user.role.cliente') }}
                                </dt>
                                <dd class="mt-1 font-mono text-gray-800">cliente@ticketera.test</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-200 py-6 text-center text-sm text-gray-500">
            {{ appName }}
        </footer>
    </div>
</template>
