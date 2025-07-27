<script setup>
const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['toggle']);

const toggleItem = () => {
    emit('toggle', props.index);
};
</script>

<template>
    <div
        @click="toggleItem"
        class="flex items-center space-x-3 p-2 rounded-lg transition-colors duration-200 cursor-pointer"
        :class="item.completed ? 'bg-green-50 hover:bg-green-100' : 'bg-gray-50 hover:bg-gray-100'"
    >
        <div :class="[
            'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0',
            item.completed ? 'bg-green-500 border-green-500' : 'border-gray-300'
        ]">
            <svg v-if="item.completed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <span :class="[
            'text-sm',
            item.completed ? 'line-through text-gray-500' : 'text-gray-800'
        ]">
            {{ item.text }}
        </span>
    </div>
</template>
