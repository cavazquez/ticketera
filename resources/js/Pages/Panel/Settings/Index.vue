<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTrans } from '@/composables/useTrans';

const props = defineProps({
    settings: Object,
    authDrivers: Array,
    userRoles: Array,
    departments: Array,
    keycloakCallbackUrl: String,
    timezones: Array,
    locales: Object,
});

const { t } = useTrans();

const form = useForm({
    app_name: props.settings.app_name,
    support_email: props.settings.support_email || '',
    timezone: props.settings.timezone || 'UTC',
    locale: props.settings.locale || 'es',
    notify_on_reply: props.settings.notify_on_reply,
    notify_on_status_change: props.settings.notify_on_status_change,
    auto_assign_tickets: props.settings.auto_assign_tickets,
    allow_public_registration: props.settings.allow_public_registration,
    turnstile_enabled: props.settings.turnstile_enabled,
    turnstile_site_key: props.settings.turnstile_site_key || '',
    turnstile_secret_key: '',
    auth_driver: props.settings.auth_driver || 'local',
    allow_local_login: props.settings.allow_local_login ?? true,
    sso_auto_provision: props.settings.sso_auto_provision ?? true,
    sso_default_role: props.settings.sso_default_role || 'cliente',
    ldap_host: props.settings.ldap_host || '',
    ldap_port: props.settings.ldap_port ?? 389,
    ldap_base_dn: props.settings.ldap_base_dn || '',
    ldap_use_tls: props.settings.ldap_use_tls ?? false,
    ldap_username_attribute: props.settings.ldap_username_attribute || 'mail',
    ldap_bind_dn: props.settings.ldap_bind_dn || '',
    ldap_bind_password: '',
    keycloak_base_url: props.settings.keycloak_base_url || '',
    keycloak_realm: props.settings.keycloak_realm || '',
    keycloak_client_id: props.settings.keycloak_client_id || '',
    keycloak_client_secret: '',
    sla_baja_hours: props.settings.sla_baja_hours ?? '',
    sla_normal_hours: props.settings.sla_normal_hours ?? '',
    sla_alta_hours: props.settings.sla_alta_hours ?? '',
    sla_urgente_hours: props.settings.sla_urgente_hours ?? '',
    sla_warning_hours: props.settings.sla_warning_hours ?? 2,
    notify_sla_warnings: props.settings.notify_sla_warnings ?? true,
    notify_sla_breaches: props.settings.notify_sla_breaches ?? true,
    outbound_smtp_enabled: props.settings.outbound_smtp_enabled ?? false,
    smtp_host: props.settings.smtp_host || '',
    smtp_port: props.settings.smtp_port ?? 587,
    smtp_encryption: props.settings.smtp_encryption || 'tls',
    smtp_username: props.settings.smtp_username || '',
    smtp_password: '',
    smtp_from_address: props.settings.smtp_from_address || '',
    smtp_from_name: props.settings.smtp_from_name || '',
    inbound_email_enabled: props.settings.inbound_email_enabled ?? false,
    inbound_imap_host: props.settings.inbound_imap_host || '',
    inbound_imap_port: props.settings.inbound_imap_port ?? 993,
    inbound_imap_encryption: props.settings.inbound_imap_encryption || 'ssl',
    inbound_imap_username: props.settings.inbound_imap_username || '',
    inbound_imap_password: '',
    inbound_imap_folder: props.settings.inbound_imap_folder || 'INBOX',
    inbound_default_department_id: props.settings.inbound_default_department_id ?? '',
    inbound_auto_create_users: props.settings.inbound_auto_create_users ?? true,
});

const testingImap = ref(false);
const testingSmtp = ref(false);

const testImap = () => {
    testingImap.value = true;
    router.post(
        route('panel.settings.test-imap'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testingImap.value = false;
            },
        }
    );
};

