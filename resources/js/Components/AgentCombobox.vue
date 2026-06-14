<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    selectedAgent: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');
const results = ref([]);
const isOpen = ref(false);
const isLoading = ref(false);

const displayLabel = computed(() => {
    if (props.modelValue === 'unassigned') {
        return 'Sin asignar';
    }

    if (props.selectedAgent && String(props.selectedAgent.id) === String(props.modelValue)) {
        return props.selectedAgent.name;
    }

    const match = results.value.find((agent) => String(agent.id) === String(props.modelValue));

    return match?.name || query.value;
});

const searchAgents = async () => {
    isLoading.value = true;

    try {
        const { data } = await axios.get(route('panel.agents.search'), {
            params: { q: query.value },
        });
        results.value = data;
    } finally {
        isLoading.value = false;
    }
};

const selectAgent = (value) => {
    emit('update:modelValue', value === '' ? '' : String(value));
    isOpen.value = false;

    if (value === '' || value === 'unassigned') {
        query.value = value === 'unassigned' ? 'Sin asignar' : '';
    } else {
        const agent = results.value.find((item) => String(item.id) === String(value));
        query.value = agent?.name || displayLabel.value;
    }
};

const onInput = () => {
    isOpen.value = true;
    emit('update:modelValue', '');
    searchAgents();
};

const onFocus = () => {
    isOpen.value = true;
    searchAgents();
};

watch(
    () => props.modelValue,
    (value) => {
        if (!value) {
            query.value = '';
        } else if (value === 'unassigned') {
            query.value = 'Sin asignar';
        } else if (props.selectedAgent && String(props.selectedAgent.id) === String(value)) {
            query.value = props.selectedAgent.name;
        }
    },
    { immediate: true }
);

onMounted(() => {
    if (props.selectedAgent) {
        results.value = [props.selectedAgent];
    }
});
</script>

<template>
    <div class="relative">
        <input
            v-model="query"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Buscar agente..."
            autocomplete="off"
            @focus="onFocus"
            @input="onInput"
        />

        <div
            v-if="isOpen"
            class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"
        >
            <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50"
                @mousedown.prevent="selectAgent('')"
            >
                Todos los agentes
            </button>
            <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50"
                @mousedown.prevent="selectAgent('unassigned')"
            >
                Sin asignar
            </button>
            <div v-if="isLoading" class="px-3 py-2 text-sm text-gray-500">Buscando...</div>
            <button
                v-for="agent in results"
                :key="agent.id"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50"
                @mousedown.prevent="selectAgent(agent.id)"
            >
                <span class="font-medium">{{ agent.name }}</span>
                <span class="ml-2 text-gray-500">{{ agent.email }}</span>
            </button>
            <div
                v-if="!isLoading && results.length === 0 && query"
                class="px-3 py-2 text-sm text-gray-500"
            >
                Sin resultados
            </div>
        </div>
    </div>
</template>
