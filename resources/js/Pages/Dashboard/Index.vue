<template>
    <AppLayout>
        <div class="p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard</h1>


            <!-- Upcoming Deadline and Overdue Renewals -->
            <div class="grid grid-cols-2 gap-4">
                <UpcomingDeadline
                    :title="calendar?.upcoming_deadline_title"
                    :more="calendar?.upcoming_deadline_more"
                    :countdown="calendar?.upcoming_deadline_countdown"
                    @view-task="handleViewTask"
                />

                <OverdueRenewals
                    :renewals="calendar?.overdue_renewals || []"
                    @new-renewal="showRenewalModal = true"
                    @view-all="showRenewalTableModal = true"
                />
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
import UpcomingDeadline from '@/Components/UpcomingDeadline.vue';
import OverdueRenewals from '@/Components/OverdueRenewals.vue';
import NewRenewalModal from '@/Components/NewRenewalModal.vue';
import RenewalTableModal from '@/Components/RenewalTableModal.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    calendar: {
        type: Object,
        default: null
    }
});

// Modal state
const showRenewalModal = ref(false);
const showRenewalTableModal = ref(false);

// Handle view task from upcoming deadline
function handleViewTask() {
    // TODO: Implement view task functionality
    console.log('View task clicked');
}

// Handle renewal created
function handleRenewalCreated() {
    showRenewalModal.value = false;
    // Refresh the page data if needed
    router.reload({ only: ['calendar'] });
}

// Handle renewal updated
function handleRenewalUpdated() {
    // Refresh the page data if needed
    router.reload({ only: ['calendar'] });
}
</script>




