<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0" style="background-color: rgba(0, 0, 0, 0.5);" @click="close"></div>
        
        <!-- Modal -->
        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-3xl bg-white rounded-lg shadow-xl max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ isEditMode ? 'Edit Todolist' : 'Todolist Overview' }}
                    </h2>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="!isEditMode"
                        @click="enterEditMode"
                        class="px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100"
                    >
                        Edit
                    </button>
                    <button
                        v-if="isEditMode"
                        @click="cancelEdit"
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button @click="close" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div v-if="loading" class="flex-1 overflow-y-auto px-6 py-8">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-500">Loading todolist...</p>
                </div>
            </div>

            <!-- Edit Mode Form -->
            <form v-else-if="isEditMode && todolist" @submit.prevent="submitForm" class="flex-1 overflow-y-auto px-6 py-4">
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
                        @click="cancelEdit"
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
                        <span v-else>Update</span>
                    </button>
                </div>
            </form>

            <!-- View Mode -->
            <div v-else-if="todolist" class="flex-1 overflow-y-auto px-6 py-4">
                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ todolist.Title }}</h3>
                        <p v-if="todolist.Description" class="text-sm text-gray-600 mt-2">{{ todolist.Description }}</p>
                    </div>

                    <!-- Status and Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Status</label>
                            <div class="flex items-center gap-2">
                                <span v-if="todolist.status" class="px-3 py-1 text-sm font-medium rounded-md"
                                    :class="getStatusColorClass(todolist.status.name)">
                                    {{ todolist.status.name || todolist.status.label }}
                                </span>
                                <span v-else class="text-sm text-gray-400">Not set</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Priority</label>
                            <div class="flex items-center gap-2">
                                <span v-if="todolist.priority" class="px-3 py-1 text-sm font-medium rounded-md"
                                    :class="getPriorityColorClass(todolist.priority.name)">
                                    {{ todolist.priority.name || todolist.priority.label }}
                                </span>
                                <span v-else class="text-sm text-gray-400">Not set</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Start Date</label>
                            <p class="text-sm text-gray-900">{{ formatDate(todolist.start_date) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-1">End Date</label>
                            <p class="text-sm text-gray-900">{{ formatDate(todolist.end_date) }}</p>
                        </div>
                    </div>

                    <!-- Lead Information -->
                    <div v-if="todolist.lead">
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Lead</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-900">{{ todolist.lead.Shop_Name || todolist.lead.shop_name || 'Unnamed Lead' }}</p>
                            <p v-if="todolist.lead.phone_number" class="text-xs text-gray-600 mt-1">{{ todolist.lead.phone_number }}</p>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div v-if="todolist.payment">
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Payment</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Payment #{{ todolist.payment.id }}</p>
                                    <p v-if="todolist.payment.Amount" class="text-sm text-gray-600 mt-1">
                                        Amount: {{ formatCurrency(todolist.payment.Amount) }}
                                    </p>
                                </div>
                                <span v-if="todolist.payment.status" class="px-2 py-1 text-xs font-medium rounded-md bg-blue-100 text-blue-800">
                                    {{ todolist.payment.status.name || todolist.payment.status.label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Person in Charge (PICs) -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Person in Charge</label>
                        <div v-if="picsList && picsList.length > 0" class="space-y-2">
                            <div
                                v-for="pic in picsList"
                                :key="pic.id || pic.todolist_id + '-' + pic.user_id"
                                class="bg-gray-50 rounded-lg p-3"
                            >
                                <p class="text-sm font-medium text-gray-900">
                                    {{ getPicName(pic) }}
                                </p>
                                <p v-if="getPicEmail(pic)" class="text-xs text-gray-600 mt-1">
                                    {{ getPicEmail(pic) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400">No PICs assigned</p>
                    </div>

                    <!-- Parent Todolist -->
                    <div v-if="todolist.parent">
                        <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Parent Todolist</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-900">{{ todolist.parent.Title }}</p>
                        </div>
                    </div>

                    <!-- Child Todolists -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Sub-tasks</label>
                            <button
                                v-if="!showSubtaskForm"
                                @click="showSubtaskForm = true"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                            >
                                + Add Subtask
                            </button>
                        </div>
                        
                        <!-- Inline Subtask Creation Form -->
                        <div v-if="showSubtaskForm" class="mb-3 bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <div class="flex items-start gap-2">
                                <div class="flex-1">
                                    <input
                                        v-model="newSubtaskTitle"
                                        @keyup.enter="createSubtask"
                                        @keyup.esc="cancelSubtaskForm"
                                        type="text"
                                        placeholder="Task Name or type '/' for commands"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        ref="subtaskInput"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">
                                        Subtask will inherit status: <span class="font-medium">{{ todolist.status?.name || todolist.status?.label || 'Same as parent' }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="createSubtask"
                                        :disabled="!newSubtaskTitle.trim() || isCreatingSubtask"
                                        class="px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                    >
                                        <span v-if="isCreatingSubtask">Saving...</span>
                                        <span v-else>Save</span>
                                        <svg v-if="!isCreatingSubtask" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click="cancelSubtaskForm"
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Subtasks -->
                        <div v-if="todolist.children && todolist.children.length > 0" class="space-y-2">
                            <div
                                v-for="child in todolist.children"
                                :key="child.id"
                                class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 cursor-pointer transition-colors"
                                @click="viewChildTodolist(child.id)"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ child.Title }}</p>
                                        <p v-if="child.status" class="text-xs text-gray-600 mt-1">
                                            Status: {{ child.status.name || child.status.label }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="!showSubtaskForm" class="text-sm text-gray-400">No subtasks yet. Click "+ Add Subtask" to create one.</p>
                    </div>

                    <!-- Timestamps -->
                    <div class="pt-4 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-4 text-xs text-gray-500">
                            <div>
                                <span class="font-medium">Created:</span>
                                <span class="ml-2">{{ formatDateTime(todolist.created_at) }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Updated:</span>
                                <span class="ml-2">{{ formatDateTime(todolist.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="flex-1 overflow-y-auto px-6 py-8">
                <div class="text-center">
                    <p class="text-gray-500">Todolist not found</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed, nextTick, reactive, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    todolistId: {
        type: Number,
        default: null
    }
});

const emit = defineEmits(['close', 'saved', 'subtask-created', 'view-child']);

const loading = ref(false);
const todolist = ref(null);
const isEditMode = ref(false);
const isSubmitting = ref(false);
const loadingPayments = ref(false);
const loadingParentTodolists = ref(false);
const showSubtaskForm = ref(false);
const newSubtaskTitle = ref('');
const isCreatingSubtask = ref(false);
const subtaskInput = ref(null);

// Form data
const leads = ref([]);
const payments = ref([]);
const priorities = ref([]);
const statuses = ref([]);
const users = ref([]);
const availableParentTodolists = ref([]);
const selectedParentStatus = ref('');

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

// Computed property to get PICs from different possible property names
const picsList = computed(() => {
    if (!todolist.value) return [];
    
    const pics = todolist.value.todolist_pics || 
                 todolist.value.todolistPICs || 
                 todolist.value.todolist_p_i_cs ||
                 [];
    
    return Array.isArray(pics) ? pics : [];
});

// Helper functions to get PIC name and email
const getPicName = (pic) => {
    if (pic.user?.name) return pic.user.name;
    if (pic.user_name) return pic.user_name;
    if (pic.user?.email) return pic.user.email.split('@')[0];
    return 'Unknown User';
};

const getPicEmail = (pic) => {
    if (pic.user?.email) return pic.user.email;
    if (pic.user_email) return pic.user_email;
    return null;
};

// Load data functions
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

const loadParentTodolists = async (excludeId = null) => {
    loadingParentTodolists.value = true;
    try {
        const response = await axios.get('/api/v1/todolists');
        let allTodolists = response.data.data || [];
        
        if (excludeId) {
            let childrenIds = [];
            if (todolist.value?.children) {
                childrenIds = todolist.value.children.map(c => c.id) || [];
            } else {
                const currentTodolist = allTodolists.find(t => t.id === excludeId);
                childrenIds = currentTodolist?.children?.map(c => c.id) || [];
            }
            
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

// Load todolist
const loadTodolist = async () => {
    if (!props.todolistId) return;

    loading.value = true;
    try {
        const response = await axios.get(`/api/v1/todolists/${props.todolistId}`);
        todolist.value = response.data.data;
    } catch (error) {
        console.error('Error loading todolist:', error);
        todolist.value = null;
    } finally {
        loading.value = false;
    }
};

// Populate form from todolist
const populateForm = () => {
    if (!todolist.value) return;
    
    form.Title = todolist.value.Title || '';
    form.Description = todolist.value.Description || '';
    form.lead_id = todolist.value.lead_id || null;
    form.payment_id = todolist.value.payment_id || null;
    form.start_date = todolist.value.start_date ? todolist.value.start_date.split('T')[0] : '';
    form.end_date = todolist.value.end_date ? todolist.value.end_date.split('T')[0] : '';
    form.priority_id = todolist.value.priority_id || null;
    form.status_id = todolist.value.status_id || null;
    form.parent_id = todolist.value.parent_id || null;
    form.pic_user_ids = picsList.value.map(pic => pic.user_id || pic.user?.id).filter(id => id);
    
    if (form.lead_id) {
        loadPayments(form.lead_id);
    }
    
    if (todolist.value.id) {
        loadParentTodolists(todolist.value.id);
    }
};

// Enter edit mode
const enterEditMode = () => {
    isEditMode.value = true;
    populateForm();
};

// Cancel edit
const cancelEdit = () => {
    isEditMode.value = false;
    populateForm(); // Reset form to original values
};

// Form handlers
const onLeadChange = () => {
    form.payment_id = null;
    if (form.lead_id) {
        loadPayments(form.lead_id);
    } else {
        payments.value = [];
    }
};

const onParentChange = () => {
    if (form.parent_id) {
        const parent = availableParentTodolists.value.find(p => p.id === form.parent_id);
        if (parent) {
            if (parent.status_id) {
                form.status_id = parent.status_id;
                selectedParentStatus.value = parent.status?.name || parent.status?.label || 'Same as parent';
            }
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

        const response = await axios.put(`/api/v1/todolists/${todolist.value.id}`, payload);
        
        // Reload todolist
        await loadTodolist();
        
        // Exit edit mode
        isEditMode.value = false;
        
        // Emit saved event
        emit('saved', response.data.data);
    } catch (error) {
        console.error('Error saving todolist:', error);
        alert(error.response?.data?.message || 'Failed to save todolist. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

// Subtask functions
const cancelSubtaskForm = () => {
    showSubtaskForm.value = false;
    newSubtaskTitle.value = '';
};

const createSubtask = async () => {
    if (!newSubtaskTitle.value.trim() || !todolist.value) {
        return;
    }

    isCreatingSubtask.value = true;
    try {
        const parentTodolist = todolist.value;
        
        const picUserIds = [];
        if (picsList.value && picsList.value.length > 0) {
            picsList.value.forEach(pic => {
                const userId = pic.user_id || pic.user?.id;
                if (userId) picUserIds.push(userId);
            });
        }
        
        if (picUserIds.length === 0 && parentTodolist.todolist_pics) {
            parentTodolist.todolist_pics.forEach(pic => {
                const userId = pic.user_id || pic.user?.id;
                if (userId) picUserIds.push(userId);
            });
        }
        if (picUserIds.length === 0 && parentTodolist.todolistPICs) {
            parentTodolist.todolistPICs.forEach(pic => {
                const userId = pic.user_id || pic.user?.id;
                if (userId) picUserIds.push(userId);
            });
        }
        
        if (picUserIds.length === 0) {
            alert('Parent task must have at least one Person in Charge (PIC) to create subtasks.');
            isCreatingSubtask.value = false;
            return;
        }

        const formatDateForAPI = (date) => {
            if (!date) return null;
            if (typeof date === 'string') {
                return date.split('T')[0];
            }
            const d = new Date(date);
            return d.toISOString().split('T')[0];
        };

        const payload = {
            Title: newSubtaskTitle.value.trim(),
            Description: '',
            parent_id: parentTodolist.id,
            status_id: parentTodolist.status_id,
            priority_id: parentTodolist.priority_id,
            start_date: formatDateForAPI(parentTodolist.start_date),
            end_date: formatDateForAPI(parentTodolist.end_date),
            lead_id: parentTodolist.lead_id,
            payment_id: parentTodolist.payment_id,
            pic_user_ids: picUserIds
        };

        const response = await axios.post('/api/v1/todolists', payload);
        
        newSubtaskTitle.value = '';
        showSubtaskForm.value = false;
        
        await loadTodolist();
        emit('subtask-created', response.data.data);
    } catch (error) {
        console.error('Error creating subtask:', error);
        alert(error.response?.data?.message || 'Failed to create subtask. Please try again.');
    } finally {
        isCreatingSubtask.value = false;
    }
};

const viewChildTodolist = (childId) => {
    emit('close');
    emit('view-child', childId);
};

// Formatting functions
const formatDate = (date) => {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatCurrency = (amount) => {
    if (!amount) return 'N/A';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};

const getStatusColorClass = (statusName) => {
    const status = (statusName || '').toLowerCase();
    if (status.includes('done') || status.includes('completed')) {
        return 'bg-green-100 text-green-800';
    } else if (status.includes('progress')) {
        return 'bg-orange-100 text-orange-800';
    } else if (status.includes('pending')) {
        return 'bg-pink-100 text-pink-800';
    } else if (status.includes('todo')) {
        return 'bg-blue-100 text-blue-800';
    }
    return 'bg-gray-100 text-gray-800';
};

const getPriorityColorClass = (priorityName) => {
    const priority = (priorityName || '').toLowerCase();
    if (priority === 'urgent') {
        return 'bg-red-100 text-red-800';
    } else if (priority === 'high') {
        return 'bg-orange-100 text-orange-800';
    } else if (priority === 'normal') {
        return 'bg-blue-100 text-blue-800';
    } else if (priority === 'low') {
        return 'bg-gray-100 text-gray-800';
    }
    return 'bg-gray-100 text-gray-800';
};

const close = () => {
    isEditMode.value = false;
    emit('close');
};

// Watchers
watch(() => props.isOpen, async (isOpen) => {
    if (isOpen && props.todolistId) {
        await loadTodolist();
        isEditMode.value = false;
    } else {
        todolist.value = null;
        isEditMode.value = false;
    }
});

watch(() => props.todolistId, async (newId) => {
    if (props.isOpen && newId) {
        await loadTodolist();
        isEditMode.value = false;
    }
});

watch(showSubtaskForm, async (isShown) => {
    if (isShown) {
        await nextTick();
        if (subtaskInput.value) {
            subtaskInput.value.focus();
        }
    }
});

// Load initial data
onMounted(async () => {
    await Promise.all([
        loadLeads(),
        loadPriorities(),
        loadStatuses(),
        loadUsers()
    ]);
});
</script>
