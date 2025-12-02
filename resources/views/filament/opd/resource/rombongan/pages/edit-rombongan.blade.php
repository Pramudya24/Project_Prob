<x-filament-panels::page>
    {{-- Form Edit Rombongan --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            @foreach ($this->getCachedFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    {{-- Tabs untuk Data dalam Rombongan & Data Tersedia --}}
    <div class="mt-8">
        <x-filament::tabs wire:model="activeTab">
            <x-filament::tabs.item value="items">
                📦 Data dalam Rombongan
            </x-filament::tabs.item>

            <x-filament::tabs.item value="available">
                ➕ Data Tersedia
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div class="mt-4">
            @if($activeTab === 'items')
                @livewire(\App\Filament\Opd\Resources\Rombongan\Pages\RombonganItemsTable::class, ['rombonganId' => $record->id], key('rombongan-items-' . $record->id . '-' . $refreshKey))
            @else
                @livewire(\App\Filament\Opd\Resources\Rombongan\Pages\AvailableItemsTable::class, ['rombonganId' => $record->id], key('available-items-' . $record->id . '-' . $refreshKey))
            @endif
        </div>
    </div>
</x-filament-panels::page>