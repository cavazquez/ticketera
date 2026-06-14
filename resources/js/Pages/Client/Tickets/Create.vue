<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TicketAttachmentInput from '@/Components/TicketAttachmentInput.vue';
import TurnstileWidget from '@/Components/TurnstileWidget.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    departments: Array,
    priorities: Array,
});

const page = usePage();
const turnstile = page.props.turnstile;

const form = useForm({
    subject: '',
    department_id: '',
    priority: 'normal',
    body: '',
    attachments: [],
    company_website: '',
    cf_turnstile_response: '',
});

const submit = () => {
    form.post(route('client.tickets.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Nuevo ticket" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Nuevo ticket</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <FlashMessage />

                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div
                            class="absolute -left-[9999px] h-0 w-0 overflow-hidden"
                            aria-hidden="true"
                        >
                            <label for="company_website">No completar</label>
                            <input
                                id="company_website"
                                v-model="form.company_website"
                                type="text"
                                name="company_website"
                                tabindex="-1"
                                autocomplete="off"
                            />
                        </div>

                        <div>
                            <InputLabel for="subject" value="Asunto" />
                            <TextInput
                                id="subject"
                                v-model="form.subject"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.subject" />
                        </div>

                        <div>
                            <InputLabel for="department_id" value="Departamento" />
                            <select
                                id="department_id"
                                v-model="form.department_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="">Seleccionar...</option>
                                <option
                                    v-for="department in departments"
                                    :key="department.id"
                                    :value="department.id"
                                >
                                    {{ department.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.department_id" />
                        </div>

                        <div>
                            <InputLabel for="priority" value="Prioridad" />
                            <select
                                id="priority"
                                v-model="form.priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="priority in priorities"
                                    :key="priority.value"
                                    :value="priority.value"
                                >
                                    {{ priority.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.priority" />
                        </div>

                        <div>
                            <InputLabel for="body" value="Mensaje" />
                            <textarea
                                id="body"
                                v-model="form.body"
                                rows="6"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.body" />
                        </div>

                        <TicketAttachmentInput
                            v-model="form.attachments"
                            :error="form.errors.attachments || form.errors['attachments.0']"
                        />

                        <div v-if="turnstile?.enabled && turnstile.siteKey">
                            <TurnstileWidget
                                v-model="form.cf_turnstile_response"
                                :site-key="turnstile.siteKey"
                            />
                            <InputError class="mt-2" :message="form.errors.cf_turnstile_response" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Enviar ticket
                            </PrimaryButton>
                            <Link
                                :href="route('client.tickets.index')"
                                class="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Cancelar
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
