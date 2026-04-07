<template>
    <div class="flex min-h-screen bg-[#F8FAFF]">
        <!-- Sidebar -->
        <aside class="fixed left-0 top-0 h-screen w-[10vw] bg-white border-r border-gray-200 flex flex-col z-10">
            <!-- Brand -->
            <div class="p-6 pb-4">
                <h1 class="text-2xl font-bold text-gray-900">Taiyō</h1>
                <div class="mt-4 border-t border-gray-200"></div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 overflow-y-auto">
                <!-- Main Menu Section -->
                <div class="mb-8">
                    <h2 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-4">Main Menu</h2>
                    <ul class="space-y-1">
                        <li>
                            <Link :href="route('dashboard')" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors', isActive('/') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M3 7.4V3.6C3 3.26863 3.26863 3 3.6 3H9.4C9.73137 3 10 3.26863 10 3.6V7.4C10 7.73137 9.73137 8 9.4 8H3.6C3.26863 8 3 7.73137 3 7.4Z" stroke="currentColor" stroke-width="1.5"></path>
                                    <path d="M14 20.4V16.6C14 16.2686 14.2686 16 14.6 16H20.4C20.7314 16 21 16.2686 21 16.6V20.4C21 20.7314 20.7314 21 20.4 21H14.6C14.2686 21 14 20.7314 14 20.4Z" stroke="currentColor" stroke-width="1.5"></path>
                                    <path d="M14 12.4V3.6C14 3.26863 14.2686 3 14.6 3H20.4C20.7314 3 21 3.26863 21 3.6V12.4C21 12.7314 20.7314 13 20.4 13H14.6C14.2686 13 14 12.7314 14 12.4Z" stroke="currentColor" stroke-width="1.5"></path>
                                    <path d="M3 20.4V11.6C3 11.2686 3.26863 11 3.6 11H9.4C9.73137 11 10 11.2686 10 11.6V20.4C10 20.7314 9.73137 21 9.4 21H3.6C3.26863 21 3 20.7314 3 20.4Z" stroke="currentColor" stroke-width="1.5"></path>
                                </svg>
                                <span>Dashboard</span>
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('leads.index')" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors', isActive('/leads') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M1 20V19C1 15.134 4.13401 12 8 12V12C11.866 12 15 15.134 15 19V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                    <path d="M13 14V14C13 11.2386 15.2386 9 18 9V9C20.7614 9 23 11.2386 23 14V14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                    <path d="M8 12C10.2091 12 12 10.2091 12 8C12 5.79086 10.2091 4 8 4C5.79086 4 4 5.79086 4 8C4 10.2091 5.79086 12 8 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M18 9C19.6569 9 21 7.65685 21 6C21 4.34315 19.6569 3 18 3C16.3431 3 15 4.34315 15 6C15 7.65685 16.3431 9 18 9Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                Leads
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('tracking.index')" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors', isActive('/tracking') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M20 10C20 14.4183 12 22 12 22C12 22 4 14.4183 4 10C4 5.58172 7.58172 2 12 2C16.4183 2 20 5.58172 20 10Z" stroke="currentColor" stroke-width="1.5"></path>
                                    <path d="M12 11C12.5523 11 13 10.5523 13 10C13 9.44772 12.5523 9 12 9C11.4477 9 11 9.44772 11 10C11 10.5523 11.4477 11 12 11Z" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                Tracking
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('todo.index')" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors', isActive('/todo') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M8 6L20 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M4 6.01L4.01 5.99889" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M4 12.01L4.01 11.9989" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M4 18.01L4.01 17.9989" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M8 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M8 18L20 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                To-do list
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('renewals.index')" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors relative', isActive('/renewals') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" viewBox="0 0 24 24" stroke-width="1.5" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M13 21H5C3.89543 21 3 20.1046 3 19V10H21V15M15 4V2M15 4V6M15 4H10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M3 10V6C3 4.89543 3.89543 4 5 4H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M7 2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M21 10V6C21 4.89543 20.1046 4 19 4H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M16 20L18 22L22 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <span>Renewals</span>
                                <span 
                                    v-if="overdueRenewalsCount > 0"
                                    class="ml-auto bg-red-500 text-white text-xs font-semibold w-5 h-5 rounded-full flex items-center justify-center text-center"
                                >
                                    {{ overdueRenewalsCount }}
                                </span>
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('calendar.index')" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors', isActive('/calendar') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M15 4V2M15 4V6M15 4H10.5M3 10V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V10H3Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M3 10V6C3 4.89543 3.89543 4 5 4H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M7 2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M21 10V6C21 4.89543 20.1046 4 19 4H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                Calendar
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Others Section -->
                <div>
                    <h2 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-4">Others</h2>
                    <ul class="space-y-1">
                        <li>
                            <Link href="/settings" :class="['block px-4 py-2 text-sm rounded-md flex items-center gap-2 transition-colors', isActive('/settings') ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-100']">
                                <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                                    <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M19.6224 10.3954L18.5247 7.7448L20 6L18 4L16.2647 5.48295L13.5578 4.36974L12.9353 2H10.981L10.3491 4.40113L7.70441 5.51596L6 4L4 6L5.45337 7.78885L4.3725 10.4463L2 11V13L4.40111 13.6555L5.51575 16.2997L4 18L6 20L7.79116 18.5403L10.397 19.6123L11 22H13L13.6045 19.6132L16.2551 18.5155C16.6969 18.8313 18 20 18 20L20 18L18.5159 16.2494L19.6139 13.598L21.9999 12.9772L22 11L19.6224 10.3954Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                Settings
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Logout Button -->
            <div class="px-4 py-4 border-t border-gray-200">
                <form :action="route('logout')" method="POST" class="w-full">
                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                    <button type="submit" class="w-full block px-4 py-2 text-sm rounded-md flex items-center gap-2 text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg width="24px" height="24px" stroke-width="1.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="#000000">
                            <path d="M9 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M21 12H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-[10vw] bg-[#F8FAFF]">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

// Get overdue renewals count from shared props
const overdueRenewalsCount = computed(() => {
    return page.props.overdue_renewals_count || 0;
});

// Helper function to check if a route is active
const isActive = (path) => {
    const currentUrl = page.url;
    if (path === '/') {
        return currentUrl === '/' || currentUrl.startsWith('/dashboard');
    }
    return currentUrl.startsWith(path);
};
</script>
