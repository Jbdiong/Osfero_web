<div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }" class="py-2">
    <div class="flex items-center">
        <button 
            type="button"
            x-on:click="state = !state"
            x-bind:aria-checked="state?.toString()"
            class="fi-fo-toggle relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent outline-none transition-colors duration-200 ease-in-out focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-1"
            :class="state ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"
            x-bind:style="state ? '--c-600:var(--primary-600)' : '--c-600:var(--gray-600)'"
            role="switch"
        >
            <span 
                class="pointer-events-none absolute  inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-all duration-200 ease-in-out"
                :style="state ? 'left: calc(100% - 1.25rem)' : 'left: 0rem'"
            ></span>
        </button>
        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">Global Setting?</span>
    </div>
</div>


