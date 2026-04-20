<x-filament-panels::page>
    <div class="max-w-xl mx-auto">
        <x-filament-panels::form wire:submit="join">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getFormActions()"
                :full-width="true"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>