const testSmtp = () => {
    testingSmtp.value = true;
    router.post(
        route('panel.settings.test-smtp'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testingSmtp.value = false;
            },
        }
    );
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        sla_baja_hours: data.sla_baja_hours === '' ? null : Number(data.sla_baja_hours),
        sla_normal_hours: data.sla_normal_hours === '' ? null : Number(data.sla_normal_hours),
        sla_alta_hours: data.sla_alta_hours === '' ? null : Number(data.sla_alta_hours),
        sla_urgente_hours: data.sla_urgente_hours === '' ? null : Number(data.sla_urgente_hours),
        sla_warning_hours: data.sla_warning_hours === '' ? null : Number(data.sla_warning_hours),
        support_email: data.support_email || null,
        turnstile_site_key: data.turnstile_site_key || null,
        turnstile_secret_key: data.turnstile_secret_key || null,
        ldap_host: data.ldap_host || null,
        ldap_base_dn: data.ldap_base_dn || null,
        ldap_username_attribute: data.ldap_username_attribute || null,
        ldap_bind_dn: data.ldap_bind_dn || null,
        ldap_bind_password: data.ldap_bind_password || null,
        keycloak_base_url: data.keycloak_base_url || null,
        keycloak_realm: data.keycloak_realm || null,
        keycloak_client_id: data.keycloak_client_id || null,
        keycloak_client_secret: data.keycloak_client_secret || null,
        smtp_host: data.smtp_host || null,
        smtp_username: data.smtp_username || null,
        smtp_password: data.smtp_password || null,
        smtp_from_address: data.smtp_from_address || null,
        smtp_from_name: data.smtp_from_name || null,
        inbound_imap_host: data.inbound_imap_host || null,
        inbound_imap_username: data.inbound_imap_username || null,
        inbound_imap_password: data.inbound_imap_password || null,
        inbound_imap_folder: data.inbound_imap_folder || null,
        inbound_default_department_id:
            data.inbound_default_department_id === ''
                ? null
                : Number(data.inbound_default_department_id),
    })).patch(route('panel.settings.update'));
};
</script>

