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

defineProps({
    cannedResponses: Array,
    departments: Array,
});

const createForm = useForm({
    title: '',
    body: '',
    department_id: '',
    is_active: true,
});

const editingId = ref(null);
const editForm = useForm({
    title: '',
    body: '',
    department_id: '',
    is_active: true,
});

const startEdit = (canned) => {
    editingId.value = canned.id;
    editForm.title = canned.title;
    editForm.body = canned.body;
    editForm.department_id = canned.department_id || '';
    editForm.is_active = canned.is_active;
};

const submitCreate = () => {
    createForm
        .transform((data) => ({
            ...data,
            department_id: data.department_id || null,
        }))
        .post(route('panel.canned-responses.store'), {
            onSuccess: () => createForm.reset(),
        });
};

const submitEdit = (id) => {
    editForm
        .transform((data) => ({
            ...data,
            department_id: data.department_id || null,
        }))
        .patch(route('panel.canned-responses.update', id), {
            onSuccess: () => {
                editingId.value = null;
            },
        });
};

const destroyCanned = (id) => {
    if (confirm('¿Eliminar esta respuesta predefinida?')) {
        useForm({}).delete(route('panel.canned-responses.destroy', id));
    }
};
</script>

<template>
    <Head title="Respuestas predefinidas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Respuestas predefinidas
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-1 font-semibold">Nueva macro</h3>
                    <p class="mb-4 text-sm text-gray-600">
                        Placeholders: <code>{cliente}</code>, <code>{numero}</code>,
                        <code>{asunto}</code>
                    </p>
                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <InputLabel for="title" value="Título" />
                                <TextInput
                                    id="title"
                                    v-model="createForm.title"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="createForm.errors.title" />
                            </div>
                            <div>
                                <InputLabel for="department_id" value="Departamento" />
                                <select
                                    id="department_id"
                                    v-model="createForm.department_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                >
                                    <option value="">Todos los departamentos</option>
                                    <option
                                        v-for="dept in departments"
                                        :key="dept.id"
                                        :value="dept.id"
                                    >
                                        {{ dept.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <InputLabel for="body" value="Contenido" />
                            <textarea
                                id="body"
                                v-model="createForm.body"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.body" />
                        </div>
                        <PrimaryButton :disabled="createForm.processing">Crear macro</PrimaryButton>
                    </form>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    Título
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    Departamento
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="canned in cannedResponses" :key="canned.id">
                                <td colspan="4" v-if="editingId === canned.id" class="p-4">
                                    <form @submit.prevent="submitEdit(canned.id)" class="space-y-4">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <InputLabel value="Título" />
                                                <TextInput
                                                    v-model="editForm.title"
                                                    class="mt-1 block w-full"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <InputLabel value="Departamento" />
                                                <select
                                                    v-model="editForm.department_id"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                                >
                                                    <option value="">Todos</option>
                                                    <option
                                                        v-for="dept in departments"
                                                        :key="dept.id"
                                                        :value="dept.id"
                                                    >
                                                        {{ dept.name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <textarea
                                            v-model="editForm.body"
                                            rows="4"
                                            class="block w-full rounded-md border-gray-300 shadow-sm"
                                            required
                                        />
                                        <label class="flex items-center gap-2 text-sm">
                                            <input
                                                v-model="editForm.is_active"
                                                type="checkbox"
                                                class="rounded border-gray-300"
                                            />
                                            Activa
                                        </label>
                                        <div class="flex gap-2">
                                            <PrimaryButton :disabled="editForm.processing">
                                                Guardar
                                            </PrimaryButton>
                                            <SecondaryButton
                                                type="button"
                                                @click="editingId = null"
                                            >
                                                Cancelar
                                            </SecondaryButton>
                                        </div>
                                    </form>
                                </td>
                                <template v-else>
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ canned.title }}</p>
                                        <p class="mt-1 line-clamp-2 text-sm text-gray-500">
                                            {{ canned.body }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ canned.department?.name || 'Todos' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ canned.is_active ? 'Activa' : 'Inactiva' }}
                                    </td>
                                    <td class="space-x-2 px-4 py-3 text-right text-sm">
                                        <button
                                            type="button"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            @click="startEdit(canned)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="text-red-600 hover:text-red-800"
                                            @click="destroyCanned(canned.id)"
                                        >
                                            Eliminar
                                        </button>
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
