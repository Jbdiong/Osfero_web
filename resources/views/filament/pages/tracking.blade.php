
<x-filament-panels::page>
    @vite(['resources/css/app.css'])
    <div class="flex flex-row gap-4 overflow-x-auto pb-4 items-start" style="height: calc(100vh - 12rem);">
        @foreach($columns as $userId => $data)
            <div class="flex-shrink-0 flex flex-col h-full bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-2 w-40 md:w-50" >
                
                <!-- Column Header -->
                <div class="flex items-center justify-between mb-3 px-2">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        <h3 class="font-medium text-sm text-gray-700 dark:text-gray-200">
                            {{ $data['user']->name }}
                        </h3>
                        <span class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs px-2 py-0.5 rounded-full">
                            @if($activeTab === 'tasks')
                                {{ count($data['todolists']) }}
                            @else
                                {{ count($data['customers']) }}
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="flex-1 overflow-y-auto space-y-3 px-1 custom-scrollbar">
                    
                    <!-- Tasks Section -->
                    @if($activeTab === 'tasks')
                    @if(count($data['todolists']) > 0)
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-3 px-1">Tasks</div>
                        @foreach($data['todolists'] as $todo)
                            <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer hover:ring-2 hover:ring-blue-500 transition">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm line-clamp-2">
                                        {{ $todo->Title }}
                                    </h4>
                                </div>
                                <p class="text-xs text-gray-500 line-clamp-2">{{ $todo->Description }}</p>
                                
                                <div class="flex items-center gap-2 mt-3">
                                    @if($todo->priority)
                                        <span class="flex items-center gap-1 text-[10px] 
                                            @if($todo->priority->name === 'High') text-red-600
                                            @elseif($todo->priority->name === 'Medium') text-amber-600
                                            @else text-gray-500 @endif">
                                            @if($todo->priority->name === 'High') 🔥
                                            @elseif($todo->priority->name === 'Medium') ⚡
                                            @else 🏳️ @endif
                                            {{ $todo->priority->name }}
                                        </span>
                                    @endif
                                    
                                    @if($todo->end_date)
                                         <span class="flex items-center gap-1 text-[10px] text-gray-500">
                                            📅 {{ $todo->end_date->format('M j') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif

                    @endif

                    <!-- Customers Section -->
                    @if($activeTab === 'customers')
                        @if(count($data['customers']) > 0)
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-1 px-1">Customers</div>
                            @foreach($data['customers'] as $customer)
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer hover:ring-2 hover:ring-blue-500 transition">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">
                                        {{ $customer->name }}
                                    </h4>
                                    @if($customer->company)
                                        <p class="text-xs text-gray-500 line-clamp-1 mb-1">{{ $customer->company }}</p>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    @endif

                    @if(($activeTab === 'tasks' && count($data['todolists']) == 0) || ($activeTab === 'customers' && count($data['customers']) == 0))
                        <div class="text-center py-8 text-gray-400 text-sm">
                            No items
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>


