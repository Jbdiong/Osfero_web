<template>
    <AppLayout>
        <div class="p-8">
            <!-- Filters and Actions -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button 
                        @click="showNewLeadPanel = true"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700"
                    >
                        + New leads
                    </button>
                    <div class="text-sm text-gray-600">
                        Date from <span class="font-medium">1 Oct 2024</span> to <span class="font-medium">31 Oct 2024</span>
                    </div>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50">Beauty</button>
                        <button class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50">Source</button>
                        <button class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50">Location</button>
                        <button class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50">Status</button>
                        <button class="px-3 py-1 text-xs border border-gray-300 rounded-md hover:bg-gray-50">Payment</button>
                    </div>
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

            <!-- Leads Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Relevancy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last modified</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="lead in leads" :key="lead.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ lead.contact_info.phone }}</div>
                                <div class="text-sm text-gray-500">{{ lead.contact_info.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ lead.shop_info.name }}</div>
                                <div class="text-sm text-gray-500">{{ lead.shop_info.category }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ lead.location.address }}</div>
                                <div class="text-sm text-gray-500">{{ lead.location.city }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ lead.source }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    lead.relevancy === 'Relevant' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]">
                                    <span :class="[
                                        'w-1.5 h-1.5 rounded-full mr-1.5',
                                        lead.relevancy === 'Relevant' ? 'bg-green-600' : 'bg-red-600'
                                    ]"></span>
                                    {{ lead.relevancy }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ lead.status }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    lead.payment === 'Done' ? 'bg-green-100 text-green-800' :
                                    lead.payment === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                                    lead.payment === 'Rejected' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                ]">
                                    <span :class="[
                                        'w-1.5 h-1.5 rounded-full mr-1.5',
                                        lead.payment === 'Done' ? 'bg-green-600' :
                                        lead.payment === 'Pending' ? 'bg-yellow-600' :
                                        lead.payment === 'Rejected' ? 'bg-red-600' :
                                        'bg-gray-600'
                                    ]"></span>
                                    {{ lead.payment }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ lead.last_modified }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 mr-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- New Lead Panel -->
        <NewLeadPanel 
            :isOpen="showNewLeadPanel" 
            @close="showNewLeadPanel = false"
            @created="handleLeadCreated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/Pages/Layouts/AppLayout.vue';
import NewLeadPanel from '@/Components/NewLeadPanel.vue';
import axios from 'axios';

const props = defineProps({
    leads: Array,
    stats: Object,
});

const showNewLeadPanel = ref(false);
const leads = ref(props.leads || []);
const stats = ref(props.stats || {});

const loadLeads = async () => {
    try {
        const response = await axios.get('/api/v1/leads');
        leads.value = response.data.data;
    } catch (error) {
        console.error('Error loading leads:', error);
        console.error('Response:', error.response);
        console.error('Status:', error.response?.status);
        console.error('Data:', error.response?.data);
    }
};

const handleLeadCreated = (newLead) => {
    // Add the new lead to the list
    leads.value.unshift(newLead);
    // Optionally reload all leads to get updated stats
    loadLeads();
};

// Load leads on mount if not provided via props
onMounted(() => {
    if (!props.leads || props.leads.length === 0) {
        loadLeads();
    }
});
</script>


