<x-filament-panels::page>
    <div class="mb-6 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <x-heroicon-o-archive-box class="w-6 h-6 text-primary-600" />
            <div>
                <h3 class="font-semibold text-primary-800 dark:text-primary-200">Data Final</h3>
            </div>
        </div>
    </div>
    
    {{ $this->table }}
</x-filament-panels::page>