<template>
    <AppLayout>
        <div class="p-8">
            <div class="grid grid-cols-6 gap-6 h-[92vh]">
                <!-- Left Sidebar - Calendar Widget and Lists -->
                <div class="col-span-1 flex flex-col gap-3 h-full">
                    <!-- Calendar Widget -->
                    <div class="bg-white rounded-lg border border-gray-200 p-4 flex-1 min-h-0 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900">{{ selectedMonth }}</h3>
                            <div class="flex gap-1">
                                <button @click="previousMonth" class="p-1 hover:bg-gray-100 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button @click="nextMonth" class="p-1 hover:bg-gray-100 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-xs text-center">
                            <div class="text-gray-500 py-1">SU</div>
                            <div class="text-gray-500 py-1">MO</div>
                            <div class="text-gray-500 py-1">TU</div>
                            <div class="text-gray-500 py-1">WE</div>
                            <div class="text-gray-500 py-1">TH</div>
                            <div class="text-gray-500 py-1">FR</div>
                            <div class="text-gray-500 py-1">SA</div>
                            <div 
                                v-for="(dayItem, index) in calendarDays" 
                                :key="index"
                                @click="selectDate(dayItem)"
                                :class="[
                                    'py-2 rounded cursor-pointer',
                                    !dayItem.isCurrentMonth ? 'text-gray-300' :
                                    dayItem.isToday ? 'bg-blue-600 text-white font-semibold' :
                                    dayItem.isSelected ? 'bg-blue-100 text-blue-700' :
                                    'text-gray-700 hover:bg-gray-100'
                                ]">
                                {{ dayItem.day }}
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Deadline -->
                    <UpcomingDeadline
                        :title="calendar.upcoming_deadline_title"
                        :more="calendar.upcoming_deadline_more"
                        :countdown="calendar.upcoming_deadline_countdown"
                        @view-task="handleViewTask"
                    />

                    <!-- Overdue Renewals -->
                    <OverdueRenewals
                        :renewals="calendar.overdue_renewals"
                        @new-renewal="showRenewalModal = true"
                        @view-all="showRenewalTableModal = true"
                    />
                </div>

                <!-- Main Calendar Grid -->
                <div class="col-span-5 h-full rounded-lg flex flex-col bg-white" style="position: sticky; top: 0;">
                    <!-- Calendar Toolbar -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200 flex-shrink-0">
                        <!-- New Task Button -->
                        <button 
                            @click="handleNewTask"
                            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>New task</span>
                        </button>

                        <!-- View Selector and Navigation -->
                        <div class="flex items-center gap-3">
                            <!-- View Toggle Group -->
                            <div class="flex items-center bg-white rounded-lg border border-gray-200 p-1">
                                <button
                                    @click="setView('month')"
                                    :class="[
                                        'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                        currentView === 'month' 
                                            ? 'bg-blue-600 text-white' 
                                            : 'text-gray-900 hover:bg-gray-50'
                                    ]"
                                >
                                    Month
                                </button>
                                <button
                                    @click="setView('week')"
                                    :class="[
                                        'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                        currentView === 'week' 
                                            ? 'bg-blue-600 text-white' 
                                            : 'text-gray-900 hover:bg-gray-50'
                                    ]"
                                >
                                    Week
                                </button>
                                <button
                                    @click="setView('day')"
                                    :class="[
                                        'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                        currentView === 'day' 
                                            ? 'bg-blue-600 text-white' 
                                            : 'text-gray-900 hover:bg-gray-50'
                                    ]"
                                >
                                    Day
                                </button>
                            </div>

                            <!-- Navigation Arrows -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="navigateCalendar('prev')"
                                    class="w-8 h-8 flex items-center justify-center bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <svg class="w-4 h-4 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <button
                                    @click="navigateCalendar('next')"
                                    class="w-8 h-8 flex items-center justify-center bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <svg class="w-4 h-4 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <FullCalendar
                            ref="fullCalendarRef"
                            :options="calendarOptions"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- New Renewal Modal -->
        <NewRenewalModal 
            :isOpen="showRenewalModal" 
            @close="showRenewalModal = false"
            @created="handleRenewalCreated"
        />

        <!-- Renewal Table Modal -->
        <RenewalTableModal 
            :isOpen="showRenewalTableModal" 
            @close="showRenewalTableModal = false"
            @updated="handleRenewalUpdated"
        />
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Pages/Layouts/AppLayout.vue';
import NewRenewalModal from '@/Components/NewRenewalModal.vue';
import RenewalTableModal from '@/Components/RenewalTableModal.vue';
import UpcomingDeadline from '@/Components/UpcomingDeadline.vue';
import OverdueRenewals from '@/Components/OverdueRenewals.vue';
import { computed, ref, watch, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    calendar: Object,
});

