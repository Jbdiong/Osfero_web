<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0" style="background-color: rgba(0, 0, 0, 0.5);" @click="close"></div>
        
        <!-- Modal -->
        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-lg shadow-xl">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-gray-900">New Renewal</h2>
                </div>
                <button @click="close" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="p-6 space-y-6">
                <!-- Label -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Label <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.label"
                        type="text"
                        placeholder="Enter renewal label"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <!-- Start Date -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <div class="relative date-picker-container">
                            <input
                                v-model="form.start_date"
                                type="text"
                                placeholder="DD/MM/YYYY"
                                pattern="\d{2}/\d{2}/\d{4}"
                                required
                                @input="formatDateInput($event, 'start_date')"
                                @change="calculateEndDate"
                                @focus="showStartDatePicker = true"
                                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button
                                type="button"
                                @click.stop="showStartDatePicker = !showStartDatePicker"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <!-- Date Picker Popup -->
                            <div v-if="showStartDatePicker" @click.stop class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4" style="width: 280px; left: 0;">
                                <div class="flex items-center justify-between mb-4">
                                    <button @click="prevMonth('start')" class="p-1 hover:bg-gray-100 rounded">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                    </button>
                                    <div class="font-semibold">{{ startDatePickerMonthYear }}</div>
                                    <button @click="nextMonth('start')" class="p-1 hover:bg-gray-100 rounded">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <div v-for="day in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="day" class="text-center text-xs font-medium text-gray-500 py-1">{{ day }}</div>
                                </div>
                                <div class="grid grid-cols-7 gap-1">
                                    <div v-for="day in startDatePickerDays" :key="day.date" 
                                        @click="selectStartDate(day.date)"
                                        :class="[
                                            'text-center py-2 rounded cursor-pointer text-sm',
                                            day.isCurrentMonth ? 'hover:bg-blue-100' : 'text-gray-300',
                                            day.isSelected ? 'bg-blue-600 text-white' : '',
                                            day.isToday ? 'font-bold' : ''
                                        ]">
                                        {{ day.day }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Format: DD/MM/YYYY</p>
                    </div>

                    <!-- End Date (Renew Date) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Renew Date (End Date) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative date-picker-container">
                            <input
                                v-model="form.Renew_Date"
                                type="text"
                                placeholder="DD/MM/YYYY"
                                pattern="\d{2}/\d{2}/\d{4}"
                                required
                                @input="formatDateInput($event, 'Renew_Date')"
                                @change="form.duration = ''"
                                @focus="showEndDatePicker = true"
                                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button
                                type="button"
                                @click.stop="showEndDatePicker = !showEndDatePicker"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <!-- Date Picker Popup -->
                            <div v-if="showEndDatePicker" @click.stop class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4" style="width: 280px; left: 0;">
                                <div class="flex items-center justify-between mb-4">
                                    <button @click="prevMonth('end')" class="p-1 hover:bg-gray-100 rounded">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                    </button>
                                    <div class="font-semibold">{{ endDatePickerMonthYear }}</div>
                                    <button @click="nextMonth('end')" class="p-1 hover:bg-gray-100 rounded">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-7 gap-1 mb-2">
                                    <div v-for="day in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="day" class="text-center text-xs font-medium text-gray-500 py-1">{{ day }}</div>
                                </div>
                                <div class="grid grid-cols-7 gap-1">
                                    <div v-for="day in endDatePickerDays" :key="day.date" 
                                        @click="selectEndDate(day.date)"
                                        :class="[
                                            'text-center py-2 rounded cursor-pointer text-sm',
                                            day.isCurrentMonth ? 'hover:bg-blue-100' : 'text-gray-300',
                                            day.isSelected ? 'bg-blue-600 text-white' : '',
                                            day.isToday ? 'font-bold' : ''
                                        ]">
                                        {{ day.day }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Format: DD/MM/YYYY</p>
                    </div>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Duration
                    </label>
                    <select
                        v-model="form.duration"
                        @change="calculateEndDate"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                    >
                        <option value="">Select duration (optional)</option>
                        <option value="1">1 Month</option>
                        <option value="2">2 Months</option>
                        <option value="3">3 Months</option>
                        <option value="6">6 Months</option>
                        <option value="12">1 Year</option>
                    </select>
                </div>

                


                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        @click="close"
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md font-medium hover:bg-gray-200 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isSubmitting ? 'Adding...' : 'Add Renewal' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    leadId: {
        type: [Number, String],
        default: null
    }
});

