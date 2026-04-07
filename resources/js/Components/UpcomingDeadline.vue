<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex-1 min-h-0 flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
                <h3 class="font-semibold text-gray-900">Upcoming deadline</h3>
            </div>
            <span v-if="countdown" class="text-xs text-gray-500">{{ countdown }}</span>
        </div>
        <div v-if="title" class="text-sm font-medium text-gray-900 mb-1">{{ title }}</div>
        <div v-if="more" class="text-xs text-gray-500 mb-4">{{ more }}</div>
        <div v-else-if="!title" class="text-sm text-gray-500 mb-4">No upcoming deadlines</div>

        <button 
            v-if="title"
            @click="$emit('view-task')"
            class="w-full bg-blue-600 text-white text-sm py-2 rounded-md hover:bg-blue-700"
        >
            View task
        </button>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: null
    },
    more: {
        type: String,
        default: null
    },
    countdown: {
        type: String,
        default: null
    }
});

defineEmits(['view-task']);
</script>

