<x-filament-panels::page>
    <div class="mb-6 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-600" />
            <div>
                <h3 class="font-semibold text-warning-800 dark:text-warning-200">Data Perlu Perbaikan</h3>
                <p class="text-sm text-warning-600 dark:text-warning-300">Data di bawah ini perlu diperbaiki sesuai catatan verifikator sebelum dikirim ulang.</p>
            </div>
        </div>
    </div>
    
    {{ $this->table }}
</x-filament-panels::page>