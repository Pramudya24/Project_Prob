<div class="fi-ta-ctn divide-y divide-gray-200 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

    <div class="px-6 py-4">
        <div class="flex items-center gap-4">

            {{-- Dropdown OPD --}}
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Pilih OPD:
                </label>

                <select
                    wire:model="opdSelected"
                    class="w-64 rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm
                           focus:border-primary-500 focus:ring-primary-500
                           dark:border-gray-600 dark:bg-gray-800 dark:text-white">

                    <option value="" class="text-gray-400 dark:text-gray-400">
                        -- Pilih OPD --
                    </option>

                    @foreach ($opds as $code => $name)
                    <option value="{{ $code }}" class="text-gray-900 dark:text-white">
                        {{ $code }}
                    </option>
                    @endforeach

                </select>
            </div>

            {{-- Tombol Cari --}}
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

        {{-- Info UX --}}
        @if ($opdSelected && $opdSelected !== $opdApplied)
        <div class="mt-3 text-sm text-amber-600 dark:text-amber-400">
            Klik <b>Cari</b> untuk menampilkan data dari OPD yang dipilih
        </div>
        @endif

        {{-- Warning belum pilih OPD --}}
        @if (!$opdApplied)
        <div class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600 
                    dark:bg-red-900/20 dark:text-red-400">
            Silakan pilih OPD dan klik tombol <b>Cari</b> untuk melihat data.
        </div>
        @endif
    </div>
</div>