<template>
    <Head title="Configuración" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Configuración</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <FlashMessage />

                <form @submit.prevent="submit" class="space-y-6">
                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">General</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Identidad del sistema, contacto de soporte y huso horario.
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <InputLabel for="app_name" value="Nombre de la aplicación" />
                                <TextInput
                                    id="app_name"
                                    v-model="form.app_name"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.app_name" />
                            </div>
                            <div>
                                <InputLabel for="support_email" value="Correo de soporte" />
                                <TextInput
                                    id="support_email"
                                    v-model="form.support_email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    placeholder="soporte@empresa.com"
                                />
                                <InputError class="mt-2" :message="form.errors.support_email" />
                            </div>
                            <div>
                                <InputLabel for="timezone" value="Huso horario" />
                                <select
                                    id="timezone"
                                    v-model="form.timezone"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <template v-for="group in timezones" :key="group.region">
                                        <optgroup :label="group.region">
                                            <option
                                                v-for="option in group.options"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }} ({{ option.value }})
                                            </option>
                                        </optgroup>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Afecta fechas en el panel, notificaciones y diagnóstico del
                                    sistema.
                                </p>
                                <InputError class="mt-2" :message="form.errors.timezone" />
                            </div>
                            <div>
                                <InputLabel for="locale" :value="t('settings.locale')" />
                                <select
                                    id="locale"
                                    v-model="form.locale"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option
                                        v-for="(label, code) in locales"
                                        :key="code"
                                        :value="code"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ t('settings.locale_help') }}
                                </p>
                                <InputError class="mt-2" :message="form.errors.locale" />
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Notificaciones</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Controlá qué eventos envían correos automáticos.
                        </p>

                        <div class="mt-4 space-y-3">
                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    v-model="form.notify_on_reply"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Notificar cuando hay una nueva respuesta
                            </label>
                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    v-model="form.notify_on_status_change"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Notificar cuando cambia el estado del ticket
                            </label>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Email saliente (SMTP)</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Enviá notificaciones reales por correo. Si está desactivado, se usa la
                            configuración de <code>.env</code> (en desarrollo suele ser
                            <code>log</code>). Los asuntos incluyen un token firmado, por ejemplo
                            <code>[TKT-000001-a1b2c3d4]</code>.
                        </p>

                        <label class="mt-4 flex items-center gap-3 text-sm text-gray-700">
                            <input
                                v-model="form.outbound_smtp_enabled"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Usar SMTP configurado en el panel
                        </label>

                        <div v-if="form.outbound_smtp_enabled" class="mt-4 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="smtp_host" value="Host SMTP" />
                                    <TextInput
                                        id="smtp_host"
                                        v-model="form.smtp_host"
                                        class="mt-1 block w-full"
                                        placeholder="smtp.ejemplo.com"
                                    />
                                    <InputError class="mt-2" :message="form.errors.smtp_host" />
                                </div>
                                <div>
                                    <InputLabel for="smtp_port" value="Puerto" />
                                    <TextInput
                                        id="smtp_port"
                                        v-model="form.smtp_port"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.smtp_port" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="smtp_encryption" value="Cifrado" />
                                    <select
                                        id="smtp_encryption"
                                        v-model="form.smtp_encryption"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="tls">TLS (587)</option>
                                        <option value="ssl">SSL (465)</option>
                                        <option value="none">Ninguno</option>
                                    </select>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.smtp_encryption"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="smtp_username" value="Usuario SMTP" />
                                    <TextInput
                                        id="smtp_username"
                                        v-model="form.smtp_username"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError class="mt-2" :message="form.errors.smtp_username" />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="smtp_password" value="Contraseña SMTP" />
                                    <TextInput
                                        id="smtp_password"
                                        v-model="form.smtp_password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        :placeholder="
                                            settings.smtp_password_set
                                                ? '•••••••• (sin cambios)'
                                                : ''
                                        "
                                    />
                                    <InputError class="mt-2" :message="form.errors.smtp_password" />
                                </div>
                                <div>
                                    <InputLabel
                                        for="smtp_from_address"
                                        value="Remitente (opcional)"
                                    />
                                    <TextInput
                                        id="smtp_from_address"
                                        v-model="form.smtp_from_address"
                                        type="email"
                                        class="mt-1 block w-full"
                                        placeholder="Por defecto: correo de soporte"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.smtp_from_address"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    for="smtp_from_name"
                                    value="Nombre del remitente (opcional)"
                                />
                                <TextInput
                                    id="smtp_from_name"
                                    v-model="form.smtp_from_name"
                                    class="mt-1 block w-full"
                                    placeholder="Por defecto: nombre de la aplicación"
                                />
                                <InputError class="mt-2" :message="form.errors.smtp_from_name" />
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <SecondaryButton
                                    type="button"
                                    :disabled="testingSmtp"
                                    @click="testSmtp"
                                >
                                    {{ testingSmtp ? 'Enviando…' : 'Probar SMTP' }}
                                </SecondaryButton>
                                <p class="text-xs text-gray-500">
                                    Guardá los cambios antes de probar. El correo de prueba se envía
                                    a tu email de administrador.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Email entrante (IMAP)</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Importá tickets y respuestas desde un buzón IMAP. Respondé a los correos
                            salientes (incluyen token firmado en el asunto). El scheduler ejecuta
                            <code>tickets:fetch-email</code> cada 5 minutos.
                        </p>

                        <label class="mt-4 flex items-center gap-3 text-sm text-gray-700">
                            <input
                                v-model="form.inbound_email_enabled"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Activar email entrante
                        </label>

                        <div v-if="form.inbound_email_enabled" class="mt-4 space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="inbound_imap_host" value="Host IMAP" />
                                    <TextInput
                                        id="inbound_imap_host"
                                        v-model="form.inbound_imap_host"
                                        class="mt-1 block w-full"
                                        placeholder="imap.ejemplo.com"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.inbound_imap_host"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="inbound_imap_port" value="Puerto" />
                                    <TextInput
                                        id="inbound_imap_port"
                                        v-model="form.inbound_imap_port"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.inbound_imap_port"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="inbound_imap_encryption" value="Cifrado" />
                                    <select
                                        id="inbound_imap_encryption"
                                        v-model="form.inbound_imap_encryption"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                        <option value="none">Ninguno</option>
                                    </select>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.inbound_imap_encryption"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="inbound_imap_folder" value="Carpeta" />
                                    <TextInput
                                        id="inbound_imap_folder"
                                        v-model="form.inbound_imap_folder"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.inbound_imap_folder"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="inbound_imap_username" value="Usuario IMAP" />
                                    <TextInput
                                        id="inbound_imap_username"
                                        v-model="form.inbound_imap_username"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.inbound_imap_username"
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        for="inbound_imap_password"
                                        value="Contraseña IMAP"
                                    />
                                    <TextInput
                                        id="inbound_imap_password"
                                        v-model="form.inbound_imap_password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        :placeholder="
                                            settings.inbound_imap_password_set
                                                ? '•••••••• (sin cambios)'
                                                : ''
                                        "
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.inbound_imap_password"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    for="inbound_default_department_id"
                                    value="Departamento por defecto"
                                />
                                <select
                                    id="inbound_default_department_id"
                                    v-model="form.inbound_default_department_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Primer departamento activo</option>
                                    <option
                                        v-for="department in departments"
                                        :key="department.id"
                                        :value="department.id"
                                    >
                                        {{ department.name }}
                                    </option>
                                </select>
                                <InputError
                                    class="mt-2"
                                    :message="form.errors.inbound_default_department_id"
                                />
                            </div>

                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    v-model="form.inbound_auto_create_users"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Crear usuarios cliente automáticamente si no existen
                            </label>

                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                <SecondaryButton
                                    type="button"
                                    :disabled="testingImap"
                                    @click="testImap"
                                >
                                    {{ testingImap ? 'Probando…' : 'Probar IMAP' }}
                                </SecondaryButton>
                                <p class="text-xs text-gray-500">
                                    Guardá los cambios antes de probar la conexión al buzón.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Asignación automática</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Al crear un ticket, asignarlo al agente del departamento con menos
                            tickets abiertos.
                        </p>

                        <label class="mt-4 flex items-center gap-3 text-sm text-gray-700">
                            <input
                                v-model="form.auto_assign_tickets"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Activar asignación automática
                        </label>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Seguridad</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Acceso al sistema y protección anti-bots en formularios.
                        </p>

                        <div class="mt-4 space-y-4">
                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    v-model="form.allow_public_registration"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Permitir registro público
                            </label>

                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    v-model="form.turnstile_enabled"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Activar Cloudflare Turnstile al crear tickets
                            </label>

                            <div v-if="form.turnstile_enabled" class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="turnstile_site_key" value="Site key" />
                                    <TextInput
                                        id="turnstile_site_key"
                                        v-model="form.turnstile_site_key"
                                        class="mt-1 block w-full"
                                        placeholder="0x..."
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.turnstile_site_key"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="turnstile_secret_key" value="Secret key" />
                                    <TextInput
                                        id="turnstile_secret_key"
                                        v-model="form.turnstile_secret_key"
                                        type="password"
                                        class="mt-1 block w-full"
                                        :placeholder="
                                            settings.turnstile_secret_key_set
                                                ? '•••••••• (dejar vacío para no cambiar)'
                                                : 'Secret key de Cloudflare'
                                        "
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.turnstile_secret_key"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Autenticación (SSO)</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Elegí el proveedor de identidad para el inicio de sesión.
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <InputLabel for="auth_driver" value="Método de autenticación" />
                                <select
                                    id="auth_driver"
                                    v-model="form.auth_driver"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="driver in authDrivers"
                                        :key="driver.value"
                                        :value="driver.value"
                                    >
                                        {{ driver.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.auth_driver" />
                            </div>

                            <label
                                v-if="form.auth_driver === 'keycloak'"
                                class="flex items-center gap-3 text-sm text-gray-700"
                            >
                                <input
                                    v-model="form.allow_local_login"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Permitir login local como respaldo
                            </label>

                            <label class="flex items-center gap-3 text-sm text-gray-700">
                                <input
                                    v-model="form.sso_auto_provision"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Crear usuarios automáticamente al primer login SSO
                            </label>

                            <div>
                                <InputLabel
                                    for="sso_default_role"
                                    value="Rol por defecto (auto-provisión)"
                                />
                                <select
                                    id="sso_default_role"
                                    v-model="form.sso_default_role"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option
                                        v-for="role in userRoles"
                                        :key="role.value"
                                        :value="role.value"
                                    >
                                        {{ role.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.sso_default_role" />
                            </div>

                            <div
                                v-if="form.auth_driver === 'ldap'"
                                class="space-y-4 border-t border-gray-100 pt-4"
                            >
                                <h4 class="font-medium text-gray-900">LDAP / Active Directory</h4>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="ldap_host" value="Host" />
                                        <TextInput
                                            id="ldap_host"
                                            v-model="form.ldap_host"
                                            class="mt-1 block w-full"
                                            placeholder="ldap.empresa.com"
                                        />
                                        <InputError class="mt-2" :message="form.errors.ldap_host" />
                                    </div>
                                    <div>
                                        <InputLabel for="ldap_port" value="Puerto" />
                                        <TextInput
                                            id="ldap_port"
                                            v-model="form.ldap_port"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError class="mt-2" :message="form.errors.ldap_port" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel for="ldap_base_dn" value="Base DN" />
                                    <TextInput
                                        id="ldap_base_dn"
                                        v-model="form.ldap_base_dn"
                                        class="mt-1 block w-full"
                                        placeholder="dc=empresa,dc=com"
                                    />
                                    <InputError class="mt-2" :message="form.errors.ldap_base_dn" />
                                </div>

                                <label class="flex items-center gap-3 text-sm text-gray-700">
                                    <input
                                        v-model="form.ldap_use_tls"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    Usar TLS (LDAPS)
                                </label>

                                <div>
                                    <InputLabel
                                        for="ldap_username_attribute"
                                        value="Atributo de usuario"
                                    />
                                    <TextInput
                                        id="ldap_username_attribute"
                                        v-model="form.ldap_username_attribute"
                                        class="mt-1 block w-full"
                                        placeholder="mail"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.ldap_username_attribute"
                                    />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="ldap_bind_dn" value="Bind DN (opcional)" />
                                        <TextInput
                                            id="ldap_bind_dn"
                                            v-model="form.ldap_bind_dn"
                                            class="mt-1 block w-full"
                                            placeholder="cn=admin,dc=empresa,dc=com"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.ldap_bind_dn"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            for="ldap_bind_password"
                                            value="Bind password (opcional)"
                                        />
                                        <TextInput
                                            id="ldap_bind_password"
                                            v-model="form.ldap_bind_password"
                                            type="password"
                                            class="mt-1 block w-full"
                                            :placeholder="
                                                settings.ldap_bind_password_set
                                                    ? '•••••••• (dejar vacío para no cambiar)'
                                                    : 'Contraseña del bind DN'
                                            "
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.ldap_bind_password"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="form.auth_driver === 'keycloak'"
                                class="space-y-4 border-t border-gray-100 pt-4"
                            >
                                <h4 class="font-medium text-gray-900">Keycloak</h4>
                                <p class="text-sm text-gray-600">
                                    Redirect URI en Keycloak:
                                    <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">{{
                                        keycloakCallbackUrl
                                    }}</code>
                                </p>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="keycloak_base_url" value="URL base" />
                                        <TextInput
                                            id="keycloak_base_url"
                                            v-model="form.keycloak_base_url"
                                            class="mt-1 block w-full"
                                            placeholder="https://auth.empresa.com"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.keycloak_base_url"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel for="keycloak_realm" value="Realm" />
                                        <TextInput
                                            id="keycloak_realm"
                                            v-model="form.keycloak_realm"
                                            class="mt-1 block w-full"
                                            placeholder="ticketera"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.keycloak_realm"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel for="keycloak_client_id" value="Client ID" />
                                        <TextInput
                                            id="keycloak_client_id"
                                            v-model="form.keycloak_client_id"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.keycloak_client_id"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            for="keycloak_client_secret"
                                            value="Client secret"
                                        />
                                        <TextInput
                                            id="keycloak_client_secret"
                                            v-model="form.keycloak_client_secret"
                                            type="password"
                                            class="mt-1 block w-full"
                                            :placeholder="
                                                settings.keycloak_client_secret_set
                                                    ? '•••••••• (dejar vacío para no cambiar)'
                                                    : 'Secret del cliente OIDC'
                                            "
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="form.errors.keycloak_client_secret"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">SLA por prioridad</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Tiempo máximo de resolución en horas. Dejá vacío para desactivar el SLA
                            de esa prioridad.
                        </p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="sla_baja_hours" value="Baja (horas)" />
                                <TextInput
                                    id="sla_baja_hours"
                                    v-model="form.sla_baja_hours"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.sla_baja_hours" />
                            </div>
                            <div>
                                <InputLabel for="sla_normal_hours" value="Normal (horas)" />
                                <TextInput
                                    id="sla_normal_hours"
                                    v-model="form.sla_normal_hours"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.sla_normal_hours" />
                            </div>
                            <div>
                                <InputLabel for="sla_alta_hours" value="Alta (horas)" />
                                <TextInput
                                    id="sla_alta_hours"
                                    v-model="form.sla_alta_hours"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.sla_alta_hours" />
                            </div>
                            <div>
                                <InputLabel for="sla_urgente_hours" value="Urgente (horas)" />
                                <TextInput
                                    id="sla_urgente_hours"
                                    v-model="form.sla_urgente_hours"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full"
                                />
                                <InputError class="mt-2" :message="form.errors.sla_urgente_hours" />
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <h4 class="font-medium text-gray-900">Alertas de SLA</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                El scheduler envía emails al agente asignado (o al departamento) y a
                                los admins. Requiere el servicio <code>scheduler</code> en Docker.
                            </p>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel
                                        for="sla_warning_hours"
                                        value="Aviso previo (horas antes del vencimiento)"
                                    />
                                    <TextInput
                                        id="sla_warning_hours"
                                        v-model="form.sla_warning_hours"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.sla_warning_hours"
                                    />
                                </div>
                                <div class="space-y-3 sm:col-span-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            v-model="form.notify_sla_warnings"
                                            type="checkbox"
                                            class="rounded border-gray-300"
                                        />
                                        Enviar aviso antes del vencimiento
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            v-model="form.notify_sla_breaches"
                                            type="checkbox"
                                            class="rounded border-gray-300"
                                        />
                                        Enviar alerta cuando el SLA esté vencido
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            Guardar configuración
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
