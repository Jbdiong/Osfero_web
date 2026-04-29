import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import googleCalendarPlugin from '@fullcalendar/google-calendar';
import axios from 'axios';

// Configure Axios
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const filamentCalendarFactory = ({
    events = [],
    todolists = [],
    initialDate = null,
    upcomingDeadline = null,
    overdueRenewals = [],
    calendarRenewals = [],
    customers = [],
    tenantId = null
}) => ({
    // --- Main Calendar State ---
    calendar: null,
    events: [],
    fullCalendarEvents: [],
    fullCalendarTodolists: [],
    fullCalendarRenewals: [],
    currentView: 'dayGridMonth',
    selectedDate: null,
    currentDate: null, // For mini calendar navigation

    filters: {
        events: true,
        todolist: true,
        renewals: true,
        holidays: true
    },

    // --- Sidebar Data ---
    upcomingDeadline: upcomingDeadline || { title: null, more: null, countdown: null },
    sidebarRenewals: overdueRenewals || [],
    customers: customers || [],
    tenantId: tenantId || null,

    // --- Modals State ---
    showNewRenewalModal: false,
    showRenewalTableModal: false,

    // --- New Renewal Form State ---
    renewalForm: {
        label: '',
        start_date: '', // DD/MM/YYYY
        duration: '',
        Renew_Date: '', // DD/MM/YYYY
        lead_id: null
    },
    isSubmittingRenewal: false,
    showStartDatePicker: false,
    showEndDatePicker: false,
    startDatePickerDate: new Date(),
    endDatePickerDate: new Date(),

    // --- Renewal Table State ---
    allRenewals: [],
    renewalStatuses: [],
    audits: [],
    isLoadingRenewals: false,
    isLoadingAudits: false,
    showAuditView: false,
    updatingRenewalIds: [],

    // --- Google Style Event Modal State ---
    showEventModal: false,
    eventModalPosition: { top: 0, left: 0 },
    isEditing: false,
    canDelete: false,
    showDeleteConfirmModal: false,
    isDeletingEvent: false,
    eventForm: {
        id: null,
        title: '',
        type: 'event',
        start: null,
        end: null,
        allDay: true,
        customer_id: '',
        description: '',
        saving: false,
        error: ''
    },

    // --- Hover Event Tooltip ---
    showEventHoverCard: false,
    hoverCardPosition: { top: 0, left: 0 },
    hoverCardData: {
        title: '',
        calendarName: '',
        dateStr: '',
        creator: ''
    },

    // Helpers for Date/Time pickers (converting Date objects to/from strings)
    getPickerDate(date) {
        if (!date) return '';
        const d = new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    },
    getPickerTime(date) {
        if (!date) return '';
        const d = new Date(date);
        const pad = (n) => String(n).padStart(2, '0');
        return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    },
    setPickerDate(field, val) {
        const d = new Date(this.eventForm[field]);
        const parts = val.split('-');
        if (parts.length === 3) {
            d.setFullYear(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
            this.eventForm[field] = new Date(d);
        }
    },
    setPickerTime(field, val) {
        const d = new Date(this.eventForm[field]);
        const parts = val.split(':');
        if (parts.length === 2) {
            d.setHours(parseInt(parts[0]), parseInt(parts[1]), 0, 0);
            this.eventForm[field] = new Date(d);
        }
    },

    // =================================================================================================
    // COMPUTED PROPERTIES (Mapped as getters/functions for Alpine)
    // =================================================================================================

    // Mini Calendar Computed
    get currentMonth() { return this.currentDate.getMonth(); },
    get currentYear() { return this.currentDate.getFullYear(); },
    get selectedMonthName() {
        return this.currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    },
    get today() {
        const now = new Date();
        return { year: now.getFullYear(), month: now.getMonth(), date: now.getDate() };
    },
    get calendarDays() {
        const year = this.currentYear;
        const month = this.currentMonth;
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay(); // 0 = Sunday

        const days = [];
        for (let i = 0; i < startingDayOfWeek; i++) {
            days.push({ day: null });
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = year === this.today.year && month === this.today.month && day === this.today.date;
            const dateObj = new Date(year, month, day);
            const isSelected = this.selectedDate && dateObj.toDateString() === this.selectedDate.toDateString();
            days.push({ day, isToday, isCurrentMonth: true, isSelected, date: dateObj });
        }
        return days;
    },

    // Overdue Count for Table Modal
    get overdueCount() {
        return this.allRenewals.filter(r => this.checkIsOverdue(r.Renew_Date)).length;
    },

    // Date Pickers inside Modal
    get startDatePickerDays() { return this.generatePickerDays(this.startDatePickerDate, this.renewalForm.start_date); },
    get endDatePickerDays() { return this.generatePickerDays(this.endDatePickerDate, this.renewalForm.Renew_Date); },
    get startDatePickerMonthYear() { return this.startDatePickerDate.toLocaleDateString('default', { month: 'long', year: 'numeric' }); },
    get endDatePickerMonthYear() { return this.endDatePickerDate.toLocaleDateString('default', { month: 'long', year: 'numeric' }); },


    // =================================================================================================
    // INITIALIZATION
    // =================================================================================================
    init() {
        this.selectedDate = initialDate ? new Date(initialDate) : new Date();
        this.currentDate = new Date(this.selectedDate);

        this.events = events || [];
        this.processEvents(this.events);
        this.processTodolists(todolists);
        this.processRenewals(calendarRenewals);

        // Delay initialization to ensure DOM is ready and styled
        setTimeout(() => {
            this.initCalendar();
        }, 50);


        this.$watch('selectedDate', (newDate) => {
            if (this.calendar) this.calendar.gotoDate(newDate);
        });

        // Watchers for Modal Form Date logic
        this.$watch('renewalForm.start_date', (val) => {
            if (val && val.length === 10) {
                const parts = val.split('/');
                if (parts.length === 3) this.startDatePickerDate = new Date(parts[2], parts[1] - 1, 1);
            }
        });
        this.$watch('renewalForm.Renew_Date', (val) => {
            if (val && val.length === 10) {
                const parts = val.split('/');
                if (parts.length === 3) this.endDatePickerDate = new Date(parts[2], parts[1] - 1, 1);
            }
        });
    },


    // =================================================================================================
    // CALENDAR LOGIC
    // =================================================================================================
    processEvents(rawEvents) {
        if (!rawEvents) return;
        const eventDate = new Date(this.selectedDate);
        const dateStr = eventDate.toISOString().split('T')[0];

        this.fullCalendarEvents = rawEvents.map(event => {
            const start = new Date(event.start);
            const end = new Date(event.end || event.start);
            
            let displayAllDay = !!event.allDay;
            let isMultiDayTimed = false;

            // Detect timed events spanning across midnight
            if (!displayAllDay && start.toDateString() !== end.toDateString()) {
                displayAllDay = true;
                isMultiDayTimed = true;
            }

            // For display purposes with allDay: true, FullCalendar end date is exclusive.
            // If it's a multi-day event, we must ensure the end date is the day AFTER the last day.
            let displayEnd = event.end;
            if (displayAllDay) {
                const dEnd = new Date(end);
                dEnd.setDate(dEnd.getDate() + 1);
                // Use local date string YYYY-MM-DD instead of ISO (which is UTC)
                const year = dEnd.getFullYear();
                const month = String(dEnd.getMonth() + 1).padStart(2, '0');
                const day = String(dEnd.getDate()).padStart(2, '0');
                displayEnd = `${year}-${month}-${day}`;
            }

            return {
                id: event.id.toString(),
                title: event.title,
                start: event.start, 
                end: displayEnd,     
                allDay: displayAllDay,
                backgroundColor: '#ffd7b5', 
                borderColor: '#ff6700', 
                textColor: '#000000',
                extendedProps: { 
                    ...event, 
                    isMultiDayTimed: isMultiDayTimed,
                    realEnd: event.end // Keep real end for modal
                }
            };
        });

        if (this.calendar) {
            const localSource = this.calendar.getEventSourceById('local-events');
            if (localSource) {
                localSource.remove();
            }
            this.calendar.addEventSource({
                id: 'local-events',
                events: this.fullCalendarEvents,
                display: 'block',
                backgroundColor: '#ffd7b5',
                borderColor: '#ff6700',
                textColor: '#000000'
            });
        }
    },

    processTodolists(rawTodolists) {
        if (!rawTodolists) return;

        this.fullCalendarTodolists = rawTodolists.map(task => ({
            id: task.id.toString(),
            title: task.title,
            start: task.start,
            end: task.end || undefined,
            allDay: true,
            extendedProps: { ...task, calendarType: 'todolist' }
        }));

        if (this.calendar) {
            const existing = this.calendar.getEventSourceById('todolist-events');
            if (existing) existing.remove();
            this.calendar.addEventSource({
                id: 'todolist-events',
                events: this.fullCalendarTodolists,
                display: 'block',
                backgroundColor: '#dbeafe',
                borderColor: '#3b82f6',
                textColor: '#1e3a8a'
            });
        }
    },

    processRenewals(rawRenewals) {
        if (!rawRenewals) return;

        this.fullCalendarRenewals = rawRenewals.map(renewal => ({
            id: renewal.id.toString(),
            title: renewal.title,
            start: renewal.start,
            allDay: true,
            backgroundColor: '#fee2e2',
            borderColor: '#ef4444',
            textColor: '#991b1b',
            extendedProps: { ...renewal, calendarType: 'renewal' }
        }));

        if (this.calendar) {
            const existing = this.calendar.getEventSourceById('renewal-events');
            if (existing) existing.remove();
            this.calendar.addEventSource({
                id: 'renewal-events',
                events: this.fullCalendarRenewals,
                display: 'block',
                backgroundColor: '#fee2e2',
                borderColor: '#ef4444',
                textColor: '#991b1b'
            });
        }
    },

    toggleSource(sourceId, isVisible) {
        if (!this.calendar) return;
        
        if (!isVisible) {
            const source = this.calendar.getEventSourceById(sourceId);
            if (source) source.remove();
        } else {
            // Re-add
            if (sourceId === 'local-events') {
                this.calendar.addEventSource({
                    id: 'local-events',
                    events: this.fullCalendarEvents,
                    display: 'block',
                    backgroundColor: '#ffd7b5',
                    borderColor: '#ff6700',
                    textColor: '#000000'
                });
            } else if (sourceId === 'todolist-events') {
                this.calendar.addEventSource({
                    id: 'todolist-events',
                    events: this.fullCalendarTodolists,
                    display: 'block',
                    backgroundColor: '#dbeafe',
                    borderColor: '#3b82f6',
                    textColor: '#1e3a8a'
                });
            } else if (sourceId === 'renewal-events') {
                this.calendar.addEventSource({
                    id: 'renewal-events',
                    events: this.fullCalendarRenewals,
                    display: 'block',
                    backgroundColor: '#fee2e2',
                    borderColor: '#ef4444',
                    textColor: '#991b1b'
                });
            } else if (sourceId === 'google-holidays') {
                this.calendar.addEventSource({
                    id: 'google-holidays',
                    googleCalendarId: 'en.malaysia#holiday@group.v.calendar.google.com',
                    color: '#C6FFCA',
                    textColor: '#000000',
                    borderColor: '#008002'
                });
            }
        }
    },

    initCalendar() {
        const calendarEl = this.$refs.fullCalendar;
        if (!calendarEl) return;

        this.calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin, googleCalendarPlugin],
            googleCalendarApiKey: 'AIzaSyC7-5vxTrZT5gXzVLWUIvY9sA1TWu5QQLg',
            initialView: 'dayGridMonth',
            initialDate: this.selectedDate,
            headerToolbar: false,
            height: '100%',
            dayMaxEvents: true,    // auto-fit to row height; overflow becomes "+X more"
            nowIndicator: true,
            selectable: true,
            selectMirror: true,
            eventContent: (arg) => {
                const ev = arg.event;
                const isMultiDayTimed = !ev.extendedProps?.all_day && ev.allDay;
                
                if (isMultiDayTimed) {
                    const start = ev.start;
                    const end = ev.extendedProps?.realEnd ? new Date(ev.extendedProps.realEnd) : ev.end;
                    
                    const formatTime = (d) => d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
                    const formatDate = (d) => `${d.getDate()}/${d.getMonth() + 1}`;
                    
                    const rangeStr = `${formatDate(start)} ${formatTime(start)} - ${formatDate(end)} ${formatTime(end)}`;
                    
                    return {
                        html: `<div class="fc-content" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85em;">
                                <span class="fc-time" style="font-weight: 600; margin-right: 4px;">${rangeStr}</span>
                                <span class="fc-title">${ev.title}</span>
                              </div>`
                    };
                }
                return null; // use default
            },
            select: (info) => {
                let start = info.start;
                let end = info.end;
                let allDay = info.allDay;

                if (!allDay && (end.getTime() - start.getTime() === 30 * 60000)) {
                    end = new Date(start.getTime() + 60 * 60000);
                }

                this.openEventModal(start, end, allDay, info.jsEvent);
            },
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                this.showEventHoverCard = false;

                const ev = info.event;
                const isGoogleHoliday = ev.source && ev.source.id === 'google-holidays';
                const isTodolist = ev.source && ev.source.id === 'todolist-events';
                const isRenewal = ev.source && ev.source.id === 'renewal-events';

                // Holidays, todolists, and renewals are read-only — don't open edit modal
                if (isGoogleHoliday || isTodolist || isRenewal) return;

                this.openEditModal(ev, info.jsEvent);
            },
            // Re-clamp events after navigation or async Google events load
            datesSet: () => {
                setTimeout(() => {
                    if (this.calendar) this.calendar.updateSize();
                }, 50);
            },
            eventMouseEnter: (info) => {
                const ev = info.event;
                const isGoogleHoliday = ev.source && ev.source.id === 'google-holidays';
                const isTodolist = ev.source && ev.source.id === 'todolist-events';
                const isRenewal = ev.source && ev.source.id === 'renewal-events';

                let calendarName = 'Primary Calendar';
                if (isGoogleHoliday) {
                    calendarName = 'Holidays in Malaysia';
                } else if (isTodolist) {
                    calendarName = 'To-do Task';
                } else if (isRenewal) {
                    calendarName = 'Customer Renewal';
                } else if (ev.extendedProps.customer_id) {
                    const cust = this.customers.find(c => String(c.id) === String(ev.extendedProps.customer_id));
                    if (cust) calendarName = cust.label;
                }

                this.hoverCardData = {
                    title: ev.title,
                    calendarName: calendarName,
                    dateStr: ev.start.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }),
                    creator: isGoogleHoliday ? 'Holidays in Malaysia' : (isTodolist ? 'Todolist' : (isRenewal ? 'System' : (ev.extendedProps.creator || 'User Setup')))
                };

                const rect = info.el.getBoundingClientRect();
                const cardWidth = 320;
                const cardHeight = 160;

                let top = rect.top;
                let left = rect.right + 10;

                if (left + cardWidth > window.innerWidth) {
                    left = rect.left - cardWidth - 10;
                }

                if (top + cardHeight > window.innerHeight) {
                    top = window.innerHeight - cardHeight - 10;
                }

                if (top < 10) top = 10;
                if (left < 10) left = 10;

                this.hoverCardPosition = { top, left };
                this.showEventHoverCard = true;
            },
            eventMouseLeave: (info) => {
                this.showEventHoverCard = false;
            },
            eventSources: [
                {
                    id: 'local-events',
                    events: this.fullCalendarEvents,
                    display: 'block',
                    backgroundColor: '#ffd7b5',
                    borderColor: '#ff6700',
                    textColor: '#000000'
                },
                {
                    id: 'todolist-events',
                    events: this.fullCalendarTodolists,
                    display: 'block',
                    backgroundColor: '#dbeafe',
                    borderColor: '#3b82f6',
                    textColor: '#1e3a8a'
                },
                {
                    id: 'renewal-events',
                    events: this.fullCalendarRenewals,
                    display: 'block',
                    backgroundColor: '#fee2e2',
                    borderColor: '#ef4444',
                    textColor: '#991b1b'
                },
                this.filters.holidays ? {
                    id: 'google-holidays',
                    googleCalendarId: 'en.malaysia#holiday@group.v.calendar.google.com',
                    color: '#C6FFCA',
                    textColor: '#000000',
                    borderColor: '#008002'
                } : null
            ].filter(Boolean),
            eventContent: (arg) => {
                const isGoogleHoliday = arg.event.source && arg.event.source.id === 'google-holidays';
                const isTodolist = arg.event.source && arg.event.source.id === 'todolist-events';

                if (isGoogleHoliday) {
                    return {
                        html: `<div class=" flex items-center gap-1 overflow-hidden">
                            <svg class="w-2 h-2 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                            <div class="text-xs font-semibold text-gray-700 capitalize truncate flex-1 min-w-0" title="${arg.event.title}">${arg.event.title}</div>
                        </div>`
                    };
                }

                if (isTodolist) {
                    return {
                        html: `<div class="flex items-center gap-1 overflow-hidden">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            <div class="text-xs font-semibold truncate flex-1 min-w-0" title="${arg.event.title}">${arg.event.title}</div>
                        </div>`
                    };
                }

                return {
                    html: `<div class=" overflow-hidden"><div class="text-xs font-semibold truncate" title="${arg.event.title}">${arg.event.title}</div></div>`
                };
            }
        });
        this.calendar.render();
    },

    populateCalendarDays() {
        // Logic handled by getter `calendarDays`
    },

    previousMonth() { this.currentDate = new Date(this.currentYear, this.currentMonth - 1, 1); },
    nextMonth() { this.currentDate = new Date(this.currentYear, this.currentMonth + 1, 1); },
    selectDate(day) {
        if (day && day.isCurrentMonth && day.date) {
            this.selectedDate = new Date(day.date);
            this.processEvents(events);
            // Also jump main calendar to this date
            if (this.calendar) this.calendar.gotoDate(this.selectedDate);
        }
    },

    // --- Main Calendar Navigation ---
    setView(view) {
        this.currentView = view;
        if (this.calendar) {
            const viewMap = {
                'month': 'dayGridMonth',
                'week': 'timeGridWeek',
                'day': 'timeGridDay',
                'list': 'listWeek'
            };
            this.calendar.changeView(viewMap[view] || 'dayGridMonth');
        }
    },

    navigateMainCalendar(direction) {
        if (this.calendar) {
            if (direction === 'prev') this.calendar.prev();
            else if (direction === 'next') this.calendar.next();
            else if (direction === 'today') this.calendar.today();

            // Sync internal state
            this.selectedDate = this.calendar.getDate();
            this.currentDate = new Date(this.selectedDate);

            // Force re-measure so dayMaxEvents: true clamps rows correctly
            setTimeout(() => {
                if (this.calendar) this.calendar.updateSize();
            }, 100);
        }
    },


    // =================================================================================================
    // NEW EVENT/TASK MODAL LOGIC (Google Style)
    // =================================================================================================

    openEventModal(start, end, allDay, jsEvent = null) {
        this.isEditing = false;
        this.canDelete = false;
        this.showDeleteConfirmModal = false;
        this.fetchCustomers(); // always fetch fresh customer list

        if (!start) {
            start = new Date();
            // Default 1 hour from now without seconds
            start.setMinutes(0, 0, 0);
            end = new Date(start.getTime() + 60 * 60000);
            allDay = false;
        }

        this.eventForm = {
            id: null,
            title: '',
            type: 'event',
            start: start,
            end: allDay ? new Date(end.getTime() - 24 * 60 * 60 * 1000) : end,
            allDay: allDay,
            customer_id: '',
            description: '',
            saving: false,
            error: ''
        };

        if (jsEvent) {
            const modalWidth = 450;
            const modalHeight = 450; // estimate
            let top = jsEvent.clientY;
            let left = jsEvent.clientX + 20;

            if (left + modalWidth > window.innerWidth) {
                left = jsEvent.clientX - modalWidth - 20;
            }

            if (top + modalHeight > window.innerHeight) {
                top = window.innerHeight - modalHeight - 20;
            }
            if (top < 0) top = 20;
            if (left < 0) left = 20;

            this.eventModalPosition = { top, left };
        } else {
            this.eventModalPosition = {
                top: Math.max((window.innerHeight - 450) / 2, 20),
                left: Math.max((window.innerWidth - 450) / 2, 20)
            };
        }

        this.showEventModal = true;
    },

    closeEventModal() {
        this.showEventModal = false;
        this.showDeleteConfirmModal = false;
        if (this.calendar) {
            this.calendar.unselect();
        }
    },

    openEditModal(ev, jsEvent = null) {
        this.isEditing = true;
        this.canDelete = true;
        this.showDeleteConfirmModal = false;
        this.fetchCustomers(); // always fetch fresh customer list

        this.eventForm = {
            id: ev.id,
            title: ev.title,
            type: 'event',
            start: ev.start,
            end: ev.extendedProps?.realEnd ? new Date(ev.extendedProps.realEnd) : (ev.end || ev.start),
            allDay: !!ev.extendedProps?.all_day, // Use the real status from DB, not the display status
            customer_id: ev.extendedProps?.customer_id || '',
            description: ev.extendedProps?.description || '',
            saving: false,
            error: ''
        };

        // Position near click point
        if (jsEvent) {
            const modalWidth = 450;
            const modalHeight = 450;
            let top = jsEvent.clientY;
            let left = jsEvent.clientX + 20;
            if (left + modalWidth > window.innerWidth) left = jsEvent.clientX - modalWidth - 20;
            if (top + modalHeight > window.innerHeight) top = window.innerHeight - modalHeight - 20;
            if (top < 0) top = 20;
            if (left < 0) left = 20;
            this.eventModalPosition = { top, left };
        } else {
            this.eventModalPosition = {
                top: Math.max((window.innerHeight - 450) / 2, 20),
                left: Math.max((window.innerWidth - 450) / 2, 20)
            };
        }

        this.showEventModal = true;
    },

    async saveEvent() {
        if (!this.eventForm.title.trim()) {
            this.eventForm.error = 'Title is required.';
            return;
        }
        this.eventForm.error = '';
        this.eventForm.saving = true;

        // Ensure CSRF token is present for axios
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }

        const toISO = (d) => {
            if (!d) return null;
            const pad = (n) => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
        };

        let startTime = this.eventForm.start;
        let endTime = this.eventForm.end || this.eventForm.start;

        // If it's an all-day event, ensure start is at 00:00 and end is at 23:59
        if (this.eventForm.allDay) {
            if (startTime) {
                startTime = new Date(startTime);
                startTime.setHours(0, 0, 0, 0);
            }
            if (endTime) {
                endTime = new Date(endTime);
                endTime.setHours(23, 59, 59, 999);
            }
        }

        try {
            let ev;
            if (this.isEditing) {
                // Update existing event
                const response = await axios.patch(`/calendar/events/${this.eventForm.id}`, {
                    title: this.eventForm.title.trim(),
                    description: this.eventForm.description || null,
                    start_time: toISO(startTime),
                    end_time: toISO(endTime),
                    customer_id: this.eventForm.customer_id || null,
                    all_day: this.eventForm.allDay ? 1 : 0,
                });
                if (response.data.success) {
                    ev = response.data.event;
                    
                    // Update local data cache
                    const idx = this.events.findIndex(e => String(e.id) === String(ev.id));
                    if (idx !== -1) {
                        this.events[idx] = ev;
                    }
                    
                    // Re-process all events to update calendar display
                    this.processEvents(this.events);
                    this.closeEventModal();
                }
            } else {
                // Create new event
                const response = await axios.post('/calendar/events/quick-store', {
                    title: this.eventForm.title.trim(),
                    description: this.eventForm.description || null,
                    start_time: toISO(startTime),
                    end_time: toISO(endTime),
                    customer_id: this.eventForm.customer_id || null,
                    all_day: this.eventForm.allDay ? 1 : 0,
                });
                if (response.data.success) {
                    ev = response.data.event;
                    
                    // Add to local data cache
                    this.events.push(ev);
                    
                    // Re-process all events to update calendar display
                    this.processEvents(this.events);
                    this.closeEventModal();
                }
            }
        } catch (err) {
            const msg = err.response?.data?.message
                ?? Object.values(err.response?.data?.errors ?? {})[0]?.[0]
                ?? 'Failed to save. Please try again.';
            this.eventForm.error = msg;
        } finally {
            this.eventForm.saving = false;
        }
    },

    async deleteEvent() {
        this.isDeletingEvent = true;
        try {
            await axios.delete(`/calendar/events/${this.eventForm.id}`);
            const calEv = this.calendar.getEventById(String(this.eventForm.id));
            if (calEv) calEv.remove();
            this.showDeleteConfirmModal = false;
            this.closeEventModal();
        } catch (err) {
            alert(err.response?.data?.message || 'Failed to delete event.');
        } finally {
            this.isDeletingEvent = false;
        }
    },

    async fetchCustomers() {
        try {
            const response = await axios.get('/calendar/customers');
            this.customers = response.data;
        } catch (err) {
            console.error('Failed to fetch customers:', err);
        }
    },

    formatEventDateTimeDisplay() {
        if (!this.eventForm.start) return '';

        const start = this.eventForm.start;
        const end = this.eventForm.end;
        const allDay = this.eventForm.allDay;

        const formatDate = (d) => d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        const formatTime = (d) => d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase();

        if (allDay) {
            // FullCalendar passes start/end days. End is exclusive.
            let endDisplayObj = new Date(end);
            endDisplayObj.setDate(endDisplayObj.getDate() - 1);

            if (start.getTime() === endDisplayObj.getTime() || endDisplayObj < start) {
                return formatDate(start);
            } else {
                return `${formatDate(start)} – ${formatDate(endDisplayObj)}`;
            }
        } else {
            const isSameDay = start.toDateString() === end.toDateString();
            if (isSameDay) {
                return `${formatDate(start)} ⋅ ${formatTime(start)} – ${formatTime(end)}`;
            } else {
                return `${formatDate(start)}, ${formatTime(start)} – ${formatDate(end)}, ${formatTime(end)}`;
            }
        }
    },


    // =================================================================================================
    // RENEWAL FORM LOGIC
    // =================================================================================================
    openNewRenewalModal() {
        this.resetRenewalForm();
        this.showNewRenewalModal = true;
        // Set default dates to today
        const today = new Date();
        const fmt = this.formatDateForInput(today);
        this.renewalForm.start_date = fmt;
        this.renewalForm.Renew_Date = fmt;
    },

    closeNewRenewalModal() {
        this.showNewRenewalModal = false;
    },

    resetRenewalForm() {
        this.renewalForm = { label: '', start_date: '', duration: '', Renew_Date: '', lead_id: null };
    },

    formatDateInput(e, field) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2);
        if (value.length >= 5) value = value.substring(0, 5) + '/' + value.substring(5, 9);
        this.renewalForm[field] = value;
    },

    calculateEndDate() {
        if (!this.renewalForm.start_date) return;
        const parts = this.renewalForm.start_date.split('/');
        if (parts.length !== 3) return;

        const startDate = new Date(parts[2], parts[1] - 1, parts[0]);
        if (isNaN(startDate.getTime())) return;

        if (this.renewalForm.duration) {
            startDate.setMonth(startDate.getMonth() + parseInt(this.renewalForm.duration));
            this.renewalForm.Renew_Date = this.formatDateForInput(startDate);
        } else if (!this.renewalForm.Renew_Date) {
            this.renewalForm.Renew_Date = this.renewalForm.start_date;
        }
    },

    formatDateForInput(date) {
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        return `${d}/${m}/${y}`;
    },

    convertToApiDate(dateStr) {
        if (!dateStr) return null;
        const parts = dateStr.split('/');
        if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`; // YYYY-MM-DD
        return null;
    },

    async submitRenewal() {
        // Validation
        if (!this.renewalForm.label) return alert('Label is required');
        if (!this.renewalForm.start_date) return alert('Start Date is required');
        if (!this.renewalForm.Renew_Date) return alert('Renew Date is required');

        this.isSubmittingRenewal = true;
        try {
            const payload = {
                label: this.renewalForm.label,
                start_date: this.convertToApiDate(this.renewalForm.start_date),
                Renew_Date: this.convertToApiDate(this.renewalForm.Renew_Date),
                lead_id: this.renewalForm.lead_id
            };

            const res = await axios.post('/api/v1/renewals', payload);

            this.closeNewRenewalModal();
            if (this.showRenewalTableModal) this.loadRenewals();

        } catch (err) {
            console.error(err);
            alert(err.response?.data?.message || 'Failed to create renewal');
        } finally {
            this.isSubmittingRenewal = false;
        }
    },

    // Date Picker Navigation & Selection
    prevPickerMonth(type) {
        if (type === 'start') this.startDatePickerDate = new Date(this.startDatePickerDate.getFullYear(), this.startDatePickerDate.getMonth() - 1, 1);
        else this.endDatePickerDate = new Date(this.endDatePickerDate.getFullYear(), this.endDatePickerDate.getMonth() - 1, 1);
    },
    nextPickerMonth(type) {
        if (type === 'start') this.startDatePickerDate = new Date(this.startDatePickerDate.getFullYear(), this.startDatePickerDate.getMonth() + 1, 1);
        else this.endDatePickerDate = new Date(this.endDatePickerDate.getFullYear(), this.endDatePickerDate.getMonth() + 1, 1);
    },
    selectPickerDate(date, type) {
        const fmt = this.formatDateForInput(date);
        if (type === 'start') {
            this.renewalForm.start_date = fmt;
            this.showStartDatePicker = false;
            this.calculateEndDate();
        } else {
            this.renewalForm.Renew_Date = fmt;
            this.showEndDatePicker = false;
            this.renewalForm.duration = '';
        }
    },
    generatePickerDays(currentDate, selectedDateStr) {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        let selectedObj = null;
        if (selectedDateStr) {
            const p = selectedDateStr.split('/');
            if (p.length === 3) selectedObj = new Date(p[2], p[1] - 1, p[0]);
        }

        const days = [];
        for (let i = 0; i < startingDayOfWeek; i++) {
            // Previous month days
            const d = new Date(year, month, -i);
            days.unshift({ day: d.getDate(), date: d, isCurrentMonth: false });
        }
        for (let i = 1; i <= daysInMonth; i++) {
            const d = new Date(year, month, i);
            const isSel = selectedObj && d.getTime() === selectedObj.getTime();
            const isToday = d.toDateString() === new Date().toDateString();
            days.push({ day: i, date: d, isCurrentMonth: true, isSelected: isSel, isToday });
        }
        // Fill rest
        const remaining = 42 - days.length;
        for (let i = 1; i <= remaining; i++) {
            const d = new Date(year, month + 1, i);
            days.push({ day: i, date: d, isCurrentMonth: false });
        }
        return days;
    },


    // =================================================================================================
    // RENEWAL TABLE LOGIC
    // =================================================================================================
    openRenewalTableModal() {
        this.showRenewalTableModal = true;
        this.loadRenewals();
        this.loadRenewalStatuses();
    },

    async loadRenewals() {
        this.isLoadingRenewals = true;
        try {
            const res = await axios.get('/api/v1/renewals');
            this.allRenewals = res.data.data;
        } catch (err) {
            console.error(err);
        } finally {
            this.isLoadingRenewals = false;
        }
    },

    async loadRenewalStatuses() {
        try {
            const res = await axios.get('/api/v1/lookups/Renewal Status');
            this.renewalStatuses = res.data.data;
        } catch (err) {
            console.error(err);
        }
    },

    async updateRenewalStatus(renewal) {
        this.updatingRenewalIds.push(renewal.id);
        try {
            const res = await axios.patch(`/api/v1/renewals/${renewal.id}`, { status_id: renewal.status_id });
            // Update local list? 
            if (this.showAuditView) this.loadAudits();
        } catch (err) {
            alert('Failed update');
            this.loadRenewals(); // revert
        } finally {
            this.updatingRenewalIds = this.updatingRenewalIds.filter(id => id !== renewal.id);
        }
    },

    checkIsOverdue(dateStr) {
        // dateStr is usually YYYY-MM-DD from API
        if (!dateStr) return false;
        const d = new Date(dateStr);
        d.setHours(0, 0, 0, 0);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return d < today;
    },

    formatDateDisplay(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }
});

// Expose globally so x-data="filamentCalendar(...)" finds it even if Alpine.data isn't ready or matching
window.filamentCalendar = filamentCalendarFactory;

// Also attempt to register as an Alpine data component
const registerWithAlpine = () => {
    if (typeof window.Alpine !== 'undefined') {
        if (!window.Alpine.data('filamentCalendar')) { // Check if already registered? Alpine doesn't have hasData? 
            // Actually Alpine.data() just registers. Overwriting might warn but it's okay.
            console.log('Filament Calendar: Registering Alpine data');
            window.Alpine.data('filamentCalendar', filamentCalendarFactory);
        }
    } else {
        console.log('Filament Calendar: Alpine not found yet');
    }
};

if (typeof window.Alpine !== 'undefined') {
    registerWithAlpine();
} else {
    document.addEventListener('alpine:init', registerWithAlpine);
}

console.log('Filament Calendar: Script loaded');
window.dispatchEvent(new CustomEvent('filament-calendar-loaded'));