// FullCalendar ref
const fullCalendarRef = ref(null);

// Modal state
const showRenewalModal = ref(false);
const showRenewalTableModal = ref(false);

// Calendar view state
const currentView = ref('month');

// Current date state for small calendar widget
const currentDate = ref(new Date());
const selectedDate = ref(new Date());

// Computed properties for small calendar
const currentMonth = computed(() => currentDate.value.getMonth());
const currentYear = computed(() => currentDate.value.getFullYear());
const selectedMonth = computed(() => {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    return `${monthNames[currentMonth.value]} ${currentYear.value}`;
});

// Get today's date for comparison
const today = computed(() => {
    const now = new Date();
    return {
        year: now.getFullYear(),
        month: now.getMonth(),
        date: now.getDate()
    };
});

// Generate calendar days for small widget
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.
    
    const days = [];
    
    // Add empty cells for days before the month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
        days.push({ day: null, isToday: false, isCurrentMonth: false });
    }
    
    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = year === today.value.year && 
                       month === today.value.month && 
                       day === today.value.date;
        const dateObj = new Date(year, month, day);
        const isSelected = dateObj.toDateString() === selectedDate.value.toDateString();
        days.push({ 
            day, 
            isToday, 
            isCurrentMonth: true,
            isSelected,
            date: dateObj
        });
    }
    
    return days;
});

// Convert backend events to FullCalendar format
const fullCalendarEvents = computed(() => {
    if (!props.calendar?.events) return [];
    
    // Use selected date for events
    const eventDate = new Date(selectedDate.value);
    
    return props.calendar.events.map(event => {
        // Parse start and end times
        const [startHours, startMinutes] = event.start.split(':').map(Number);
        const [endHours, endMinutes] = event.end.split(':').map(Number);
        
        const startDateTime = new Date(eventDate);
        startDateTime.setHours(startHours, startMinutes, 0, 0);
        
        const endDateTime = new Date(eventDate);
        endDateTime.setHours(endHours, endMinutes, 0, 0);
        
        // Map color to FullCalendar color
        const colorMap = {
            'purple': '#9333ea',
            'blue': '#3b82f6',
            'pink': '#ec4899',
            'green': '#10b981'
        };
        
        return {
            id: event.id.toString(),
            title: event.title,
            start: startDateTime.toISOString(),
            end: endDateTime.toISOString(),
            backgroundColor: colorMap[event.color] || colorMap['green'],
            borderColor: colorMap[event.color] || colorMap['green'],
            extendedProps: {
                assignee: event.assignee,
                assignees: event.assignees,
                assignee_color: event.assignee_color,
                assignee_colors: event.assignee_colors
            }
        };
    });
});

