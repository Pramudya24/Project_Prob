<x-filament-panels::page>
    <div class="mb-6 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-700 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <x-heroicon-o-check-circle class="w-6 h-6 text-success-600" />
            <div>
                <h3 class="font-semibold text-success-800 dark:text-success-200">Data Lolos Verifikasi</h3>
                <p class="text-sm text-success-600 dark:text-success-300">Data di bawah ini sudah lolos verifikasi 100%. Siap dikirim ke Data Akhir.</p>
            </div>
        </div>
    </div>
    
    {{ $this->table }}
</x-filament-panels::page>