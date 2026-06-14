import { usePage } from '@inertiajs/vue3';

export function useTrans() {
    const page = usePage();

    const t = (key, replacements = {}) => {
        let value = page.props.translations?.[key] ?? key;

        Object.entries(replacements).forEach(([placeholder, replacement]) => {
            value = value.replace(`:${placeholder}`, String(replacement));
        });

        return value;
    };

    return {
        t,
        locale: () => page.props.locale,
        locales: () => page.props.locales ?? {},
    };
}