// FullCalendar options
const calendarOptions = computed(() => {
    const today = new Date();
    const selectedDateStr = selectedDate.value.toISOString().split('T')[0];
    
    // Map view names to FullCalendar view types
    const viewMap = {
        'month': 'dayGridMonth',
        'week': 'timeGridWeek',
        'day': 'timeGridDay'
    };
    
    return {
        plugins: [dayGridPlugin, timeGridPlugin],
        initialView: viewMap[currentView.value] || 'timeGridDay',
        initialDate: selectedDateStr,
        headerToolbar: false,
        height: '100%',
        scrollTime: '08:00:00',
        scrollTimeReset: false,

        allDaySlot: false,
        slotDuration: '01:00:00',
        events: fullCalendarEvents.value,
        eventContent: (arg) => {
            const event = arg.event;
            const extendedProps = event.extendedProps;
            
            // Create a container div
            const container = document.createElement('div');
            container.className = 'p-2';
            
            // Title
            const title = document.createElement('div');
            title.className = 'font-medium text-gray-900 text-xs';
            title.textContent = event.title;
            container.appendChild(title);
            
            // Assignees container
            const assigneesContainer = document.createElement('div');
            assigneesContainer.className = 'flex items-center gap-1 mt-1';
            
            if (extendedProps.assignees && extendedProps.assignees.length > 0) {
                extendedProps.assignees.forEach((assignee, idx) => {
                    const color = extendedProps.assignee_colors?.[idx] || 'blue';
                    const colorMap = {
                        'blue': 'bg-blue-500',
                        'green': 'bg-green-500',
                        'purple': 'bg-purple-500',
                        'orange': 'bg-orange-500'
                    };
                    const badge = document.createElement('div');
                    badge.className = `w-5 h-5 rounded-full flex items-center justify-center text-xs text-white ${colorMap[color] || 'bg-blue-500'}`;
                    badge.textContent = assignee;
                    assigneesContainer.appendChild(badge);
                });
            } else if (extendedProps.assignee) {
                const color = extendedProps.assignee_color || 'blue';
                const colorMap = {
                    'blue': 'bg-blue-500',
                    'green': 'bg-green-500',
                    'purple': 'bg-purple-500',
                    'orange': 'bg-orange-500'
                };
                const badge = document.createElement('div');
                badge.className = `w-5 h-5 rounded-full flex items-center justify-center text-xs text-white ${colorMap[color] || 'bg-blue-500'}`;
                badge.textContent = extendedProps.assignee;
                assigneesContainer.appendChild(badge);
            }
            
            container.appendChild(assigneesContainer);
            
            return { domNodes: [container] };
        },
        nowIndicator: true,
        now: new Date().toISOString(),
    };
});

// Watch for selectedDate changes and update FullCalendar
watch(selectedDate, (newDate) => {
    if (fullCalendarRef.value?.getApi) {
        const calendarApi = fullCalendarRef.value.getApi();
        calendarApi.gotoDate(newDate);
    }
}, { immediate: false });

// Navigation functions for small calendar
function previousMonth() {
    currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}

function nextMonth() {
    currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

function selectDate(day) {
    if (day && day.isCurrentMonth && day.date) {
        selectedDate.value = new Date(day.date);
        // Update FullCalendar to show the selected date
        if (fullCalendarRef.value?.getApi) {
            const calendarApi = fullCalendarRef.value.getApi();
            calendarApi.gotoDate(day.date);
        }
    }
}

// Sync small calendar month with FullCalendar when it changes
onMounted(() => {
    if (fullCalendarRef.value?.getApi) {
        const calendarApi = fullCalendarRef.value.getApi();
        const currentView = calendarApi.view;
        if (currentView) {
            const viewDate = currentView.currentStart;
            currentDate.value = new Date(viewDate);
            selectedDate.value = new Date(viewDate);
        }
    }
});

// Handle renewal created
const handleRenewalCreated = (renewal) => {
    // Refresh the calendar data
    router.reload({ only: ['calendar'] });
};

// Handle renewal updated
const handleRenewalUpdated = (renewal) => {
    // Refresh the calendar data
    router.reload({ only: ['calendar'] });
};

// Set calendar view
function setView(view) {
    currentView.value = view;
    if (fullCalendarRef.value?.getApi) {
        const calendarApi = fullCalendarRef.value.getApi();
        const viewMap = {
            'month': 'dayGridMonth',
            'week': 'timeGridWeek',
            'day': 'timeGridDay'
        };
        calendarApi.changeView(viewMap[view] || 'timeGridDay');
    }
}

// Navigate calendar (prev/next)
function navigateCalendar(direction) {
    if (fullCalendarRef.value?.getApi) {
        const calendarApi = fullCalendarRef.value.getApi();
        if (direction === 'prev') {
            calendarApi.prev();
        } else if (direction === 'next') {
            calendarApi.next();
        }
        // Update selected date to match calendar navigation
        const currentDate = calendarApi.getDate();
        selectedDate.value = new Date(currentDate);
    }
}

// Handle new task button click
function handleNewTask() {
    // TODO: Implement new task functionality
    console.log('New task clicked');
}

// Handle view task from upcoming deadline
function handleViewTask() {
    // TODO: Implement view task functionality
    console.log('View task clicked');
}
</script>




