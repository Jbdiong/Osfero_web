<template>
    <div v-if="isOpen" :class="inline ? 'relative' : 'fixed inset-0 z-50 overflow-hidden'">
        <!-- Backdrop -->
        <div v-if="!inline" class="absolute inset-0" style="background-color: rgba(0, 0, 0, 0.5);" @click="close"></div>
        
        <!-- Modal -->
        <div :class="[
            inline ? 'relative w-full bg-white rounded-lg border border-gray-200 shadow-sm flex flex-col' : 'absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl bg-white rounded-lg shadow-xl max-h-[90vh] flex flex-col'
        ]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-gray-900">Renewals</h2>
                    <span class="text-sm text-gray-500">({{ showAuditView ? audits.length : renewals.length }} {{ showAuditView ? 'audits' : 'total' }})</span>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="showAuditView = false"
                        :class="['px-3 py-1 text-sm rounded-md font-medium', !showAuditView ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        Renewals
                    </button>
                    <button
                        @click="showAuditView = true"
                        :class="['px-3 py-1 text-sm rounded-md font-medium', showAuditView ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']"
                    >
                        Audit Log
                    </button>
                    <button v-if="!inline" @click="close" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div v-if="loading || loadingAudits" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-500">Loading {{ showAuditView ? 'audits' : 'renewals' }}...</p>
                </div>
                
                <div v-else-if="showAuditView && audits.length === 0" class="text-center py-8">
                    <p class="text-gray-500">No audit logs found for renewals.</p>
                </div>
                <div v-else-if="!showAuditView && renewals.length === 0" class="text-center py-8">
                    <p class="text-gray-500">No renewals found.</p>
                </div>

                <!-- Renewals Table -->
                <table v-else-if="!showAuditView" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renew Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="renewal in renewals" :key="renewal.id" class="hover:bg-gray-50" :class="isOverdue(renewal.Renew_Date) ? 'bg-red-100 ' : 'bg-gray-100 '">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ renewal.label || '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(renewal.start_date) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <span :class="[
                                    'px-2 py-1 rounded text-xs font-medium',
                                    isOverdue(renewal.Renew_Date) ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                ]">
                                    {{ formatDate(renewal.Renew_Date) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <select
                                    v-model="renewal.status_id"
                                    @change="updateStatus(renewal)"
                                    :disabled="updatingIds.includes(renewal.id)"
                                    class="text-sm border border-gray-300 rounded-md px-3 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <option :value="null">No Status</option>
                                    <option v-for="status in renewalStatuses" :key="status.id" :value="status.id">
                                        {{ status.name || status.label }}
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Audit Log Table -->
                <table v-else class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renewal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Field</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changes</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="audit in audits" :key="audit.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ audit.renewal_label || '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span :class="[
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    audit.audit_type === 'Create' ? 'bg-green-100 text-green-800' :
                                    audit.audit_type === 'Update' ? 'bg-blue-100 text-blue-800' :
                                    audit.audit_type === 'Delete' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                ]">
                                    {{ audit.audit_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                {{ audit.performed_by_user?.name || 'System' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ audit.column_name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div v-if="audit.old_value !== null && audit.new_value !== null" class="whitespace-normal">
                                    <span class="line-through text-red-500">{{ audit.formatted_old_value || audit.old_value }}</span> → <span class="text-green-600">{{ audit.formatted_new_value || audit.new_value }}</span>
                                </div>
                                <div v-else-if="audit.new_value !== null" class="whitespace-normal">{{ audit.formatted_new_value || audit.new_value }}</div>
                                <div v-else-if="audit.old_value !== null" class="whitespace-normal">{{ audit.formatted_old_value || audit.old_value }}</div>
                                <div v-else>—</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(audit.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                <div class="text-sm text-gray-500">
                    <span v-if="overdueCount > 0" class="text-red-600 font-medium">{{ overdueCount }} overdue</span>
                </div>
                <button
                    v-if="!inline"
                    @click="close"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md font-medium hover:bg-gray-200 transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    inline: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'updated']);

const loading = ref(false);
const loadingAudits = ref(false);
const renewals = ref([]);
const renewalStatuses = ref([]);
const updatingIds = ref([]);
const audits = ref([]);
const showAuditView = ref(false);

// Load renewal statuses
const loadRenewalStatuses = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Renewal Status'));
        renewalStatuses.value = response.data.data;
    } catch (error) {
        console.error('Error loading renewal statuses:', error);
    }
};

// Load renewals
const loadRenewals = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/v1/renewals');
        renewals.value = response.data.data;
    } catch (error) {
        console.error('Error loading renewals:', error);
        alert('Failed to load renewals. Please try again.');
    } finally {
        loading.value = false;
    }
};

// Update renewal status
const updateStatus = async (renewal) => {
    updatingIds.value.push(renewal.id);
    try {
        const response = await axios.patch(`/api/v1/renewals/${renewal.id}`, {
            status_id: renewal.status_id,
        });
        
        // Update the renewal in the list
        const index = renewals.value.findIndex(r => r.id === renewal.id);
        if (index !== -1) {
            renewals.value[index] = response.data.data;
        }
        
        // Reload audit log to show the status change
        await loadAudits();
        
        // Reload shared props to update sidebar badge (preserve scroll to avoid disruption)
        router.reload({ preserveScroll: true });
        
        emit('updated', response.data.data);
    } catch (error) {
        console.error('Error updating renewal:', error);
        alert(error.response?.data?.message || 'Failed to update renewal status. Please try again.');
        // Revert the change
        await loadRenewals();
    } finally {
        updatingIds.value = updatingIds.value.filter(id => id !== renewal.id);
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Format audit value for display (parse JSON if needed)
const formatAuditValue = (value, auditType) => {
    if (!value) return '—';
    
    // For Create actions, try to parse JSON and format it
    if (auditType === 'Create') {
        try {
            const parsed = JSON.parse(value);
            if (typeof parsed === 'object' && parsed !== null) {
                const parts = [];
                
                if (parsed.label) {
                    parts.push(`Label: ${parsed.label}`);
                }
                
                if (parsed.start_date) {
                    const startDate = new Date(parsed.start_date);
                    if (!isNaN(startDate.getTime())) {
                        parts.push(`Start Date: ${startDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`);
                    }
                }
                
                if (parsed.Renew_Date) {
                    const renewDate = new Date(parsed.Renew_Date);
                    if (!isNaN(renewDate.getTime())) {
                        parts.push(`Renew Date: ${renewDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`);
                    }
                }
                
                if (parsed.status_id) {
                    // Try to find status name from renewalStatuses
                    const status = renewalStatuses.value.find(s => s.id === parsed.status_id);
                    if (status) {
                        parts.push(`Status: ${status.name || status.label}`);
                    } else {
                        parts.push(`Status ID: ${parsed.status_id}`);
                    }
                }
                
                if (parsed.lead_id) {
                    parts.push(`Lead ID: ${parsed.lead_id}`);
                }
                
                return parts.length > 0 ? parts.join(' | ') : value;
            }
        } catch (e) {
            // Not JSON, return as is
        }
    }
    
    // For Update actions, format dates if they look like dates
    if (auditType === 'Update') {
        // Check if it's a date string (YYYY-MM-DD format)
        const dateMatch = value.match(/^\d{4}-\d{2}-\d{2}$/);
        if (dateMatch) {
            const date = new Date(value);
            if (!isNaN(date.getTime())) {
                return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            }
        }
    }
    
    return value;
};

// Check if date is overdue
const isOverdue = (date) => {
    if (!date) return false;
    const renewDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    renewDate.setHours(0, 0, 0, 0);
    return renewDate < today;
};

// Count overdue renewals
const overdueCount = computed(() => {
    return renewals.value.filter(r => isOverdue(r.Renew_Date)).length;
});

// Load audits
const loadAudits = async () => {
    loadingAudits.value = true;
    try {
        const response = await axios.get('/api/v1/renewals/audits');
        audits.value = response.data.data.map(audit => ({
            ...audit,
            renewal_label: audit.renewal?.label || audit.renewal?.lead?.Shop_Name || 'Unnamed Renewal',
            // Format the values for display
            formatted_old_value: formatAuditValue(audit.old_value, audit.audit_type),
            formatted_new_value: formatAuditValue(audit.new_value, audit.audit_type),
        }));
    } catch (error) {
        console.error('Error loading audits:', error);
        alert('Failed to load audit logs. Please try again.');
    } finally {
        loadingAudits.value = false;
    }
};

// Watch for modal opening
watch(() => props.isOpen, (newValue) => {
    if (newValue) {
        loadRenewalStatuses();
        loadRenewals();
        loadAudits();
        showAuditView.value = false;
    }
});

// Watch for audit view toggle
watch(showAuditView, (newValue) => {
    if (newValue && audits.value.length === 0) {
        loadAudits();
    }
});

// Load on mount if modal is open
onMounted(() => {
    if (props.isOpen) {
        loadRenewalStatuses();
        loadRenewals();
        loadAudits();
    }
});

const close = () => {
    emit('close');
};
</script>

