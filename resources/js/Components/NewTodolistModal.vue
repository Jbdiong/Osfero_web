<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0" style="background-color: rgba(0, 0, 0, 0.5);" @click="close"></div>
        
        <!-- Modal -->
        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-lg shadow-xl max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ editingTodolist ? 'Edit Todolist' : 'New Todolist' }}
                </h2>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto px-6 py-4">
                <div class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.Title"
                            type="text"
                            required
                            placeholder="Enter todolist title"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            v-model="form.Description"
                            rows="3"
                            placeholder="Enter description"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                    </div>

                    <!-- Lead Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Lead (Optional)
                        </label>
                        <select
                            v-model="form.lead_id"
                            @change="onLeadChange"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option :value="null">Select a lead (optional)</option>
                            <option v-for="lead in leads" :key="lead.id" :value="lead.id">
                                {{ lead.Shop_Name || lead.shop_name || 'Unnamed Lead' }}
                            </option>
                        </select>
                    </div>

                    <!-- Payment Selection (only if lead is selected) -->
                    <div v-if="form.lead_id">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Payment (Optional)
                        </label>
                        <select
                            v-model="form.payment_id"
                            :disabled="loadingPayments"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white disabled:opacity-50"
                        >
                            <option :value="null">Select a payment (optional)</option>
                            <option v-for="payment in payments" :key="payment.id" :value="payment.id">
                                Payment #{{ payment.id }} - {{ formatCurrency(payment.Amount) }}
                            </option>
                        </select>
                        <p v-if="loadingPayments" class="text-sm text-gray-500 mt-1">Loading payments...</p>
                        <p v-else-if="form.lead_id && payments.length === 0" class="text-sm text-gray-500 mt-1">No payments found for this lead</p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                End Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                required
                                :min="form.start_date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.priority_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option :value="null">Select priority</option>
                            <option v-for="priority in priorities" :key="priority.id" :value="priority.id">
                                {{ priority.name || priority.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.status_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                        >
                            <option :value="null">Select status</option>
                            <option v-for="status in statuses" :key="status.id" :value="status.id">
                                {{ status.name || status.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Parent Todolist (for subtasks) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Parent Todolist (Optional - for creating subtasks)
                        </label>
                        <select
                            v-model="form.parent_id"
                            @change="onParentChange"
                            :disabled="loadingParentTodolists"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white disabled:opacity-50"
                        >
                            <option :value="null">None (Main Task)</option>
                            <option v-for="parent in availableParentTodolists" :key="parent.id" :value="parent.id">
                                {{ parent.Title }}
                                <span v-if="parent.lead" class="text-gray-500">
                                    - {{ parent.lead.Shop_Name || parent.lead.shop_name || 'Unnamed Lead' }}
                                </span>
                            </option>
                        </select>
                        <p v-if="loadingParentTodolists" class="text-sm text-gray-500 mt-1">Loading parent todolists...</p>
                        <p v-else-if="form.parent_id" class="text-xs text-gray-500 mt-1">
                            This will be created as a subtask. Status will be inherited from parent: 
                            <span class="font-medium">{{ selectedParentStatus || 'Same as parent' }}</span>
                        </p>
                    </div>

                    <!-- Person in Charge (PICs) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Person in Charge (PICs) <span class="text-red-500">*</span>
                        </label>
                        <div class="border border-gray-300 rounded-md p-3 max-h-48 overflow-y-auto bg-white">
                            <div v-if="users.length === 0" class="text-sm text-gray-500 py-2">
                                No users available
                            </div>
                            <div v-else class="space-y-2">
                                <label
                                    v-for="user in users"
                                    :key="user.id"
                                    class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                                >
                                    <input
                                        type="checkbox"
                                        :value="user.id"
                                        v-model="form.pic_user_ids"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700">
                                        {{ user.name }} <span class="text-gray-500">({{ user.email }})</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Select at least one user</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        @click="close"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isSubmitting">Saving...</span>
                        <span v-else>{{ editingTodolist ? 'Update' : 'Create' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    todolist: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);

const isSubmitting = ref(false);
const loadingPayments = ref(false);
const loadingParentTodolists = ref(false);
const leads = ref([]);
const payments = ref([]);
const priorities = ref([]);
const statuses = ref([]);
const users = ref([]);
const availableParentTodolists = ref([]);
const selectedParentStatus = ref('');

const editingTodolist = ref(false);

const form = reactive({
    Title: '',
    Description: '',
    lead_id: null,
    payment_id: null,
    start_date: '',
    end_date: '',
    priority_id: null,
    status_id: null,
    parent_id: null,
    pic_user_ids: []
});

const resetForm = () => {
    form.Title = '';
    form.Description = '';
    form.lead_id = null;
    form.payment_id = null;
    form.start_date = '';
    form.end_date = '';
    form.priority_id = null;
    form.status_id = null;
    form.parent_id = null;
    form.pic_user_ids = [];
    payments.value = [];
};

const loadParentTodolists = async (excludeId = null) => {
    loadingParentTodolists.value = true;
    try {
        const response = await axios.get('/api/v1/todolists');
        let allTodolists = response.data.data || [];
        
        // Filter out the current todolist and its children to prevent circular references
        if (excludeId) {
            // Try to get children from the todolist prop first, then from the API response
            let childrenIds = [];
            if (props.todolist?.children) {
                childrenIds = props.todolist.children.map(c => c.id) || [];
            } else {
                // Fallback: get from API response
                const currentTodolist = allTodolists.find(t => t.id === excludeId);
                childrenIds = currentTodolist?.children?.map(c => c.id) || [];
            }
            
            // Exclude current todolist and all its children
            allTodolists = allTodolists.filter(t => 
                t.id !== excludeId && !childrenIds.includes(t.id)
            );
        }
        
        availableParentTodolists.value = allTodolists;
    } catch (error) {
        console.error('Error loading parent todolists:', error);
        availableParentTodolists.value = [];
    } finally {
        loadingParentTodolists.value = false;
    }
};

// Watch for todolist prop changes
watch(() => props.todolist, (newTodolist) => {
    if (newTodolist) {
        editingTodolist.value = true;
        form.Title = newTodolist.Title || '';
        form.Description = newTodolist.Description || '';
        form.lead_id = newTodolist.lead_id || null;
        form.payment_id = newTodolist.payment_id || null;
        form.start_date = newTodolist.start_date ? newTodolist.start_date.split('T')[0] : '';
        form.end_date = newTodolist.end_date ? newTodolist.end_date.split('T')[0] : '';
        form.priority_id = newTodolist.priority_id || null;
        form.status_id = newTodolist.status_id || null;
        form.parent_id = newTodolist.parent_id || null;
        form.pic_user_ids = (newTodolist.todolist_pics || []).map(pic => pic.user_id || pic.user?.id) || [];
        
        // Load payments if lead is selected
        if (form.lead_id) {
            loadPayments(form.lead_id);
        }
        
        // Reload parent todolists to exclude current one and its children
        loadParentTodolists(newTodolist.id);
    } else {
        editingTodolist.value = false;
        resetForm();
        // Reload parent todolists when creating new
        loadParentTodolists(null);
    }
}, { immediate: true });

// Watch for modal open/close
watch(() => props.isOpen, (isOpen) => {
    if (!isOpen) {
        resetForm();
        editingTodolist.value = false;
    } else {
        // Reload parent todolists when modal opens (in case new ones were created)
        const excludeId = props.todolist?.id || null;
        loadParentTodolists(excludeId);
    }
});

onMounted(async () => {
    await Promise.all([
        loadLeads(),
        loadPriorities(),
        loadStatuses(),
        loadUsers(),
        loadParentTodolists(null)
    ]);
});

const loadLeads = async () => {
    try {
        const response = await axios.get('/api/v1/leads?per_page=100');
        leads.value = response.data.data;
    } catch (error) {
        console.error('Error loading leads:', error);
    }
};

const loadPayments = async (leadId) => {
    if (!leadId) {
        payments.value = [];
        return;
    }
    
    loadingPayments.value = true;
    try {
        const response = await axios.get(`/api/v1/leads/${leadId}/payments`);
        payments.value = response.data.data;
    } catch (error) {
        console.error('Error loading payments:', error);
        payments.value = [];
    } finally {
        loadingPayments.value = false;
    }
};

const loadPriorities = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Priority'));
        priorities.value = response.data.data;
    } catch (error) {
        console.error('Error loading priorities:', error);
    }
};

const loadStatuses = async () => {
    try {
        const response = await axios.get('/api/v1/lookups/' + encodeURIComponent('Todolist Status'));
        statuses.value = response.data.data;
    } catch (error) {
        console.error('Error loading statuses:', error);
    }
};

const loadUsers = async () => {
    try {
        const response = await axios.get('/api/v1/marketers');
        users.value = response.data.data;
    } catch (error) {
        console.error('Error loading users:', error);
    }
};

const onLeadChange = () => {
    form.payment_id = null; // Reset payment when lead changes
    if (form.lead_id) {
        loadPayments(form.lead_id);
    } else {
        payments.value = [];
    }
};

const onParentChange = () => {
    if (form.parent_id) {
        // Find the selected parent todolist
        const parent = availableParentTodolists.value.find(p => p.id === form.parent_id);
        if (parent) {
            // Inherit status from parent
            if (parent.status_id) {
                form.status_id = parent.status_id;
                selectedParentStatus.value = parent.status?.name || parent.status?.label || 'Same as parent';
            }
            // Inherit other fields if not already set
            if (!form.priority_id && parent.priority_id) {
                form.priority_id = parent.priority_id;
            }
            if (!form.start_date && parent.start_date) {
                form.start_date = parent.start_date.split('T')[0];
            }
            if (!form.end_date && parent.end_date) {
                form.end_date = parent.end_date.split('T')[0];
            }
            if (!form.lead_id && parent.lead_id) {
                form.lead_id = parent.lead_id;
                loadPayments(parent.lead_id);
            }
        }
    } else {
        selectedParentStatus.value = '';
    }
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const close = () => {
    emit('close');
    resetForm();
};

const submitForm = async () => {
    if (!form.Title.trim()) {
        alert('Title is required');
        return;
    }

    if (!form.start_date) {
        alert('Start date is required');
        return;
    }

    if (!form.end_date) {
        alert('End date is required');
        return;
    }

    if (!form.priority_id) {
        alert('Priority is required');
        return;
    }

    if (!form.status_id) {
        alert('Status is required');
        return;
    }

    if (!form.pic_user_ids || form.pic_user_ids.length === 0) {
        alert('At least one Person in Charge (PIC) is required');
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            ...form,
            lead_id: form.lead_id || null,
            payment_id: form.payment_id || null,
            priority_id: form.priority_id || null,
            status_id: form.status_id || null,
            parent_id: form.parent_id || null,
            pic_user_ids: form.pic_user_ids || []
        };

        let response;
        if (editingTodolist.value && props.todolist) {
            response = await axios.put(`/api/v1/todolists/${props.todolist.id}`, payload);
        } else {
            response = await axios.post('/api/v1/todolists', payload);
        }

        emit('saved', response.data.data);
        close();
    } catch (error) {
        console.error('Error saving todolist:', error);
        alert(error.response?.data?.message || 'Failed to save todolist. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

