<template>
    <AppLayout>
        <div class="p-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mt-1">
                        {{ totalTodos }} To-do
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" placeholder="Search..." class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <button class="px-3 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-200">Filters</button>
                    <button class="p-2 border border-gray-300 rounded-md hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="grid grid-cols-4 gap-4">
                <!-- To-do Column -->
                <div 
                    class="kanban-column bg-gray-200 rounded-lg p-4 transition-colors duration-200"
                    @dragover.prevent="handleDragOver($event, 'todo')"
                    @drop="handleDrop($event, 'todo')"
                    @dragenter.prevent="handleDragEnter($event, 'todo')"
                    @dragleave="handleDragLeave($event)"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                            <h3 class="font-semibold text-gray-900">To-do</h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded">{{ localTodos.todo.length }}</span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                    </div>
                    <button @click="openNewModal" class="w-full text-left text-sm text-gray-500 hover:text-gray-700 mb-3">+ Add task</button>
                    <!-- Drop indicator -->
                    <div 
                        v-if="dropIndicator.column === 'todo' && dropIndicator.index === 0"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                    <div 
                        v-for="(task, index) in localTodos.todo" 
                        :key="task.id" 
                        :data-task-id="task.id"
                        :data-task-index="index"
                        :draggable="true"
                        @dragstart="handleDragStart($event, task, 'todo', index)"
                        @dragend="handleDragEnd"
                        @dragover="handleTaskDragOver($event, 'todo', index)"
                        @click="viewTodolist(task.id)" 
                        :class="[
                            'bg-white rounded-lg p-4 mb-3 shadow-sm cursor-move hover:shadow-md transition-shadow',
                            isDragging && draggedTask?.id === task.id ? 'opacity-50' : ''
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div :class="['w-2 h-2 rounded-full', task.type_color === 'red' ? 'bg-red-500' : 'bg-blue-500']"></div>
                            <span class="text-xs text-gray-500">{{ task.type }}</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-3">{{ task.company }}</div>
                        <div class="space-y-1">
                            <div v-for="item in task.checklist" :key="item.id" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" :checked="item.checked" class="rounded border-gray-300">
                                <span :class="item.checked ? 'line-through text-gray-400' : 'text-gray-700'">{{ item.text }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Drop indicator after task -->
                    <div 
                        v-if="dropIndicator.column === 'todo' && dropIndicator.index === index + 1"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                </div>

                <!-- In Progress Column -->
                <div 
                    class="kanban-column bg-gray-200 rounded-lg p-4 transition-colors duration-200"
                    @dragover.prevent="handleDragOver($event, 'in_progress')"
                    @drop="handleDrop($event, 'in_progress')"
                    @dragenter.prevent="handleDragEnter($event, 'in_progress')"
                    @dragleave="handleDragLeave($event)"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                            <h3 class="font-semibold text-gray-900">In progress</h3>
                            <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2 py-0.5 rounded">{{ localTodos.in_progress.length }}</span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                    </div>
                    <button @click="openNewModal" class="w-full text-left text-sm text-gray-500 hover:text-gray-700 mb-3">+ Add task</button>
                    <!-- Drop indicator -->
                    <div 
                        v-if="dropIndicator.column === 'in_progress' && dropIndicator.index === 0"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                    <div 
                        v-for="(task, index) in localTodos.in_progress" 
                        :key="task.id" 
                        :data-task-id="task.id"
                        :data-task-index="index"
                        :draggable="true"
                        @dragstart="handleDragStart($event, task, 'in_progress', index)"
                        @dragend="handleDragEnd"
                        @dragover="handleTaskDragOver($event, 'in_progress', index)"
                        @click="viewTodolist(task.id)" 
                        :class="[
                            'bg-white rounded-lg p-4 mb-3 shadow-sm cursor-move hover:shadow-md transition-shadow',
                            isDragging && draggedTask?.id === task.id ? 'opacity-50' : ''
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div :class="['w-2 h-2 rounded-full', task.type_color === 'red' ? 'bg-red-500' : 'bg-blue-500']"></div>
                            <span class="text-xs text-gray-500">{{ task.type }}</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-3">{{ task.company }}</div>
                        <div class="space-y-1">
                            <div v-for="item in task.checklist" :key="item.id" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" :checked="item.checked" class="rounded border-gray-300 text-blue-600">
                                <span :class="item.checked ? 'line-through text-gray-400' : 'text-gray-700'">{{ item.text }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Drop indicator after task -->
                    <div 
                        v-if="dropIndicator.column === 'in_progress' && dropIndicator.index === index + 1"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                </div>

                <!-- Pending Column -->
                <div 
                    class="kanban-column bg-gray-200 rounded-lg p-4 transition-colors duration-200"
                    @dragover.prevent="handleDragOver($event, 'pending')"
                    @drop="handleDrop($event, 'pending')"
                    @dragenter.prevent="handleDragEnter($event, 'pending')"
                    @dragleave="handleDragLeave($event)"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-pink-500"></div>
                            <h3 class="font-semibold text-gray-900">Pending</h3>
                            <span class="bg-pink-100 text-pink-800 text-xs font-medium px-2 py-0.5 rounded">{{ localTodos.pending.length }}</span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                    </div>
                    <button @click="openNewModal" class="w-full text-left text-sm text-gray-500 hover:text-gray-700 mb-3">+ Add task</button>
                    <!-- Drop indicator -->
                    <div 
                        v-if="dropIndicator.column === 'pending' && dropIndicator.index === 0"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                    <div 
                        v-for="(task, index) in localTodos.pending" 
                        :key="task.id" 
                        :data-task-id="task.id"
                        :data-task-index="index"
                        :draggable="true"
                        @dragstart="handleDragStart($event, task, 'pending', index)"
                        @dragend="handleDragEnd"
                        @dragover="handleTaskDragOver($event, 'pending', index)"
                        @click="viewTodolist(task.id)" 
                        :class="[
                            'bg-white rounded-lg p-4 mb-3 shadow-sm cursor-move hover:shadow-md transition-shadow',
                            isDragging && draggedTask?.id === task.id ? 'opacity-50' : ''
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div :class="['w-2 h-2 rounded-full', task.type_color === 'green' ? 'bg-green-500' : 'bg-blue-500']"></div>
                            <span class="text-xs text-gray-500">{{ task.type }}</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900 mb-3">{{ task.company }}</div>
                        <div class="space-y-1">
                            <div v-for="item in task.checklist" :key="item.id" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" :checked="item.checked" class="rounded border-gray-300 text-blue-600">
                                <span :class="item.checked ? 'line-through text-gray-400' : 'text-gray-700'">{{ item.text }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Drop indicator after task -->
                    <div 
                        v-if="dropIndicator.column === 'pending' && dropIndicator.index === index + 1"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                </div>

                <!-- Completed Column -->
                <div 
                    class="kanban-column bg-gray-200 rounded-lg p-4 transition-colors duration-200"
                    @dragover.prevent="handleDragOver($event, 'completed')"
                    @drop="handleDrop($event, 'completed')"
                    @dragenter.prevent="handleDragEnter($event, 'completed')"
                    @dragleave="handleDragLeave($event)"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <h3 class="font-semibold text-gray-900">Completed</h3>
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded">{{ localTodos.completed.length }}</span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                            </svg>
                        </button>
                    </div>
                    <button @click="openNewModal" class="w-full text-left text-sm text-gray-500 hover:text-gray-700 mb-3">+ Add task</button>
                    <!-- Drop indicator -->
                    <div 
                        v-if="dropIndicator.column === 'completed' && dropIndicator.index === 0"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                    <div 
                        v-for="(task, index) in localTodos.completed" 
                        :key="task.id" 
                        :data-task-id="task.id"
                        :data-task-index="index"
                        :draggable="true"
                        @dragstart="handleDragStart($event, task, 'completed', index)"
                        @dragend="handleDragEnd"
                        @dragover="handleTaskDragOver($event, 'completed', index)"
                        @click="viewTodolist(task.id)" 
                        :class="[
                            'bg-white rounded-lg p-4 mb-3 shadow-sm cursor-move hover:shadow-md transition-shadow',
                            isDragging && draggedTask?.id === task.id ? 'opacity-50' : ''
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div :class="['w-2 h-2 rounded-full', task.type_color === 'green' ? 'bg-green-500' : 'bg-blue-500']"></div>
                            <span class="text-xs text-gray-500">{{ task.type }}</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ task.company }}</div>
                    </div>
                    <!-- Drop indicator after task -->
                    <div 
                        v-if="dropIndicator.column === 'completed' && dropIndicator.index === index + 1"
                        class="h-1 bg-blue-500 rounded-full mb-3 transition-all duration-200"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <NewTodolistModal
            :isOpen="showNewModal"
            :todolist="null"
            @close="closeNewModal"
            @saved="handleTodolistSaved"
        />

        <TodolistOverviewModal
            :isOpen="showOverviewModal"
            :todolistId="selectedTodolistId"
            @close="closeOverviewModal"
            @saved="handleTodolistSaved"
            @subtask-created="handleTodolistSaved"
            @view-child="viewTodolist"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import axios from 'axios';
import AppLayout from '@/Pages/Layouts/AppLayout.vue';
import NewTodolistModal from '@/Components/NewTodolistModal.vue';
import TodolistOverviewModal from '@/Components/TodolistOverviewModal.vue';

const props = defineProps({
    todos: Object,
    statusMap: Object,
});

// Create reactive todos that can be updated during drag and drop
const localTodos = reactive({
    todo: [...(props.todos?.todo || [])],
    in_progress: [...(props.todos?.in_progress || [])],
    pending: [...(props.todos?.pending || [])],
    completed: [...(props.todos?.completed || [])],
});

// Drag and drop state
const draggedTask = ref(null);
const draggedFromColumn = ref(null);
const isDragging = ref(false);
const dropIndicator = ref({ column: null, index: null });

const totalTodos = computed(() => {
    return (localTodos.todo?.length || 0) + 
           (localTodos.in_progress?.length || 0) + 
           (localTodos.pending?.length || 0) + 
           (localTodos.completed?.length || 0);
});

// Map column names to status names
const columnToStatusMap = {
    'todo': 'To do',
    'in_progress': 'In Progress',
    'pending': 'Pending',
    'completed': 'Done'
};

// Get status ID for a column
const getStatusId = (columnName) => {
    const statusName = columnToStatusMap[columnName];
    return props.statusMap?.[statusName] || null;
};

const showNewModal = ref(false);
const showOverviewModal = ref(false);
const selectedTodolistId = ref(null);

const openNewModal = () => {
    showNewModal.value = true;
};

const closeNewModal = () => {
    showNewModal.value = false;
};

const viewTodolist = (id) => {
    selectedTodolistId.value = id;
    showOverviewModal.value = true;
};

const closeOverviewModal = () => {
    showOverviewModal.value = false;
    selectedTodolistId.value = null;
};

// Edit is now handled within TodolistOverviewModal

const handleTodolistSaved = () => {
    // Reload the page or refresh the todos data
    window.location.reload();
};

// Drag and drop handlers
const handleDragStart = (event, task, fromColumn, index) => {
    draggedTask.value = task;
    draggedFromColumn.value = fromColumn;
    isDragging.value = true;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', task.id);
    // Add visual feedback
    event.target.style.opacity = '0.5';
};

const handleDragEnd = (event) => {
    isDragging.value = false;
    event.target.style.opacity = '1';
    
    // Remove drag-over class from all columns
    document.querySelectorAll('.kanban-column').forEach(col => {
        col.classList.remove('drag-over');
    });
    
    // Clear drop indicator
    dropIndicator.value = { column: null, index: null };
    
    draggedTask.value = null;
    draggedFromColumn.value = null;
};

const handleDragEnter = (event, toColumn) => {
    event.preventDefault();
    const column = event.currentTarget;
    column.classList.add('drag-over');
    
    // Initialize drop indicator when entering the same column
    if (draggedFromColumn.value === toColumn && draggedTask.value) {
        const tasks = localTodos[toColumn];
        const oldIndex = tasks.findIndex(t => t.id === draggedTask.value.id);
        // Set initial indicator position
        if (oldIndex !== -1 && (dropIndicator.value.column !== toColumn || dropIndicator.value.index === null)) {
            dropIndicator.value = { column: toColumn, index: oldIndex };
        }
    }
};

const handleDragLeave = (event) => {
    // Only remove drag-over if we're actually leaving the column (not just moving to a child element)
    const column = event.currentTarget;
    const rect = column.getBoundingClientRect();
    const x = event.clientX;
    const y = event.clientY;
    
    // Check if we're still within the column bounds
    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        column.classList.remove('drag-over');
        // Clear drop indicator when leaving column
        dropIndicator.value = { column: null, index: null };
    }
};

