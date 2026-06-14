<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTrans } from '@/composables/useTrans';

defineProps({
    categories: Array,
    articles: Array,
});

const { t } = useTrans();

const editingCategoryId = ref(null);
const editingArticleId = ref(null);

const categoryForm = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

const categoryEditForm = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

const articleForm = useForm({
    kb_category_id: '',
    title: '',
    slug: '',
    summary: '',
    body: '',
    is_published: true,
    is_featured: false,
    sort_order: 0,
});

const articleEditForm = useForm({
    kb_category_id: '',
    title: '',
    slug: '',
    summary: '',
    body: '',
    is_published: true,
    is_featured: false,
    sort_order: 0,
});

const submitCategory = () => {
    categoryForm.post(route('panel.kb-categories.store'), {
        onSuccess: () => categoryForm.reset(),
    });
};

const startEditCategory = (category) => {
    editingCategoryId.value = category.id;
    categoryEditForm.name = category.name;
    categoryEditForm.slug = category.slug;
    categoryEditForm.description = category.description || '';
    categoryEditForm.sort_order = category.sort_order;
    categoryEditForm.is_active = category.is_active;
};

const submitCategoryEdit = (id) => {
    categoryEditForm.patch(route('panel.kb-categories.update', id), {
        onSuccess: () => {
            editingCategoryId.value = null;
        },
    });
};

const destroyCategory = (id) => {
    if (confirm(t('kb.delete_category'))) {
        useForm({}).delete(route('panel.kb-categories.destroy', id));
    }
};

const submitArticle = () => {
    articleForm
        .transform((data) => ({
            ...data,
            kb_category_id: data.kb_category_id || null,
        }))
        .post(route('panel.kb-articles.store'), {
            onSuccess: () => articleForm.reset(),
        });
};

const startEditArticle = (article) => {
    editingArticleId.value = article.id;
    articleEditForm.kb_category_id = article.kb_category_id || '';
    articleEditForm.title = article.title;
    articleEditForm.slug = article.slug;
    articleEditForm.summary = article.summary || '';
    articleEditForm.body = article.body;
    articleEditForm.is_published = article.is_published;
    articleEditForm.is_featured = article.is_featured;
    articleEditForm.sort_order = article.sort_order;
};

const submitArticleEdit = (id) => {
    articleEditForm
        .transform((data) => ({
            ...data,
            kb_category_id: data.kb_category_id || null,
        }))
        .patch(route('panel.kb-articles.update', id), {
            onSuccess: () => {
                editingArticleId.value = null;
            },
        });
};

const destroyArticle = (id) => {
    if (confirm(t('kb.delete_article'))) {
        useForm({}).delete(route('panel.kb-articles.destroy', id));
    }
};
</script>

