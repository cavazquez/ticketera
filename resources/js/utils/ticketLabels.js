import { useTrans } from '@/composables/useTrans';

export const statusColor = (status) =>
    ({
        abierto: 'blue',
        en_progreso: 'yellow',
        resuelto: 'green',
        cerrado: 'gray',
    })[status] || 'gray';

export const priorityColor = (priority) =>
    ({
        baja: 'gray',
        normal: 'blue',
        alta: 'orange',
        urgente: 'red',
    })[priority] || 'gray';

export function useTicketLabels() {
    const { t } = useTrans();

    const statusLabels = {
        abierto: t('ticket.status.abierto'),
        en_progreso: t('ticket.status.en_progreso'),
        resuelto: t('ticket.status.resuelto'),
        cerrado: t('ticket.status.cerrado'),
    };

    const priorityLabels = {
        baja: t('ticket.priority.baja'),
        normal: t('ticket.priority.normal'),
        alta: t('ticket.priority.alta'),
        urgente: t('ticket.priority.urgente'),
    };

    const roleLabels = {
        cliente: t('user.role.cliente'),
        agente: t('user.role.agente'),
        admin: t('user.role.admin'),
    };

    return { statusLabels, priorityLabels, roleLabels, statusColor, priorityColor };
}

// Backward-compatible static exports (fallback Spanish)
export const statusLabels = {
    abierto: 'Abierto',
    en_progreso: 'En progreso',
    resuelto: 'Resuelto',
    cerrado: 'Cerrado',
};

export const priorityLabels = {
    baja: 'Baja',
    normal: 'Normal',
    alta: 'Alta',
    urgente: 'Urgente',
};

export const roleLabels = {
    cliente: 'Cliente',
    agente: 'Agente',
    admin: 'Administrador',
};