const handleTaskDragOver = (event, column, index) => {
    // Only show indicator if dragging within the same column
    if (draggedFromColumn.value === column && draggedTask.value) {
        event.preventDefault();
        event.stopPropagation();
        
        const taskElement = event.currentTarget;
        const rect = taskElement.getBoundingClientRect();
        const y = event.clientY;
        
        // Get the current task's index in the array
        const currentTaskId = parseInt(taskElement.getAttribute('data-task-id'));
        const tasks = localTodos[column];
        const currentTaskIndex = tasks.findIndex(t => t.id === currentTaskId);
        
        if (currentTaskIndex === -1) return;
        
        // Determine if we should drop before or after this task
        const midPoint = rect.top + rect.height / 2;
        let dropIndex;
        
        if (y < midPoint) {
            // Drop before this task
            dropIndex = currentTaskIndex;
        } else {
            // Drop after this task
            dropIndex = currentTaskIndex + 1;
        }
        
        // Don't show indicator if dropping at the same position
        const draggedTaskIndex = tasks.findIndex(t => t.id === draggedTask.value.id);
        if (draggedTaskIndex !== -1) {
            // If moving down, adjust the drop index
            if (draggedTaskIndex < dropIndex) {
                dropIndex -= 1;
            }
            // Don't show indicator if it's the same position
            if (dropIndex === draggedTaskIndex) {
                dropIndicator.value = { column: null, index: null };
                return;
            }
        }
        
        // Only update if the position actually changed to prevent flickering
        if (dropIndicator.value.column !== column || dropIndicator.value.index !== dropIndex) {
            dropIndicator.value = { column, index: dropIndex };
        }
    }
};

