<template>
    <AppLayout>
        <div class="p-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">Tracking</h1>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" placeholder="Search..." class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <button class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Filters</button>
                    <button class="p-2 border border-gray-300 rounded-md hover:bg-gray-50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="grid gap-4" :style="{ gridTemplateColumns: `repeat(${Object.keys(columns).length}, minmax(250px, 1fr))` }">
                <!-- User Columns -->
                <div v-for="(column, columnId) in columns" :key="columnId" class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                            <h3 class="font-semibold text-gray-900">{{ column.name }}</h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded">
                                {{ (column.leads?.length || 0) + (column.todolists?.length || 0) }}
                            </span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Leads -->
                    <div v-for="lead in (column.leads || [])" :key="`lead-${lead.id}`" class="bg-white rounded-lg p-4 mb-3 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-2 h-2 rounded-full" :class="lead.relevant ? 'bg-green-500' : 'bg-red-500'"></div>
                            <span class="text-xs text-gray-500">{{ lead.status || 'No status' }}</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-2">{{ lead.shop_name }}</div>
                        <div class="space-y-1 text-xs text-gray-600">
                            <div v-if="lead.industry" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                {{ lead.industry }}
                            </div>
                            <div v-if="lead.city || lead.state" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ [lead.city, lead.state].filter(Boolean).join(', ') }}
                            </div>
                            <div v-if="lead.last_modified" class="flex items-center gap-1 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ formatDate(lead.last_modified) }}
                            </div>
                        </div>
                    </div>

                    <!-- Todolists -->
                    <div v-for="todolist in (column.todolists || [])" :key="`todolist-${todolist.id}`" class="bg-white rounded-lg p-4 mb-3 shadow-sm border-l-4 border-purple-500">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="text-xs font-medium text-purple-600">TODOLIST</span>
                            <span class="text-xs text-gray-500">{{ todolist.status || 'No status' }}</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-2">{{ todolist.title }}</div>
                        <div v-if="todolist.description" class="text-xs text-gray-600 mb-2 line-clamp-2">{{ todolist.description }}</div>
                        <div class="space-y-1 text-xs text-gray-600">
                            <div v-if="todolist.priority" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="font-medium">{{ todolist.priority }}</span>
                            </div>
                            <div v-if="todolist.lead" class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ todolist.lead.shop_name }}
                            </div>
                            <div v-if="todolist.start_date || todolist.end_date" class="flex items-center gap-1 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span v-if="todolist.start_date && todolist.end_date">
                                    {{ formatDateShort(todolist.start_date) }} - {{ formatDateShort(todolist.end_date) }}
                                </span>
                                <span v-else-if="todolist.start_date">{{ formatDateShort(todolist.start_date) }}</span>
                                <span v-else-if="todolist.end_date">{{ formatDateShort(todolist.end_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Pages/Layouts/AppLayout.vue';

defineProps({
    columns: Object,
    users: Array,
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};
</script>

