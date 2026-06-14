<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { useTicketLabels } from '@/utils/ticketLabels';
import { useTrans } from '@/composables/useTrans';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { t } = useTrans();
const { roleLabels } = useTicketLabels();

defineProps({
    users: Array,
    departments: Array,
    roles: Array,
});

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'cliente',
    department_id: '',
});

const editingId = ref(null);
const editForm = useForm({
    name: '',
    email: '',
    role: 'cliente',
    department_id: '',
});

const createNeedsDepartment = computed(() => createForm.role !== 'cliente');
const editNeedsDepartment = computed(() => editForm.role !== 'cliente');

const startEdit = (user) => {
    editingId.value = user.id;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role;
    editForm.department_id = user.department_id || '';
};

const submitCreate = () => {
    createForm.post(route('panel.agents.store'), {
        onSuccess: () =>
            createForm.reset('name', 'email', 'password', 'password_confirmation', 'department_id'),
    });
};

const submitEdit = (userId) => {
    editForm.patch(route('panel.agents.update', userId), {
        onSuccess: () => {
            editingId.value = null;
        },
    });
};
</script>

<template>
    <Head :title="t('nav.users')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ t('nav.users') }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-1 font-semibold">{{ t('agents.new') }}</h3>
                    <p class="mb-4 text-sm text-gray-600">
                        {{ t('agents.new_hint') }}
                    </p>
                    <form @submit.prevent="submitCreate" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="name" :value="t('common.name')" />
                            <TextInput
                                id="name"
                                v-model="createForm.name"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="email" :value="t('common.email')" />
                            <TextInput
                                id="email"
                                v-model="createForm.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="password" :value="t('auth.password')" />
                            <PasswordInput
                                id="password"
                                v-model="createForm.password"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.password" />
                        </div>
                        <div>
                            <InputLabel
                                for="password_confirmation"
                                :value="t('agents.confirm_password')"
                            />
                            <PasswordInput
                                id="password_confirmation"
                                v-model="createForm.password_confirmation"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div>
                            <InputLabel for="role" :value="t('common.role')" />
                            <select
                                id="role"
                                v-model="createForm.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                        </div>
                        <div v-if="createNeedsDepartment">
                            <InputLabel for="department_id" :value="t('common.department')" />
                            <select
                                id="department_id"
                                v-model="createForm.department_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">{{ t('common.select') }}</option>
                                <option
                                    v-for="department in departments"
                                    :key="department.id"
                                    :value="department.id"
                                >
                                    {{ department.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="createForm.errors.department_id" />
                        </div>
                        <div class="md:col-span-2">
                            <PrimaryButton :disabled="createForm.processing">{{
                                t('agents.create')
                            }}</PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    {{ t('common.name') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    {{ t('common.email') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    {{ t('common.role') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    {{ t('common.department') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500"
                                >
                                    {{ t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-6 py-4">
                                    <TextInput
                                        v-if="editingId === user.id"
                                        v-model="editForm.name"
                                        class="block w-full"
                                    />
                                    <span v-else>{{ user.name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <TextInput
                                        v-if="editingId === user.id"
                                        v-model="editForm.email"
                                        class="block w-full"
                                    />
                                    <span v-else>{{ user.email }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <select
                                        v-if="editingId === user.id"
                                        v-model="editForm.role"
                                        class="rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option
                                            v-for="role in roles"
                                            :key="role.value"
                                            :value="role.value"
                                        >
                                            {{ role.label }}
                                        </option>
                                    </select>
                                    <span v-else>{{ roleLabels[user.role] || user.role }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <select
                                        v-if="editingId === user.id && editNeedsDepartment"
                                        v-model="editForm.department_id"
                                        class="rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option
                                            v-for="department in departments"
                                            :key="department.id"
                                            :value="department.id"
                                        >
                                            {{ department.name }}
                                        </option>
                                    </select>
                                    <span v-else>{{ user.department?.name || '—' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <button
                                        v-if="editingId === user.id"
                                        class="text-indigo-600 hover:text-indigo-800"
                                        @click="submitEdit(user.id)"
                                    >
                                        {{ t('common.save') }}
                                    </button>
                                    <button
                                        v-else
                                        class="text-indigo-600 hover:text-indigo-800"
                                        @click="startEdit(user)"
                                    >
                                        {{ t('common.edit') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