<template>
    <Head :title="t('kb.admin_title')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ t('kb.admin_title') }}
                </h2>
                <a
                    :href="route('help.index')"
                    target="_blank"
                    class="text-sm text-indigo-600 hover:text-indigo-500"
                >
                    {{ t('nav.help') }} ↗
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-8 sm:px-6 lg:px-8">
                <FlashMessage />

                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ t('kb.new_category') }}</h3>
                    <form @submit.prevent="submitCategory" class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="cat_name" :value="t('kb.category_name')" />
                            <TextInput id="cat_name" v-model="categoryForm.name" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="categoryForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="cat_slug" :value="t('kb.category_slug')" />
                            <TextInput id="cat_slug" v-model="categoryForm.slug" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="categoryForm.errors.slug" />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel for="cat_desc" :value="t('kb.category_description')" />
                            <textarea
                                id="cat_desc"
                                v-model="categoryForm.description"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div class="flex items-end">
                            <PrimaryButton :disabled="categoryForm.processing">{{ t('common.create') }}</PrimaryButton>
                        </div>
                    </form>

                    <h4 class="mb-3 mt-8 font-medium text-gray-900">{{ t('kb.categories') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left">{{ t('kb.category_name') }}</th>
                                    <th class="px-4 py-2 text-left">{{ t('kb.category_slug') }}</th>
                                    <th class="px-4 py-2">{{ t('common.active') }}</th>
                                    <th class="px-4 py-2">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="category in categories" :key="category.id">
                                    <td colspan="4" v-if="editingCategoryId === category.id" class="bg-indigo-50 p-4">
                                        <form @submit.prevent="submitCategoryEdit(category.id)" class="grid gap-3 md:grid-cols-2">
                                            <TextInput v-model="categoryEditForm.name" required />
                                            <TextInput v-model="categoryEditForm.slug" required />
                                            <textarea v-model="categoryEditForm.description" rows="2" class="md:col-span-2 w-full rounded-md border-gray-300" />
                                            <label class="flex items-center gap-2">
                                                <input v-model="categoryEditForm.is_active" type="checkbox" />
                                                {{ t('common.active') }}
                                            </label>
                                            <div class="flex gap-2">
                                                <PrimaryButton type="submit">{{ t('common.save') }}</PrimaryButton>
                                                <SecondaryButton type="button" @click="editingCategoryId = null">{{ t('common.cancel') }}</SecondaryButton>
                                            </div>
                                        </form>
                                    </td>
                                    <template v-else>
                                        <td class="px-4 py-2">{{ category.name }}</td>
                                        <td class="px-4 py-2 font-mono text-xs">{{ category.slug }}</td>
                                        <td class="px-4 py-2 text-center">{{ category.is_active ? t('common.yes') : t('common.no') }}</td>
                                        <td class="px-4 py-2">
                                            <button type="button" class="text-indigo-600" @click="startEditCategory(category)">{{ t('common.edit') }}</button>
                                            <button type="button" class="ms-3 text-red-600" @click="destroyCategory(category.id)">{{ t('common.delete') }}</button>
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">{{ t('kb.new_article') }}</h3>
                    <form @submit.prevent="submitArticle" class="mt-4 space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel for="art_title" :value="t('kb.article_title')" />
                                <TextInput id="art_title" v-model="articleForm.title" class="mt-1 block w-full" required />
                                <InputError class="mt-2" :message="articleForm.errors.title" />
                            </div>
                            <div>
                                <InputLabel for="art_cat" :value="t('kb.article_category')" />
                                <select id="art_cat" v-model="articleForm.kb_category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="">{{ t('kb.no_category') }}</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <InputLabel for="art_summary" :value="t('kb.article_summary')" />
                            <textarea id="art_summary" v-model="articleForm.summary" rows="2" class="mt-1 block w-full rounded-md border-gray-300" />
                        </div>
                        <div>
                            <InputLabel for="art_body" :value="t('kb.article_body')" />
                            <textarea id="art_body" v-model="articleForm.body" rows="8" class="mt-1 block w-full rounded-md border-gray-300" required />
                            <InputError class="mt-2" :message="articleForm.errors.body" />
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="articleForm.is_published" type="checkbox" />
                                {{ t('common.published') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="articleForm.is_featured" type="checkbox" />
                                {{ t('common.featured') }}
                            </label>
                            <PrimaryButton :disabled="articleForm.processing">{{ t('common.create') }}</PrimaryButton>
                        </div>
                    </form>

                    <h4 class="mb-3 mt-8 font-medium text-gray-900">{{ t('kb.articles') }}</h4>
                    <ul class="divide-y divide-gray-100">
                        <li v-for="article in articles" :key="article.id" class="py-4">
                            <div v-if="editingArticleId === article.id" class="space-y-3 rounded-lg bg-indigo-50 p-4">
                                <form @submit.prevent="submitArticleEdit(article.id)" class="space-y-3">
                                    <TextInput v-model="articleEditForm.title" required />
                                    <select v-model="articleEditForm.kb_category_id" class="w-full rounded-md border-gray-300">
                                        <option value="">{{ t('kb.no_category') }}</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                    </select>
                                    <textarea v-model="articleEditForm.summary" rows="2" class="w-full rounded-md border-gray-300" />
                                    <textarea v-model="articleEditForm.body" rows="6" class="w-full rounded-md border-gray-300" required />
                                    <div class="flex flex-wrap gap-4">
                                        <label class="flex items-center gap-2 text-sm"><input v-model="articleEditForm.is_published" type="checkbox" />{{ t('common.published') }}</label>
                                        <label class="flex items-center gap-2 text-sm"><input v-model="articleEditForm.is_featured" type="checkbox" />{{ t('common.featured') }}</label>
                                    </div>
                                    <div class="flex gap-2">
                                        <PrimaryButton type="submit">{{ t('common.save') }}</PrimaryButton>
                                        <SecondaryButton type="button" @click="editingArticleId = null">{{ t('common.cancel') }}</SecondaryButton>
                                    </div>
                                </form>
                            </div>
                            <div v-else class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <a :href="route('help.show', article.slug)" target="_blank" class="font-medium text-gray-900 hover:text-indigo-600">{{ article.title }}</a>
                                    <p class="text-sm text-gray-500">{{ article.category?.name ?? t('kb.no_category') }} · {{ article.is_published ? t('common.published') : t('common.draft') }}</p>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    <button type="button" class="text-indigo-600" @click="startEditArticle(article)">{{ t('common.edit') }}</button>
                                    <button type="button" class="text-red-600" @click="destroyArticle(article.id)">{{ t('common.delete') }}</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