const emit = defineEmits(['close', 'created']);

const isSubmitting = ref(false);
const showStartDatePicker = ref(false);
const showEndDatePicker = ref(false);
const startDatePickerDate = ref(new Date());
const endDatePickerDate = ref(new Date());

const form = reactive({
    label: '',
    start_date: '',
    duration: '',
    Renew_Date: '',
});

// Format date input to DD/MM/YYYY
const formatDateInput = (event, field) => {
    let value = event.target.value.replace(/\D/g, ''); // Remove non-digits
    
    // Add slashes automatically
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2);
    }
    if (value.length >= 5) {
        value = value.substring(0, 5) + '/' + value.substring(5, 9);
    }
    
    form[field] = value;
};

// Convert DD/MM/YYYY to YYYY-MM-DD for calculations
const parseDate = (dateString) => {
    if (!dateString) return null;
    const parts = dateString.split('/');
    if (parts.length === 3) {
        const day = parts[0];
        const month = parts[1];
        const year = parts[2];
        return new Date(`${year}-${month}-${day}`);
    }
    return null;
};

// Convert YYYY-MM-DD to DD/MM/YYYY for display
const formatDateForDisplay = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

// Calculate end date based on start date and duration
const calculateEndDate = () => {
    if (!form.start_date) {
        return;
    }

    const startDate = parseDate(form.start_date);
    if (!startDate || isNaN(startDate.getTime())) {
        return;
    }

    if (form.duration) {
        const months = parseInt(form.duration);
        
        // Add months to start date
        startDate.setMonth(startDate.getMonth() + months);
        
        // Format as DD/MM/YYYY
        const day = String(startDate.getDate()).padStart(2, '0');
        const month = String(startDate.getMonth() + 1).padStart(2, '0');
        const year = startDate.getFullYear();
        
        form.Renew_Date = `${day}/${month}/${year}`;
    } else if (!form.Renew_Date) {
        // If no duration selected and no end date, set end date same as start date
        form.Renew_Date = form.start_date;
    }
};

