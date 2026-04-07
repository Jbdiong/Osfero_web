<template>
    <AppLayout>
        <div class="p-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Renewals</h1>
                <button 
                    @click="showNewRenewalForm = !showNewRenewalForm"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ showNewRenewalForm ? 'Cancel' : '+ New Renewal' }}
                </button>
            </div>

            <!-- New Renewal Modal -->
            <NewRenewalModal 
                :isOpen="showNewRenewalForm"
                @close="showNewRenewalForm = false"
                @created="handleRenewalCreated"
            />

            <!-- Renewals Table (using component inline) -->
            <RenewalTableModal 
                :key="tableKey"
                :isOpen="true"
                :inline="true"
                @close="() => {}"
                @updated="handleRenewalUpdated"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Pages/Layouts/AppLayout.vue';
import NewRenewalModal from '@/Components/NewRenewalModal.vue';
import RenewalTableModal from '@/Components/RenewalTableModal.vue';
import { ref } from 'vue';

const showNewRenewalForm = ref(false);
const tableKey = ref(0);

const handleRenewalCreated = () => {
    showNewRenewalForm.value = false;
    // Force refresh of the renewals table by triggering a reload
    // We'll use a key to force re-render
    tableKey.value++;
};

const handleRenewalUpdated = () => {
    // The RenewalTableModal handles its own updates
};
</script>
