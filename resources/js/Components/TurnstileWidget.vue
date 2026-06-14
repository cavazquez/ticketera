<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    siteKey: {
        type: String,
        required: true,
    },
});

const model = defineModel({
    type: String,
    default: '',
});

const container = ref(null);
let widgetId = null;
let scriptLoaded = false;

const loadScript = () =>
    new Promise((resolve) => {
        if (window.turnstile) {
            resolve();
            return;
        }

        if (scriptLoaded) {
            const interval = setInterval(() => {
                if (window.turnstile) {
                    clearInterval(interval);
                    resolve();
                }
            }, 50);
            return;
        }

        scriptLoaded = true;
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        document.head.appendChild(script);
    });

const renderWidget = async () => {
    if (!container.value || !props.siteKey) {
        return;
    }

    await loadScript();

    if (widgetId !== null) {
        window.turnstile.remove(widgetId);
        widgetId = null;
    }

    widgetId = window.turnstile.render(container.value, {
        sitekey: props.siteKey,
        callback: (token) => {
            model.value = token;
        },
        'expired-callback': () => {
            model.value = '';
        },
        'error-callback': () => {
            model.value = '';
        },
    });
};

onMounted(renderWidget);

watch(
    () => props.siteKey,
    () => renderWidget()
);

onBeforeUnmount(() => {
    if (widgetId !== null && window.turnstile) {
        window.turnstile.remove(widgetId);
    }
});
</script>

<template>
    <div ref="container" class="min-h-[65px]" />
</template>
