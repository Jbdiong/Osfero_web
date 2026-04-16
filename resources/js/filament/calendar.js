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
    initialDate = null,
    upcomingDeadline = null,
    overdueRenewals = []
}) => ({
    // --- Main Calendar State ---
    calendar: null,
    fullCalendarEvents: [],
    currentView: 'dayGridMonth',
    selectedDate: null,
    currentDate: null, // For mini calendar navigation

    // --- Sidebar Data ---
    upcomingDeadline: upcomingDeadline || { title: null, more: null, countdown: null },
    sidebarRenewals: overdueRenewals || [],

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
    eventForm: {
        title: '',
        type: 'event', // event or task
        start: null,
        end: null,
        allDay: true
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

        this.processEvents(events);

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
            const colorMap = { 'purple': '#9333ea', 'blue': '#3b82f6', 'pink': '#ec4899', 'green': '#10b981' };

            // Start and End are usually ISO strings from Laravel (YYYY-MM-DDTHH:mm:ss)
            // We can pass them directly to FullCalendar, or normalize them if needed.
            return {
                id: event.id.toString(),
                title: event.title,
                start: event.start, // Pass strictly as received (ISO string)
                end: event.end,     // Pass strictly as received (ISO string)
                backgroundColor: '#ffd7b5', // Forced to requested orange
                borderColor: '#ff6700', // Forced to requested border
                textColor: '#000000',
                extendedProps: { ...event }
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
            nowIndicator: true,
            selectable: true,
            selectMirror: true,
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
                info.jsEvent.preventDefault(); // Prevents navigating to the Google URL
            },
            eventMouseEnter: (info) => {
                const ev = info.event;
                const isGoogleHoliday = ev.source && ev.source.id === 'google-holidays';

                this.hoverCardData = {
                    title: ev.title,
                    calendarName: isGoogleHoliday ? 'Holidays in Malaysia' : (ev.extendedProps.calendarName || 'Primary Calendar'),
                    dateStr: ev.start.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }),
                    creator: isGoogleHoliday ? 'Holidays in Malaysia' : (ev.extendedProps.creator || 'User Setup')
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
                    id: 'google-holidays',
                    googleCalendarId: 'en.malaysia#holiday@group.v.calendar.google.com',
                    color: '#C6FFCA',
                    textColor: '#000000',
                    borderColor: '#008002'
                }
            ],
            eventContent: (arg) => {
                const isGoogleHoliday = arg.event.source && arg.event.source.id === 'google-holidays';

                if (isGoogleHoliday) {
                    return {
                        html: `<div class=" flex items-center gap-1 overflow-hidden">
                            <svg class="w-2 h-2 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                            <div class="text-xs font-semibold text-gray-700 capitalize truncate flex-1 min-w-0" title="${arg.event.title}">${arg.event.title}</div>
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
        }
    },


    // =================================================================================================
    // NEW EVENT/TASK MODAL LOGIC (Google Style)
    // =================================================================================================

    openEventModal(start, end, allDay, jsEvent = null) {
        if (!start) {
            start = new Date();
            // Default 1 hour from now without seconds
            start.setMinutes(0, 0, 0);
            end = new Date(start.getTime() + 60 * 60000);
            allDay = false;
        }

        this.eventForm = {
            title: '',
            type: 'event',
            start: start,
            end: end,
            allDay: allDay
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
        if (this.calendar) {
            this.calendar.unselect();
        }
    },

    saveEvent() {
        // Here you would connect to Laravel/Livewire to save the draft event/task
        this.closeEventModal();
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
