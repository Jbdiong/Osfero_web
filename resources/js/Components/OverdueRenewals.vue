<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex-1 min-h-0 flex flex-col">
        <div class="flex items-center justify-between mb-4 flex-shrink-0">
            <h3 class="font-semibold text-gray-900 text-xl">Renewal</h3>
            <button 
                v-if="showNewButton"
                @click="$emit('new-renewal')"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700"
            >
                + New renewal
            </button>
        </div>
        <div class="space-y-3 flex-1 min-h-0 overflow-y-auto">
            <div v-if="renewals && renewals.length > 0" class="space-y-2">
                <div v-for="renewal in renewals" :key="renewal.name" class="flex items-center gap-2 px-2 py-1 rounded-md" :class="renewal.is_overdue ? 'bg-red-100' : 'bg-orange-100'">
                    <svg class="w-4 h-4" :class="renewal.is_overdue ? 'text-red-500' : 'text-orange-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-gray-900">{{ renewal.name }}</div>
                        <div :class="[
                            'text-xs font-medium',
                            renewal.is_overdue ? 'text-red-600' : 'text-orange-600',
                        ]">
                            {{ renewal.is_overdue ? 'Overdue: ' : 'Due in ' + renewal.days_until + ' day' + (renewal.days_until !== 1 ? 's' : '') + ': ' }}{{ renewal.date }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-sm text-gray-500 py-2">
                No overdue or upcoming renewals
            </div>
            <button 
                v-if="showViewAllButton"
                @click="$emit('view-all')"
                class="text-xs text-blue-600 hover:underline"
            >
                View all
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    renewals: {
        type: Array,
        default: () => []
    },
    showNewButton: {
        type: Boolean,
        default: true
    },
    showViewAllButton: {
        type: Boolean,
        default: true
    }
});

defineEmits(['new-renewal', 'view-all']);
</script>

