
<x-filament-panels::page>
    @vite('resources/css/app.css')
    @vite('resources/js/filament/calendar.js')
    <style>
        .fc-scroller::-webkit-scrollbar { width: 8px; }
        .fc-scroller::-webkit-scrollbar-track { background: #f1f1f1; }
        .fc-scroller::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        .fc-scroller::-webkit-scrollbar-thumb:hover { background: #555; }
        /* Compact events — less padding keeps rows tight like Google Calendar */
        .fc-event { border-left-width: 3px; border-radius: 0.25rem; padding: 4px; }
        .fc-event-title { font-weight: 500; color: #111827; font-size: 0.75rem; }
        /* Main Calendar Today Highlight */
        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(59, 130, 246, 0.03) !important;
        }
        .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            background-color: #2563eb;
            color: white !important;
            border-radius: 9999px;
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 4px;
            font-weight: 600;
            padding: 0 6px;
        }
        
        .dark .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(156, 163, 175, 0.05) !important;
        }
        .dark .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
            background-color: #4b5563; /* gray-600 */
            color: #f1f5f9 !important;
        }

        .fc-timegrid-now-indicator-line { border-color: #ea4335; border-width: 2px; }
        .fc-timegrid-now-indicator-arrow { border: none; background-color: #ea4335; border-radius: 50%; width: 10px; height: 10px; margin-top: -4px; }
        
        /* Grid Lines contrast reduction */
        .fc-theme-standard td, .fc-theme-standard th, .fc-scrollgrid {
            border-color: #e5e7eb !important;
        }
        .dark .fc-theme-standard td, .dark .fc-theme-standard th, .dark .fc-scrollgrid {
            border-color: #374151 !important; /* gray-700 - much softer contrast for dark mode */
        }

        .fc-timegrid-slot-label { font-size: 0.75rem; color: #6b7280; }
        .dark .fc-timegrid-slot-label { color: #9ca3af; }
        
        /* Fixed row height — all rows are exactly 110px regardless of event count.
           No overflow:hidden so multi-day spanning events render correctly across cells. */
        .fc .fc-daygrid-day-frame { height: 110px; }
        
        [x-cloak] { display: none !important; }
        
        /* Dynamic Checkbox Colors */
        .custom-checkbox {
            @apply transition-all cursor-pointer border-gray-300 dark:border-gray-700 !important;
        }
        .custom-checkbox:checked {
            background-color: var(--checkbox-color) !important;
            border-color: var(--checkbox-color) !important;
        }
    </style>

    <div 
        x-data="{ 
            loaded: false,
            init() {
                // Poll until calendar.js has set window.filamentCalendar
                const check = () => {
                    if (typeof window.filamentCalendar !== 'undefined') {
                        this.loaded = true;
                    } else {
                        setTimeout(check, 50);
                    }
                };
                check();
            }
        }"
        class="h-full"
    >
        <!-- Loading State -->
        <div x-show="!loaded" class="flex items-center justify-center h-full">
            <div class="flex flex-col items-center gap-2">
                <x-filament::loading-indicator class="w-8 h-8 text-primary-500" />
                <span class="text-sm text-gray-500">Loading Calendar...</span>
            </div>
        </div>

        <!-- Actual Calendar (Only render when loaded) -->
        <template x-if="loaded">
            <div 
                class="h-full"
                x-data="filamentCalendar({ 
            events: @js($events ?? []),
            upcomingDeadline: @js($upcoming_deadline ?? null),
            overdueRenewals: @js($overdue_renewals ?? []),
            calendarRenewals: @js($calendar_renewals ?? []),
            todolists: @js($todolists ?? []),
            customers: @js($customers ?? []),
            eventTypes: @js($event_types ?? []),
            categoryColors: @js($category_colors ?? []),
            tenantId: @js($tenant_id ?? null)
        })"
                @refresh-event-types.window="refreshEventTypes()"
                x-cloak
                wire:ignore
            >
        <div class="grid md:grid-cols-5 gap-4 h-full">
            <!-- Left Sidebar: Widgets -->
            <div class="md:col-span-1 flex flex-col gap-4 h-full min-h-0">
                
                <!-- Mini Calendar Widget -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 row-span-1">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white" x-text="selectedMonthName"></h3>
                        <div class="flex gap-1">
                            <button @click="previousMonth" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                <x-heroicon-o-chevron-left class="w-4 h-4" />
                            </button>
                            <button @click="nextMonth" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                                 <x-heroicon-o-chevron-right class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-1 text-xs text-center">
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">SU</div>
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">MO</div>
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">TU</div>
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">WE</div>
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">TH</div>
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">FR</div>
                        <div class="text-gray-500 dark:text-gray-200 py-1 font-medium">SA</div>
                        
                        <template x-for="(dayItem, index) in calendarDays" :key="index">
                            <div 
                                @click="dayItem.day && selectDate(dayItem)"
                                :class="[
                                    'py-2 rounded cursor-pointer font-medium',
                                    !dayItem.isCurrentMonth ? 'text-gray-300 dark:text-gray-600' :
                                    dayItem.isToday ? 'bg-blue-600 text-white dark:bg-gray-700 dark:text-white font-semibold' :
                                    dayItem.isSelected ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' :
                                    'text-gray-700 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-800'
                                ]"
                                x-text="dayItem.day"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- My Calendars (Filters) -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">My calendars</h3>
                        <button 
                            wire:click="mountAction('createEventType')"
                            class="p-1 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                            title="Add event type"
                        >
                            <x-heroicon-m-plus class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="filters.events" @change="refreshAllSources()" 
                                   class="custom-checkbox w-4 h-4 rounded"
                                   :style="`--checkbox-color: ${categoryColors.events}`">
                            <span class="text-sm font-medium text-gray-700 dark:text-white group-hover:text-gray-900 dark:group-hover:text-white transition-colors">All Events</span>
                        </label>

                        <!-- Dynamic Event Types -->
                        <template x-for="type in eventTypes" :key="type.id">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input 
                                    type="checkbox" 
                                    x-model="filters.types[type.id]"
                                    @change="refreshAllSources()"
                                    class="custom-checkbox w-4 h-4 rounded"
                                    :style="`--checkbox-color: ${type.color}`"
                                >
                                <span class="text-sm font-medium text-gray-700 dark:text-white group-hover:text-gray-900 dark:group-hover:text-white transition-colors" x-text="type.name"></span>
                            </label>
                        </template>

                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800"></div>

                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="filters.todolist" @change="refreshAllSources()" 
                                   class="custom-checkbox w-4 h-4 rounded"
                                   :style="`--checkbox-color: ${categoryColors.todolist}`">
                            <span class="text-sm font-medium text-gray-700 dark:text-white group-hover:text-gray-900 dark:group-hover:text-white transition-colors">To-do Tasks</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="filters.renewals" @change="refreshAllSources()" 
                                   class="custom-checkbox w-4 h-4 rounded"
                                   :style="`--checkbox-color: ${categoryColors.renewals}`">
                            <span class="text-sm font-medium text-gray-700 dark:text-white group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Renewals</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="filters.holidays" @change="refreshAllSources()" 
                                   class="custom-checkbox w-4 h-4 rounded"
                                   :style="`--checkbox-color: ${categoryColors.holidays}`">
                            <span class="text-sm font-medium text-gray-700 dark:text-white group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Holidays</span>
                        </label>
                    </div>
                </div>

                <!-- Upcoming Deadlines -->
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 row-span-1">
                    <template x-if="upcomingDeadline && upcomingDeadline.title">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Deadline</h3>
                                <div class="px-2 py-1 rounded-full text-xs font-semibold"
                                     :class="upcomingDeadline.is_overdue ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'">
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-o-clock class="w-3 h-3" />
                                        <span x-text="upcomingDeadline.countdown"></span>
                                    </span>
                                </div>
                            </div>
                            
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1 line-clamp-1" x-text="upcomingDeadline.title"></h2>
                            
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" x-show="upcomingDeadline.more_count > 0">
                                + <span x-text="upcomingDeadline.more_count"></span> more due this week
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" x-show="!upcomingDeadline.more_count">
                                No other tasks due this week
                            </p>

                            <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors w-full sm:w-auto">
                                View task
                            </button>
                        </div>
                    </template>
                    
                    <template x-if="!upcomingDeadline || !upcomingDeadline.title">
                         <div class="text-center py-6">
                            <div class="text-gray-400 mb-2">
                                <x-heroicon-o-check-circle class="w-10 h-10 mx-auto" />
                            </div>
                            <p class="text-sm text-gray-500">No upcoming deadlines</p>
                        </div>
                    </template>
                </div>

                <!-- Overdue Renewals -->
                 <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 row-span-1 flex flex-col min-h-0 overflow-hidden">
                    <div class="flex items-center justify-between mb-4 flex-shrink-0">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Renewal</h3>
                        <button @click="openRenewalTableModal" class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">View all</button>
                    </div>
                    
                    <div class="space-y-4 overflow-y-auto pr-1" style="max-height: calc(3 * 64px);">
                        <template x-for="renewal in sidebarRenewals" :key="renewal.id">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-1" x-text="renewal.label"></h4>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium"
                                     :class="checkIsOverdue(renewal.Renew_Date) ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'">
                                    <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                    <span x-text="formatDateDisplay(renewal.Renew_Date)"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="sidebarRenewals.length === 0" class="text-sm text-gray-500 py-2">
                            No overdue inputs
                        </div>
                    </div>
                </div>

            </div>

            <!-- Main Calendar -->
            <div class="md:col-span-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col row-span-1">
                
                <!-- Calendar Toolbar -->
                <div class="flex items-center justify-between p-4 flex-shrink-0">
                    <!-- Left: Title or Actions -->
                    <div class="flex items-center gap-6">
                         <a href="#" @click.prevent="openEventModal()" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <x-heroicon-o-plus class="w-5 h-5" />
                            <span>New task</span>
                        </a>

                    </div>

                    <!-- Right: View Selector and Navigation -->
                    <div class="flex items-center gap-3">
                        <!-- View Toggle Group -->
                        <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                            <button
                                @click="setView('month')"
                                :class="[
                                    'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    currentView === 'month' || currentView === 'dayGridMonth'
                                        ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' 
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Month
                            </button>
                            <button
                                @click="setView('week')"
                                :class="[
                                    'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    currentView === 'week' 
                                        ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' 
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Week
                            </button>
                            <button
                                @click="setView('day')"
                                :class="[
                                    'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    currentView === 'day' 
                                        ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' 
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                Day
                            </button>
                            <button
                                @click="setView('list')"
                                :class="[
                                    'px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
                                    currentView === 'list' || currentView === 'listWeek'
                                        ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' 
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                List
                            </button>
                        </div>

                        <!-- Navigation Arrows -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="navigateMainCalendar('prev')"
                                class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <x-heroicon-o-chevron-left class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                            </button>
                            <button
                                @click="navigateMainCalendar('next')"
                                class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 p-4 overflow-y-auto" style="min-height:0">
                    <div x-ref="fullCalendar"></div>
                </div>
            </div>
        </div>


        <!-- NEW RENEWAL MODAL -->
        <div 
            x-show="showNewRenewalModal" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            style="display: none;"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="showNewRenewalModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                    @click="closeNewRenewalModal"
                    aria-hidden="true"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div 
                    x-show="showNewRenewalModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative z-50 inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                >
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">New Renewal</h3>
                                <div class="mt-2 space-y-4">
                                     <!-- Label -->
                                     <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Label <span class="text-red-500">*</span></label>
                                        <input type="text" x-model="renewalForm.label" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Enter label">
                                     </div>
                                     
                                     <!-- Start Date -->
                                     <div class="relative">
                                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date <span class="text-red-500">*</span></label>
                                         <input type="text" x-model="renewalForm.start_date" @input="formatDateInput($event, 'start_date')" @focus="showStartDatePicker = true" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="DD/MM/YYYY">
                                         <!-- Picker -->
                                         <div x-show="showStartDatePicker" @click.away="showStartDatePicker = false" class="absolute z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg p-4 w-64">
                                             <div class="flex justify-between mb-2">
                                                 <button @click="prevPickerMonth('start')" class="text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1"><</button>
                                                 <span x-text="startDatePickerMonthYear" class="text-gray-900 dark:text-white font-medium"></span>
                                                 <button @click="nextPickerMonth('start')" class="text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1">></button>
                                             </div>
                                             <div class="grid grid-cols-7 gap-1 text-xs">
                                                 <template x-for="d in startDatePickerDays">
                                                     <div @click="d.isCurrentMonth && selectPickerDate(d.date, 'start')" :class="['p-1 text-center cursor-pointer rounded', d.isSelected ? 'bg-blue-600 text-white' : '', !d.isCurrentMonth ? 'text-gray-300 dark:text-gray-600': 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']" x-text="d.day"></div>
                                                 </template>
                                             </div>
                                         </div>
                                     </div>

                                     <!-- Duration -->
                                     <div>
                                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Duration</label>
                                         <select x-model="renewalForm.duration" @change="calculateEndDate" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-no-repeat">
                                             <option value="">Select duration</option>
                                             <option value="1">1 Month</option>
                                             <option value="2">2 Months</option>
                                             <option value="3">3 Months</option>
                                             <option value="6">6 Months</option>
                                             <option value="12">1 Year</option>
                                         </select>
                                     </div>

                                     <!-- Renew Date -->
                                      <div class="relative">
                                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Renew Date (End Date) <span class="text-red-500">*</span></label>
                                         <input type="text" x-model="renewalForm.Renew_Date" @input="formatDateInput($event, 'Renew_Date')" @focus="showEndDatePicker = true" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="DD/MM/YYYY">
                                          <!-- Picker -->
                                         <div x-show="showEndDatePicker" @click.away="showEndDatePicker = false" class="absolute z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg p-4 w-64">
                                             <div class="flex justify-between mb-2">
                                                 <button @click="prevPickerMonth('end')" class="text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1"><</button>
                                                 <span x-text="endDatePickerMonthYear" class="text-gray-900 dark:text-white font-medium"></span>
                                                 <button @click="nextPickerMonth('end')" class="text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1">></button>
                                             </div>
                                             <div class="grid grid-cols-7 gap-1 text-xs">
                                                 <template x-for="d in endDatePickerDays">
                                                     <div @click="d.isCurrentMonth && selectPickerDate(d.date, 'end')" :class="['p-1 text-center cursor-pointer rounded', d.isSelected ? 'bg-blue-600 text-white' : '', !d.isCurrentMonth ? 'text-gray-300 dark:text-gray-600': 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']" x-text="d.day"></div>
                                                 </template>
                                             </div>
                                         </div>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button @click="submitRenewal" :disabled="isSubmittingRenewal" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            <span x-show="!isSubmittingRenewal">Add Renewal</span>
                            <span x-show="isSubmittingRenewal">Saving...</span>
                        </button>
                        <button @click="closeNewRenewalModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RENEWAL TABLE MODAL -->
        <div 
            x-show="showRenewalTableModal" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
             style="display: none;"
        >
             <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                 <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRenewalTableModal = false" aria-hidden="true"></div>
                 <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                 
                 <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                     <!-- Header -->
                     <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                         <h3 class="text-lg leading-6 font-medium text-gray-900">Renewals</h3>
                         <button @click="showRenewalTableModal = false" class="text-gray-400 hover:text-gray-500">
                             <span class="sr-only">Close</span>
                             <x-heroicon-o-x-mark class="h-6 w-6" />
                         </button>
                     </div>
                     
                     <!-- Body -->
                     <div class="p-6 h-[60vh] overflow-y-auto">
                         <div x-show="isLoadingRenewals" class="text-center py-4">Loading...</div>
                         <table x-show="!isLoadingRenewals" class="min-w-full divide-y divide-gray-200">
                             <thead class="bg-gray-50">
                                 <tr>
                                     <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                                     <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                     <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renew Date</th>
                                     <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                 </tr>
                             </thead>
                             <tbody class="bg-white divide-y divide-gray-200">
                                 <template x-for="renewal in allRenewals" :key="renewal.id">
                                     <tr :class="checkIsOverdue(renewal.Renew_Date) ? 'bg-red-50' : ''">
                                         <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="renewal.label || '—'"></td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDateDisplay(renewal.start_date)"></td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDateDisplay(renewal.Renew_Date)"></td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                             <select x-model="renewal.status_id" @change="updateRenewalStatus(renewal)" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                 <option :value="null">No Status</option>
                                                 <template x-for="status in renewalStatuses" :key="status.id">
                                                     <option :value="status.id" x-text="status.name || status.label"></option>
                                                 </template>
                                             </select>
                                         </td>
                                     </tr>
                                 </template>
                             </tbody>   
                         </table>
                     </div>
                 </div>
             </div>
        </div>
        <!-- EVENT HOVER CARD -->
        <div 
            x-show="showEventHoverCard"
            x-transition.opacity.duration.150ms
            class="fixed z-[110] bg-white dark:bg-gray-800 rounded-lg shadow-[0_4px_20px_rgb(0,0,0,0.1)] border border-gray-100 dark:border-gray-700 p-5 w-[320px] pointer-events-none"
            :style="`top: ${hoverCardPosition.top}px; left: ${hoverCardPosition.left}px;`"
            style="display: none;"
        >
            <h3 class="text-lg font-normal text-gray-800 dark:text-white mb-4 leading-tight font-sans tracking-wide" x-text="hoverCardData.title"></h3>
            
            <div class="space-y-4">
                <!-- Calendar Name -->
                <div class="flex items-center gap-4">
                    <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                        <x-heroicon-s-user class="w-4 h-4 text-gray-400 dark:text-gray-300" />
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-100 font-sans tracking-wide" x-text="hoverCardData.calendarName"></div>
                </div>

                <!-- Date -->
                <div class="flex items-center gap-4">
                    <div class="w-6 flex items-center justify-center">
                         <x-heroicon-o-clock class="w-5 h-5 text-gray-700 dark:text-gray-300" stroke-width="1.5" />
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-100 font-sans tracking-wide" x-text="hoverCardData.dateStr"></div>
                </div>

                <!-- Creator -->
                 <div class="flex items-center gap-4">
                    <div class="w-6 flex items-center justify-center">
                        <x-heroicon-o-user class="w-5 h-5 text-gray-700 dark:text-gray-300" stroke-width="1.5" />
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-100 font-sans tracking-wide">Created by: <span x-text="hoverCardData.creator"></span></div>
                </div>
            </div>
        </div>

        <!-- NEW EVENT/TASK MODAL -->
        <div 
            x-show="showEventModal" 
            class="fixed inset-0 z-[100]" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            style="display: none;"
        >
            <!-- Background overlay -->
            <div 
                class="fixed inset-0 transition-opacity" 
                @click="closeEventModal"
                aria-hidden="true"
            ></div>

            <!-- Absolute Modal Panel -->
            <div 
                x-show="showEventModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed z-[105] bg-white dark:bg-gray-800 rounded-2xl text-left shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-gray-200 dark:border-gray-700 transform transition-all sm:max-w-[448px] w-full overflow-hidden"
                :style="`top: ${eventModalPosition.top}px; left: ${eventModalPosition.left}px;`"
            >
                <!-- Top Control Bar -->
                <div class="flex items-center justify-end p-2 gap-1">
                    <!-- Trash button: only shown when editing a regular event -->
                    <button
                        x-show="canDelete"
                        x-cloak
                        @click="showDeleteConfirmModal = true"
                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        title="Delete event"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                    <button @click="closeEventModal" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <div class="px-6 py-2">
                    <!-- Title Input -->
                    <div class="mb-4 relative group">
                        <input 
                            type="text" 
                            x-model="eventForm.title" 
                            id="event-title-input" 
                            class="w-full !border-0 !border-b-2 !border-transparent focus:!ring-0 focus:!border-b-blue-600 bg-transparent focus:outline-none text-2xl font-normal dark:text-white pb-1 placeholder:text-gray-400 transition-colors" 
                            placeholder="Add title"
                        >
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-gray-100 group-focus-within:hidden -z-10"></div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex items-center gap-2 mb-6">
                        <button class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-4 py-1.5 rounded-lg text-sm font-medium" x-text="isEditing ? 'Edit event' : 'Event'"></button>
                    </div>

                    <!-- Icon Rows -->
                    <div class="space-y-6 mb-8">
                        <!-- DateTime -->
                        <div class="flex items-start gap-4">
                            <div class="w-6 flex justify-center text-gray-500 mt-1">
                                <x-heroicon-o-clock class="w-5 h-5" stroke-width="1.5" />
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-3">
                                    <!-- Start Date -->
                                    <input 
                                        type="date" 
                                        :value="getPickerDate(eventForm.start)"
                                        @change="setPickerDate('start', $event.target.value)"
                                        class="text-sm border-0 border-b border-transparent focus:border-blue-600 focus:ring-0 p-0 bg-transparent cursor-pointer dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-1"
                                    >
                                    
                                    <!-- Start Time -->
                                    <template x-if="!eventForm.allDay">
                                        <div class="flex items-center gap-2">
                                            <input 
                                                type="time" 
                                                :value="getPickerTime(eventForm.start)"
                                                @change="setPickerTime('start', $event.target.value)"
                                                class="text-sm border-0 border-b border-transparent focus:border-blue-600 focus:ring-0 p-0 bg-transparent cursor-pointer dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-1"
                                            >
                                            <span class="text-gray-400">–</span>
                                            
                                            <!-- End Time -->
                                            <input 
                                                type="time" 
                                                :value="getPickerTime(eventForm.end)"
                                                @change="setPickerTime('end', $event.target.value)"
                                                class="text-sm border-0 border-b border-transparent focus:border-blue-600 focus:ring-0 p-0 bg-transparent cursor-pointer dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-1"
                                            >
                                        </div>
                                    </template>

                                    <!-- End Date (if different from start or if multi-day) -->
                                    <template x-if="getPickerDate(eventForm.start) !== getPickerDate(eventForm.end)">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400" x-show="eventForm.allDay">–</span>
                                            <input 
                                                type="date" 
                                                :value="getPickerDate(eventForm.end)"
                                                @change="setPickerDate('end', $event.target.value)"
                                                class="text-sm border-0 border-b border-transparent focus:border-blue-600 focus:ring-0 p-0 bg-transparent cursor-pointer dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-1"
                                            >
                                        </div>
                                    </template>
                                </div>

                                <!-- All day toggle -->
                                <div class="flex items-center gap-2 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input 
                                            type="checkbox" 
                                            x-model="eventForm.allDay"
                                            @change="if(!eventForm.allDay) { 
                                                const s = new Date(eventForm.start); s.setHours(9,0,0,0); eventForm.start = s;
                                                const e = new Date(eventForm.start); e.setHours(10,0,0,0); eventForm.end = e;
                                            }"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                                        >
                                        <span class="text-xs text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300">All day</span>
                                    </label>
                                    <span class="text-xs text-gray-400">·</span>
                                    <span class="text-xs text-gray-400">Does not repeat</span>
                                </div>
                            </div>
                        </div>

                        <!-- Event Type -->
                        <div class="flex items-center gap-4 group">
                            <div class="w-6 flex justify-center text-gray-500">
                                <x-heroicon-o-tag class="w-5 h-5" stroke-width="1.5" />
                            </div>
                            <div class="flex-1">
                                <select x-model="eventForm.event_type_id" class="w-full !border-0 p-0 focus:ring-0 bg-transparent text-sm text-gray-700 dark:text-gray-300 placeholder:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-1 -mx-1 transition-colors">
                                    <option value="">Select event type</option>
                                    <template x-for="t in eventTypes" :key="t.id">
                                        <option :value="t.id" x-text="t.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Customer (Guests) -->
                        <div class="flex items-center gap-4 group">
                            <div class="w-6 flex justify-center text-gray-500">
                                <x-heroicon-o-users class="w-5 h-5" stroke-width="1.5" />
                            </div>
                            <div class="flex-1">
                                <select x-model="eventForm.customer_id" class="w-full !border-0 p-0 focus:ring-0 bg-transparent text-sm text-gray-700 dark:text-gray-300 placeholder:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-1 -mx-1 transition-colors">
                                    <option value="">No customer (Add guests)</option>
                                    <template x-for="c in customers" :key="c.id">
                                        <option :value="c.id" x-text="c.label"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                         <!-- Placeholder Meet Row -->
                         <div class="flex items-center gap-4 group cursor-pointer">
                            <div class="w-6 flex justify-center text-blue-600">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                            </div>
                            <div class="flex-1 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-1 -mx-1 transition-colors">
                                Add Google Meet video conferencing
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="flex items-start gap-4">
                            <div class="w-6 flex justify-center text-gray-500 mt-1">
                                <x-heroicon-o-bars-3-bottom-left class="w-5 h-5" stroke-width="1.5" />
                            </div>
                            <textarea 
                                x-model="eventForm.description" 
                                rows="1" 
                                @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                class="flex-1 !border-0 p-0 focus:ring-0 bg-transparent text-sm text-gray-700 dark:text-white resize-none placeholder:text-gray-400" 
                                placeholder="Add description"
                            ></textarea>
                        </div>
                        
                         <!-- Error Message -->
                         <div x-show="eventForm.error" x-cloak class="ml-10 text-red-500 text-xs" x-text="eventForm.error"></div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 flex justify-between items-center bg-transparent">
                     <button class="text-sm font-medium text-blue-700 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 px-3 py-2 rounded-md transition-colors">More options</button>
                     <div class="flex items-center gap-4">
                        <div class="text-xs text-gray-400" x-show="eventForm.saving">Saving...</div>
                        <button 
                            @click="saveEvent" 
                            :disabled="eventForm.saving" 
                            type="button" 
                            class="inline-flex justify-center rounded-full !px-4 py-2 !bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none shadow-sm transition-all active:scale-[0.98] disabled:opacity-50"
                        >
                            <span x-text="isEditing ? 'Update' : 'Save'"></span>
                        </button>
                     </div>
                </div>
            </div>
        </div><!-- end event modal -->

        <!-- DELETE CONFIRMATION MODAL (Filament style) -->
        <div
            x-show="showDeleteConfirmModal"
            class="fixed inset-0 z-[200] flex items-center justify-center"
            style="display: none;"
            @keydown.escape.window="showDeleteConfirmModal = false"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50" @click="showDeleteConfirmModal = false"></div>

            <!-- Panel -->
            <div
                x-show="showDeleteConfirmModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative z-10 bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 text-center"
            >
                <!-- Close X -->
                <button @click="showDeleteConfirmModal = false" class="absolute top-3 right-3 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>

                <!-- Red trash icon -->
                <div class="flex items-center justify-center w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                    Delete <span x-text="eventForm.title"></span>
                </h3>

                <!-- Body -->
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Are you sure you would like to do this?
                </p>

                <!-- Actions -->
                <div class="flex gap-3 mt-6">
                    <button
                        @click="showDeleteConfirmModal = false"
                        type="button"
                        class="flex-1 !px-4 !py-2 !text-sm !font-medium !text-gray-700 dark:!text-gray-300 !bg-white dark:!bg-gray-700 !border !border-gray-300 dark:!border-gray-600 !rounded-full hover:!bg-gray-50 dark:hover:!bg-gray-600 !transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteEvent"
                        :disabled="isDeletingEvent"
                        type="button"
                        class="flex-1 !px-4 !py-2 !text-sm !font-medium !text-white !bg-red-600 hover:!bg-red-700 !rounded-full !shadow-sm !transition-all active:!scale-[0.98] disabled:!opacity-50"
                    >
                        <span x-show="!isDeletingEvent">Delete</span>
                        <span x-show="isDeletingEvent">Deleting...</span>
                    </button>
                </div>
            </div>
        </div><!-- end delete confirm modal -->

            </div><!-- filamentCalendar scope -->
        </div>

    </template>
    <x-filament-actions::modals />
</div>
</x-filament-panels::page>


