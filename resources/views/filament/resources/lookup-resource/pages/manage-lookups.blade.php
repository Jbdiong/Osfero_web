<x-filament-panels::page>
    @vite('resources/css/app.css')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start max-w-7xl mx-auto w-full">

        <!-- Column 1: Groups -->
        <div class="space-y-4 min-w-0 flex-1">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest px-1">Groups</h3>
            <div class="space-y-2">
                @foreach($this->getGroups() as $group)
                    <div 
                        wire:click="selectGroup({{ $group->id }})"
                        x-data="{ hovered: false }"
                        x-on:mouseenter="hovered = true"
                        x-on:mouseleave="hovered = false"
                        @class([
                            'flex items-center px-4 py-3 cursor-pointer rounded-lg border transition-all duration-200 shadow-sm',
                            'text-white ring-2 ring-primary-600 ring-offset-2 dark:ring-offset-gray-900' => $selectedGroupId == $group->id,
                            'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200' => $selectedGroupId != $group->id,
                        ])
                        style="{{ $selectedGroupId == $group->id ? 'background-color: rgb(var(--primary-600)); border-color: rgb(var(--primary-600));' : '' }}"
                    >
                        <span class="flex-1 font-semibold tracking-tight truncate">{{ $group->name }}</span>
                        @if($selectedGroupId == $group->id)
                            <x-heroicon-m-chevron-right class="w-4 h-4 text-white" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Column 2: Categories (sub-groups / parents) -->
        <div class="space-y-4 min-w-0 flex-1">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest px-1">Settings Categories</h3>
            <div class="space-y-2">
                @if($selectedGroupId)
                    @foreach($this->getParents() as $parent)
                        <div 
                            wire:click="selectParent({{ $parent->id }})"
                            x-data="{ hovered: false }"
                            x-on:mouseenter="hovered = true"
                            x-on:mouseleave="hovered = false"
                            @class([
                                'flex items-center px-4 py-3 cursor-pointer rounded-lg border transition-all duration-200 shadow-sm',
                                'text-white ring-2 ring-primary-600 ring-offset-2 dark:ring-offset-gray-900' => $selectedParentId == $parent->id,
                                'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-200' => $selectedParentId != $parent->id,
                            ])
                            style="{{ $selectedParentId == $parent->id ? 'background-color: rgb(var(--primary-600)); border-color: rgb(var(--primary-600));' : '' }}"
                        >
                            <!-- Drag Handle -->
                            <div class="mr-3 transition-opacity" :style="hovered ? 'opacity:0.5' : 'opacity:0.2'">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 7h2v2H7V7zm0 4h2v2H7v-2zm4-4h2v2h-2V7zm0 4h2v2h-2v-2zM7 15h2v2H7v-2zm4 0h2v2h-2v-2z"></path>
                                </svg>
                            </div>
                            <span class="flex-1 font-semibold tracking-tight truncate">{{ $parent->name }}</span>
                            <div class="flex items-center gap-1">
                                @if($parent->tenant_id !== null || optional(auth()->user()->role)->role === 'Superadmin')
                                    <button 
                                        x-show="hovered"
                                        x-cloak
                                        wire:click.stop="mountAction('deleteLookup', { id: {{ $parent->id }} })"
                                        class="p-1.5 rounded-md transition-colors"
                                        title="Delete"
                                    >
                                        <x-heroicon-m-trash @class([
                                            'w-4 h-4',
                                            'text-white' => $selectedParentId == $parent->id,
                                            'text-gray-400' => $selectedParentId != $parent->id,
                                        ]) />
                                    </button>
                                @endif
                                @if($selectedParentId == $parent->id)
                                    <x-heroicon-m-chevron-right class="w-4 h-4 text-white" />
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if(optional(auth()->user()->role)->role === 'Superadmin')
                        <div class="pt-2 px-1">
                            {{ $this->addParentAction }}
                        </div>
                    @endif
                @else
                    <div class="px-4 py-10 text-center text-sm text-gray-400 italic">
                        Select a group to see categories.
                    </div>
                @endif
            </div>
        </div>

        <!-- Column 3: System Settings (sub-categories) -->
        <div class="space-y-4 min-w-0 flex-1">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest px-1">System Settings</h3>
            @if($selectedParentId)
                <div class="space-y-2">
                    @forelse($this->getChildren() as $child)
                        <div 
                            x-data="{ hovered: false }"
                            x-on:mouseenter="hovered = true"
                            x-on:mouseleave="hovered = false"
                            class="flex items-center px-4 py-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg transition-all duration-200 shadow-sm"
                        >
                            <!-- Drag Handle -->
                            <div class="mr-3 transition-opacity" :style="hovered ? 'opacity:0.5' : 'opacity:0.2'">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 7h2v2H7V7zm0 4h2v2H7v-2zm4-4h2v2h-2V7zm0 4h2v2h-2v-2zM7 15h2v2H7v-2zm4 0h2v2h-2v-2z"></path>
                                </svg>
                            </div>

                            <span class="flex-1 text-gray-700 dark:text-gray-200 font-medium truncate">{{ $child->name }}</span>
                            
                            @if($child->tenant_id === null && optional(auth()->user()->role)->role === 'Superadmin')
                                <span class="mr-2 text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-400 font-medium">Global</span>
                            @endif

                            <!-- Edit & Delete Actions -->
                            <div class="flex items-center gap-1" :style="hovered ? 'opacity:1' : 'opacity:0'" style="opacity:0;transition:opacity 0.15s ease;">
                                <button 
                                    wire:click.stop="mountAction('editLookup', { id: {{ $child->id }} })"
                                    class="p-1.5 rounded-md text-gray-400 transition-colors"
                                    style="--c: rgb(var(--primary-600));"
                                    onmouseover="this.style.color=this.style.getPropertyValue('--c')"
                                    onmouseout="this.style.color=''"
                                    title="Edit"
                                >
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </button>
                                <button 
                                    wire:click.stop="mountAction('deleteLookup', { id: {{ $child->id }} })"
                                    class="p-1.5 rounded-md text-gray-400 hover:text-danger-600 dark:hover:text-danger-500 transition-colors"
                                    title="Delete"
                                >
                                    <x-heroicon-m-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                            <x-heroicon-o-squares-2x2 class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                            <p class="text-sm text-gray-500">No sub-categories yet.</p>
                        </div>
                    @endforelse

                    <div class="pt-2 px-1">
                        {{ $this->addSubCategoryAction }}
                    </div>
                </div>
            @else
                <div class="px-6 py-16 text-center bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                    <x-heroicon-o-cursor-arrow-rays class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm text-gray-500 italic">Select a category on the left to manage its settings.</p>
                </div>
            @endif
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
