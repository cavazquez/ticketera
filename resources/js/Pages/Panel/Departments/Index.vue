<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    departments: Array,
});

const createForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const editingId = ref(null);
const editForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const startEdit = (department) => {
    editingId.value = department.id;
    editForm.name = department.name;
    editForm.description = department.description || '';
    editForm.is_active = department.is_active;
};

const submitCreate = () => {
    createForm.post(route('panel.departments.store'), {
        onSuccess: () => createForm.reset(),
    });
};

const submitEdit = (departmentId) => {
    editForm.patch(route('panel.departments.update', departmentId), {
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const destroyDepartment = (departmentId) => {
    if (confirm('¿Eliminar este departamento?')) {
        useForm({}).delete(route('panel.departments.destroy', departmentId));
    }
};
</script>

<template>
    <Head title="Departamentos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Departamentos</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
                <FlashMessage />

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 font-semibold">Nuevo departamento</h3>
                    <form @submit.prevent="submitCreate" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput
                                id="name"
                                v-model="createForm.name"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="createForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="description" value="Descripción" />
                            <TextInput
                                id="description"
                                v-model="createForm.description"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <PrimaryButton :disabled="createForm.processing"
                                >Crear departamento</PrimaryButton
                            >
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
                                    Nombre
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    Tickets
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                >
                                    Agentes
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="department in departments" :key="department.id">
                                <td class="px-6 py-4">
                                    <template v-if="editingId === department.id">
                                        <TextInput v-model="editForm.name" class="block w-full" />
                                    </template>
                                    <template v-else>
                                        <p class="font-medium">{{ department.name }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ department.description }}
                                        </p>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-sm">{{ department.tickets_count }}</td>
                                <td class="px-6 py-4 text-sm">{{ department.users_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <template v-if="editingId === department.id">
                                        <button
                                            class="text-indigo-600 hover:text-indigo-800"
                                            @click="submitEdit(department.id)"
                                        >
                                            Guardar
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            class="mr-3 text-indigo-600 hover:text-indigo-800"
                                            @click="startEdit(department)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            class="text-red-600 hover:text-red-800"
                                            @click="destroyDepartment(department.id)"
                                        >
                                            Eliminar
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