// Date picker computed properties
const startDatePickerMonthYear = computed(() => {
    return startDatePickerDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const endDatePickerMonthYear = computed(() => {
    return endDatePickerDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

const startDatePickerDays = computed(() => {
    return generateCalendarDays(startDatePickerDate.value, form.start_date);
});

const endDatePickerDays = computed(() => {
    return generateCalendarDays(endDatePickerDate.value, form.Renew_Date);
});

// Generate calendar days for a given month
const generateCalendarDays = (date, selectedDate) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Parse selected date
    let selectedDateObj = null;
    if (selectedDate) {
        const parts = selectedDate.split('/');
        if (parts.length === 3) {
            selectedDateObj = new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
            selectedDateObj.setHours(0, 0, 0, 0);
        }
    }
    
    const days = [];
    
    // Add empty cells for days before the first day of the month
    for (let i = 0; i < startingDayOfWeek; i++) {
        const prevMonthDate = new Date(year, month, -i);
        days.push({
            day: prevMonthDate.getDate(),
            date: prevMonthDate,
            isCurrentMonth: false,
            isSelected: false,
            isToday: false,
        });
    }
    
    // Add days of the current month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        currentDate.setHours(0, 0, 0, 0);
        const isSelected = selectedDateObj && currentDate.getTime() === selectedDateObj.getTime();
        const isToday = currentDate.getTime() === today.getTime();
        
        days.push({
            day: day,
            date: currentDate,
            isCurrentMonth: true,
            isSelected: isSelected,
            isToday: isToday,
        });
    }
    
    // Fill remaining cells to complete the grid (42 cells total for 6 rows)
    const remainingCells = 42 - days.length;
    for (let day = 1; day <= remainingCells; day++) {
        const nextMonthDate = new Date(year, month + 1, day);
        days.push({
            day: day,
            date: nextMonthDate,
            isCurrentMonth: false,
            isSelected: false,
            isToday: false,
        });
    }
    
    return days;
};

// Navigate months
const prevMonth = (type) => {
    if (type === 'start') {
        startDatePickerDate.value = new Date(startDatePickerDate.value.getFullYear(), startDatePickerDate.value.getMonth() - 1, 1);
    } else {
        endDatePickerDate.value = new Date(endDatePickerDate.value.getFullYear(), endDatePickerDate.value.getMonth() - 1, 1);
    }
};

const nextMonth = (type) => {
    if (type === 'start') {
        startDatePickerDate.value = new Date(startDatePickerDate.value.getFullYear(), startDatePickerDate.value.getMonth() + 1, 1);
    } else {
        endDatePickerDate.value = new Date(endDatePickerDate.value.getFullYear(), endDatePickerDate.value.getMonth() + 1, 1);
    }
};

// Select date from calendar
const selectStartDate = (date) => {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    form.start_date = `${day}/${month}/${year}`;
    showStartDatePicker.value = false;
    calculateEndDate();
};

const selectEndDate = (date) => {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    form.Renew_Date = `${day}/${month}/${year}`;
    showEndDatePicker.value = false;
    form.duration = '';
};

// Watch for modal opening to load data
watch(() => props.isOpen, (newValue) => {
    if (newValue) {
        // Set today's date as default for start date in DD/MM/YYYY format
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        const todayFormatted = `${day}/${month}/${year}`;
        form.start_date = todayFormatted;
        form.Renew_Date = todayFormatted;
        
        // Reset date pickers
        startDatePickerDate.value = new Date();
        endDatePickerDate.value = new Date();
        showStartDatePicker.value = false;
        showEndDatePicker.value = false;
    }
});

// Close date pickers when clicking outside
const handleClickOutside = (event) => {
    if (!event.target.closest('.date-picker-container')) {
        showStartDatePicker.value = false;
        showEndDatePicker.value = false;
    }
};

// Update date picker month when text input changes
watch(() => form.start_date, (newValue) => {
    if (newValue) {
        const parts = newValue.split('/');
        if (parts.length === 3) {
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const year = parseInt(parts[2], 10);
            if (!isNaN(day) && !isNaN(month) && !isNaN(year)) {
                startDatePickerDate.value = new Date(year, month, 1);
            }
        }
    }
});

watch(() => form.Renew_Date, (newValue) => {
    if (newValue) {
        const parts = newValue.split('/');
        if (parts.length === 3) {
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const year = parseInt(parts[2], 10);
            if (!isNaN(day) && !isNaN(month) && !isNaN(year)) {
                endDatePickerDate.value = new Date(year, month, 1);
            }
        }
    }
});

// Load on mount
onMounted(() => {
    if (props.isOpen) {
        // Set today's date as default for start date in DD/MM/YYYY format
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        const todayFormatted = `${day}/${month}/${year}`;
        form.start_date = todayFormatted;
        form.Renew_Date = todayFormatted;
    }
    
    // Add click outside listener
    document.addEventListener('click', handleClickOutside);
});

// Cleanup on unmount
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const close = () => {
    emit('close');
    resetForm();
};

const resetForm = () => {
    form.label = '';
    form.start_date = '';
    form.duration = '';
    form.Renew_Date = '';
};

// Validate date format DD/MM/YYYY
const validateDate = (dateString) => {
    if (!dateString) return false;
    const parts = dateString.split('/');
    if (parts.length !== 3) return false;
    
    const day = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const year = parseInt(parts[2], 10);
    
    if (isNaN(day) || isNaN(month) || isNaN(year)) return false;
    if (day < 1 || day > 31) return false;
    if (month < 1 || month > 12) return false;
    if (year < 1900 || year > 2100) return false;
    
    const date = new Date(year, month - 1, day);
    return date.getDate() === day && date.getMonth() === month - 1 && date.getFullYear() === year;
};

// Convert DD/MM/YYYY to YYYY-MM-DD for API
const convertToApiFormat = (dateString) => {
    if (!dateString) return null;
    const parts = dateString.split('/');
    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }
    return null;
};

const submitForm = async () => {
    if (!form.label || form.label.trim() === '') {
        alert('Label is required');
        return;
    }

    if (!form.start_date) {
        alert('Start Date is required');
        return;
    }

    if (!validateDate(form.start_date)) {
        alert('Please enter a valid start date in DD/MM/YYYY format');
        return;
    }

    if (!form.Renew_Date) {
        alert('Renew Date (End Date) is required');
        return;
    }

    if (!validateDate(form.Renew_Date)) {
        alert('Please enter a valid renew date in DD/MM/YYYY format');
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            label: form.label.trim(),
            start_date: convertToApiFormat(form.start_date),
            Renew_Date: convertToApiFormat(form.Renew_Date), // End date is stored as Renew_Date
            lead_id: props.leadId || null,
        };

        const response = await axios.post('/api/v1/renewals', payload);
        
        emit('created', response.data.data);
        close();
    } catch (error) {
        console.error('Error creating renewal:', error);
        alert(error.response?.data?.message || 'Failed to create renewal. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