const handleDragOver = (event, toColumn) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    
    // Only handle column-level drag over if not already handled by task drag over
    // This is mainly for empty columns or when dragging near the bottom
    if (draggedFromColumn.value === toColumn && draggedTask.value) {
        const column = event.currentTarget;
        const tasks = localTodos[toColumn];
        const columnRect = column.getBoundingClientRect();
        const y = event.clientY;
        
        // If column is empty, show indicator at position 0
        if (tasks.length === 0) {
            if (dropIndicator.value.column !== toColumn || dropIndicator.value.index !== 0) {
                dropIndicator.value = { column: toColumn, index: 0 };
            }
            return;
        }
        
        // Check if we're near the bottom of the column (below all tasks)
        const bottomThreshold = columnRect.bottom - 100; // 100px from bottom
        
        if (y > bottomThreshold) {
            const draggedTaskIndex = tasks.findIndex(t => t.id === draggedTask.value.id);
            let lastIndex = tasks.length;
            
            // Adjust if moving down
            if (draggedTaskIndex !== -1 && draggedTaskIndex < lastIndex) {
                lastIndex -= 1;
            }
            
            // Don't show if same position
            if (draggedTaskIndex === lastIndex) {
                dropIndicator.value = { column: null, index: null };
            } else if (dropIndicator.value.column !== toColumn || dropIndicator.value.index !== lastIndex) {
                dropIndicator.value = { column: toColumn, index: lastIndex };
            }
        }
    }
};

