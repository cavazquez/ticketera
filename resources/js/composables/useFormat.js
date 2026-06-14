import { usePage } from '@inertiajs/vue3';

const intlLocales = {
    es: 'es-AR',
    en: 'en-US',
};

export function useFormat() {
    const page = usePage();

    const intlLocale = () => intlLocales[page.props.locale] ?? undefined;

    const formatDateTime = (value) => {
        if (!value) {
            return '';
        }

        return new Date(value).toLocaleString(intlLocale());
    };

    return { formatDateTime };
}
