<div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
    
    {{-- Filter OPD Section - Sejajar dengan Search --}}
    <div class="fi-ta-header-ctn divide-y divide-gray-200 dark:divide-white/10">
        <div class="flex items-center gap-4 px-6 py-4">
            
            {{-- Filter OPD di Kiri --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Pilih OPD:
                </label>
                
                <select
                    wire:model="opdSelected"
                    class="fi-select-input block rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm transition duration-75 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500 w-64"
                >
                    <option value="">-- Pilih OPD --</option>
                    @foreach ($opds as $code => $name)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>

                {{-- ✅ Tombol Cari --}}
                <button
                    wire:click="applyFilter"
                    wire:loading.attr="disabled"
                    class="relative inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 
                           text-sm font-semibold text-white shadow transition hover:bg-primary-500 
                           disabled:opacity-50">
                    
                    {{-- Spinner --}}
                    <svg
                        wire:loading
                        wire:target="applyFilter"
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>

                    <span wire:loading.remove wire:target="applyFilter">Cari</span>
                    <span wire:loading wire:target="applyFilter">Mencari...</span>
                </button>
            </div>
            
            {{-- Spacer untuk dorong search ke kanan --}}
            <div class="flex-1"></div>
            
            {{-- Search akan muncul otomatis di sini oleh Filament --}}
        </div>
        
        {{-- Info UX: Peringatan klik Cari --}}
        @if ($opdSelected && $opdSelected !== $opdApplied)
            <div class="px-6 py-3">
                <div class="flex items-center gap-3 rounded-lg bg-warning-50 px-4 py-3 text-sm text-warning-600 dark:bg-warning-400/10 dark:text-warning-400">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span>Klik <b>Cari</b> untuk menampilkan data dari OPD yang dipilih</span>
                </div>
            </div>
        @endif
        
        {{-- Peringatan jika belum pilih OPD atau belum klik Cari --}}
        @if (!$opdApplied)
            <div class="px-6 py-4">
                <div class="flex items-center gap-3 rounded-lg bg-danger-50 px-4 py-3 text-sm text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <span class="font-medium">
                        @if($opdSelected && $opdSelected !== $opdApplied)
                            Silakan klik tombol <b>Cari</b> untuk melihat data rombongan
                        @else
                            Silakan pilih OPD dan klik tombol <b>Cari</b> untuk melihat data rombongan
                        @endif
                    </span>
                </div>
            </div>
        @endif
    </div>
    
</div>