const handleDrop = async (event, toColumn) => {
    event.preventDefault();
    event.currentTarget.classList.remove('drag-over');
    
    if (!draggedTask.value || !draggedFromColumn.value) {
        dropIndicator.value = { column: null, index: null };
        return;
    }

    const fromColumn = draggedFromColumn.value;
    const task = draggedTask.value;

    // If dropped in the same column, just reorder
    if (fromColumn === toColumn) {
        // Use the drop indicator index if available, otherwise calculate
        const tasks = localTodos[toColumn];
        let dropIndex = dropIndicator.value.index;
        
        // If no indicator index, calculate from event
        if (dropIndex === null) {
            dropIndex = getDropIndex(event, tasks);
        }
        
        // Remove from old position
        const oldIndex = tasks.findIndex(t => t.id === task.id);
        if (oldIndex === -1) {
            dropIndicator.value = { column: null, index: null };
            return;
        }
        
        // Adjust drop index if dropping after the current position
        if (oldIndex < dropIndex) {
            dropIndex -= 1;
        }
        
        tasks.splice(oldIndex, 1);
        // Insert at new position
        tasks.splice(dropIndex, 0, task);
        
        // Update positions
        await updatePositions(toColumn);
    } else {
        // Moving between columns
        const fromTasks = localTodos[fromColumn];
        const toTasks = localTodos[toColumn];
        
        // Remove from source column
        const oldIndex = fromTasks.findIndex(t => t.id === task.id);
        if (oldIndex === -1) return;
        
        fromTasks.splice(oldIndex, 1);
        
        // Find drop position in target column
        const dropIndex = getDropIndex(event, toTasks);
        
        // Insert in target column
        toTasks.splice(dropIndex, 0, task);
        
        // Update positions for both columns
        await updatePositions(fromColumn);
        await updatePositions(toColumn, task.id, getStatusId(toColumn));
    }
    
    // Reset drag state
    dropIndicator.value = { column: null, index: null };
    draggedTask.value = null;
    draggedFromColumn.value = null;
    isDragging.value = false;
};

