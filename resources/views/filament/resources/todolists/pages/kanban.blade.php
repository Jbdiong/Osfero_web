
<x-filament-panels::page>
    {{-- 
    @script
    <script>
        const channelName = 'todolists.{{ auth()->user()->tenant_id }}';
        console.log("Listening on private channel: " + channelName);
        
        // Listen to all variations to match Reverb broadcast
        Echo.private(channelName)
            .listen('.TodolistCreated', (e) => { console.log('Event(DOT): TodolistCreated', e); $wire.$refresh(); })
            .listen('TodolistCreated', (e) => { console.log('Event(Simple): TodolistCreated', e); $wire.$refresh(); })
            .listen('App\\Events\\TodolistCreated', (e) => { console.log('Event(Full): TodolistCreated', e); $wire.$refresh(); })
            
            .listen('.TodolistUpdated', (e) => { console.log('Event(DOT): TodolistUpdated', e); $wire.$refresh(); })
            .listen('TodolistUpdated', (e) => { console.log('Event(Simple): TodolistUpdated', e); $wire.$refresh(); })
            .listen('App\\Events\\TodolistUpdated', (e) => { console.log('Event(Full): TodolistUpdated', e); $wire.$refresh(); })

            .listen('.TodolistDeleted', (e) => { console.log('Event(DOT): TodolistDeleted', e); $wire.$refresh(); })
            .listen('TodolistDeleted', (e) => { console.log('Event(Simple): TodolistDeleted', e); $wire.$refresh(); })
            .listen('App\\Events\\TodolistDeleted', (e) => { console.log('Event(Full): TodolistDeleted', e); $wire.$refresh(); });
    </script>
    @endscript
    --}}
    @vite('resources/css/app.css')
    <div 
        class="flex flex-col gap-4 overflow-x-auto h-full pb-4"
        x-data="{ draggingId: null }"
    >
        @if(in_array(auth()->user()?->role?->role, ['Superadmin', 'Tenant admin', 'Manager']))
            <div class="flex justify-end pr-4 mb-2">
                <div class="flex items-center cursor-pointer select-none" wire:click="toggleEveryone" wire:key="everyone-toggle-wrapper">
                    <span class="mr-3 text-sm font-medium text-gray-900 dark:text-gray-300">Show Everyone Todolist</span>
                    <div class="relative w-12 h-5.5 flex items-center rounded-full p-1 transition-colors duration-200 {{ $viewEveryone ? 'bg-primary-600 justify-end' : 'bg-gray-300 dark:bg-gray-700 justify-start' }}">
                        <div class="w-4 h-4 bg-white rounded-full shadow-sm ring-0 transition-all duration-200 ease-in-out"></div>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex gap-4 min-w-full h-full">
            @foreach ($statuses as $status)
                <div 
                    class="kanban-column flex-1 min-w-[300px] bg-gray-100 dark:bg-gray-900 rounded-xl p-4 flex flex-col gap-4 transition-colors duration-200"
                    @dragover.prevent="if (draggingId) { event.dataTransfer.dropEffect = 'move'; }"
                    @drop="
                        if (draggingId) {
                            let draggedEl = document.querySelector(`[data-id='${draggingId}']`);
                            let sourceColumn = draggedEl.closest('.kanban-column');
                            let targetColumn = $el; // $el is the column div here

                            // Only allow drop if coming from DIFFERENT column (status change)
                            if (sourceColumn !== targetColumn) {
                                let container = $el.querySelector('.kanban-cards');
                                container.appendChild(draggedEl);
                                $wire.updateTaskStatus(draggingId, {{ $status->id }});
                                draggingId = null;
                            }
                        }
                    "
                >
                    <!-- Column Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div @class([
                                'w-2 h-2 rounded-full',
                                'bg-blue-500' => $status->name === 'To do',
                                'bg-orange-500' => $status->name === 'In Progress',
                                'bg-pink-500' => $status->name === 'Pending',
                                'bg-green-500' => $status->name === 'Completed',
                                'bg-gray-500' => !in_array($status->name, ['To do', 'In Progress', 'Pending', 'Completed']),
                            ])></div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $status->name }}</h3>
                            <span class="bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs px-2 py-0.5 rounded-full font-medium">
                                {{ $todolists->where('status_id', $status->id)->count() }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            @if($status->name === 'Completed')
                                <a 
                                    href="{{ \App\Filament\Resources\Todolists\TodolistResource::getUrl('archived') }}"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 underline"
                                >
                                    View Archive
                                </a>
                            @endif
                            <x-filament::icon-button icon="heroicon-m-ellipsis-vertical" color="gray" size="sm" />
                        </div>
                    </div>

                    <!-- Add Task Button -->
                    <button wire:click="openCreateTask({{ $status->id }})" class="text-left text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition flex items-center gap-1">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Add task
                    </button>

                    <!-- Cards -->
                    <div class="kanban-cards flex flex-col gap-3 overflow-y-auto min-h-[50px]">
                        @foreach ($todolists->where('status_id', $status->id) as $todo)
                            <div 
                                draggable="true"
                                data-id="{{ $todo->id }}"
                                @dragstart="
                                    draggingId = {{ $todo->id }}; 
                                    event.dataTransfer.effectAllowed = 'move'; 
                                    event.dataTransfer.setData('text/plain', {{ $todo->id }});
                                "
                                @dragend="draggingId = null"
                                wire:key="task-wrapper-{{ $todo->id }}"
                            >
                                <div 
                                    class="cursor-pointer bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition hover:ring-2 hover:ring-blue-500"
                                    x-on:click="$wire.openTask({{ $todo->id }})"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <!-- Drag Handle -->
                                            <div class="drag-handle cursor-grab text-gray-400 hover:text-gray-600">
                                                <x-heroicon-m-bars-2 class="w-4 h-4" />
                                            </div>

                                            <div @class([
                                                'w-2 h-2 rounded-full',
                                                'bg-red-500' => $todo->priority?->name === 'Urgent',
                                                'bg-orange-500' => $todo->priority?->name === 'High',
                                                'bg-blue-500' => $todo->priority?->name === 'Normal',
                                                'bg-green-500' => $todo->priority?->name === 'Low',
                                                'bg-gray-300' => !$todo->priority,
                                            ])></div>
                                            <span @class([
                                                'text-xs',
                                                'text-red-500 font-medium' => $todo->priority?->name === 'Urgent',
                                                'text-orange-500 font-medium' => $todo->priority?->name === 'High',
                                                'text-blue-500 font-medium' => $todo->priority?->name === 'Normal',
                                                'text-green-500 font-medium' => $todo->priority?->name === 'Low',
                                                'text-gray-500 dark:text-gray-400' => !$todo->priority,
                                            ])>{{ $todo->priority?->name ?? 'No Priority' }}</span>
                                        </div>
                                        
                                        @if($status->name === 'Completed')
                                            <span class="text-xs font-medium text-green-500">
                                                Completed
                                            </span>
                                        @elseif($todo->end_date)
                                            @php
                                                $days = (int) now()->startOfDay()->diffInDays($todo->end_date->startOfDay(), false);
                                            @endphp
                                            <span @class([
                                                'text-xs font-normal',
                                                'text-red-600 font-medium' => $days < 0,
                                                'text-orange-500 font-medium' => $days === 0,
                                                'text-green-500' => $days > 0,
                                            ])>
                                                @if($days < 0)
                                                    overdue {{ abs($days) }} {{ Str::plural('day', abs($days)) }}
                                                @elseif($days === 0)
                                                    due today
                                                @else
                                                    due in {{ $days }} {{ Str::plural('day', $days) }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-1">{{ $todo->Title }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $todo->Description }}</p>
                                    
                                    @foreach($todo->children as $child)
                                        <div class="flex items-center gap-2 mt-2 pl-2 border-l-2 border-gray-200 dark:border-gray-700">
                                            <div class="w-1.5 h-1.5 rounded-full bg-gray-400"></div>
                                            <span class="text-xs text-gray-600 dark:text-gray-400">{{ $child->Title }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