const getDropIndex = (event, tasks) => {
    // Get all task elements in the column (excluding the dragged one)
    const column = event.currentTarget;
    const taskElements = Array.from(column.querySelectorAll('[draggable="true"]'))
        .filter(el => {
            const taskId = parseInt(el.getAttribute('data-task-id'));
            return taskId !== draggedTask.value?.id;
        });
    
    // Find which task we're dropping over
    let dropIndex = tasks.length;
    
    for (let i = 0; i < taskElements.length; i++) {
        const rect = taskElements[i].getBoundingClientRect();
        const y = event.clientY;
        
        // If mouse is in the upper half of a task, drop before it
        if (y < rect.top + rect.height / 2) {
            const taskId = parseInt(taskElements[i].getAttribute('data-task-id'));
            if (taskId) {
                const index = tasks.findIndex(t => t.id === taskId);
                if (index !== -1) {
                    dropIndex = index;
                    break;
                }
            }
        }
    }
    
    return dropIndex;
};

const updatePositions = async (columnName, movedTaskId = null, newStatusId = null) => {
    const tasks = localTodos[columnName];
    const updates = [];
    
    tasks.forEach((task, index) => {
        const update = {
            id: task.id,
            position: index,
        };
        
        // If this is the moved task and we're changing status, update status_id
        if (movedTaskId && task.id === movedTaskId && newStatusId) {
            update.status_id = newStatusId;
        }
        
        updates.push(update);
    });
    
    if (updates.length > 0) {
        try {
            await axios.post('/api/v1/todolists/update-positions', {
                updates: updates
            });
        } catch (error) {
            console.error('Error updating positions:', error);
            // Revert on error
            window.location.reload();
        }
    }
};
</script>

<style scoped>
.kanban-column.drag-over {
    background-color: rgba(59, 130, 246, 0.15);
    border: 2px solid #3b82f6;
    transform: scale(1.01);
}
</style>


