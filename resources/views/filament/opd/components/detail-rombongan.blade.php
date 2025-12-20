
<div class="space-y-6">
    {{-- INFORMASI ROMBONGAN --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-lg p-6 border border-blue-200 dark:border-gray-700">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Nama Rombongan</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $record->nama_rombongan }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">No. SPM</p>
                <p class="text-sm font-bold text-green-600 dark:text-green-400">{{ $record->no_spm ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Item</p>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $record->total_items }} item</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Total Nilai</p>
                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($record->total_nilai, 0, ',', '.') }}</p>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-blue-200 dark:border-gray-700 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Verifikator</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $record->verifikator->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-1">Tanggal Kirim Monitoring</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $record->tanggal_kirim_monitoring?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- DAFTAR ITEM DALAM ACCORDION --}}
    <div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Daftar Item dalam Rombongan
        </h3>

        @php
            $groupedItems = $record->getGroupedItems();
        @endphp

        @if(empty($groupedItems))
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="font-medium">Tidak ada item dalam rombongan ini</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($groupedItems as $type => $group)
                    {{-- ACCORDION CONTAINER --}}
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                        {{-- ACCORDION HEADER --}}
                        <button 
                            type="button"
                            onclick="toggleAccordion('accordion-{{ $loop->index }}')"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📦</span>
                                <div class="text-left">
                                    <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ strtoupper($group['label']) }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($group['items']) }} item</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 accordion-icon" 
                                 id="icon-accordion-{{ $loop->index }}"
                                 fill="none" 
                                 stroke="currentColor" 
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- ACCORDION CONTENT --}}
                        <div id="accordion-{{ $loop->index }}" class="accordion-content">
                            <div class="p-6 space-y-6">
                                @foreach($group['items'] as $item)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900">
                                        {{-- HEADER ITEM --}}
                                        <div class="mb-4 pb-3 border-b border-gray-300 dark:border-gray-600">
                                            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">
                                                Item: {{ $item['data']['nama_pekerjaan'] ?? 'Tidak ada nama' }}
                                            </h5>
                                        </div>

                                        {{-- TABEL DATA ITEM --}}
                                        <div class="overflow-x-auto">
                                            <table class="w-full border-collapse border border-gray-300 dark:border-gray-600 text-sm">
                                                <thead>
                                                    <tr class="bg-gray-100 dark:bg-gray-700">
                                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold w-16">No</th>
                                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold">Uraian</th>
                                                        <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold">Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $no = 1;
                                                        $fieldsToShow = [
                                                            'nama_pekerjaan' => 'Nama Pekerjaan',
                                                            'kode_rup' => 'Kode RUP',
                                                            'pagu_rup' => 'Pagu RUP',
                                                            'kode_paket' => 'Kode Paket',
                                                            'jenis_pengadaan' => 'Jenis Pengadaan',
                                                            'metode_pengadaan' => 'Metode Pengadaan',
                                                            'nilai_kontrak' => 'Nilai Kontrak',
                                                            'pdn_tkdn_impor' => 'PDN/TKDN/IMPOR',
                                                            'nilai_pdn_tkdn_impor' => 'Nilai PDN/TKDN/IMPOR',
                                                            'umk_non_umk' => 'UMK / Non UMK',
                                                            'nilai_umk' => 'Nilai UMK',
                                                        ];
                                                    @endphp

                                                    @foreach($fieldsToShow as $fieldKey => $fieldLabel)
                                                        @if(isset($item['data'][$fieldKey]))
                                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">{{ $no++ }}</td>
                                                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-medium">{{ $fieldLabel }}</td>
                                                                <td class="border border-gray-300 dark:border-gray-600 px-4 py-3">
                                                                    @php
                                                                        $value = $item['data'][$fieldKey];
                                                                        
                                                                        // Format nilai rupiah
                                                                        if (in_array($fieldKey, ['pagu_rup', 'nilai_kontrak', 'nilai_pdn_tkdn_impor', 'nilai_umk']) && is_numeric($value)) {
                                                                            $value = 'Rp ' . number_format($value, 0, ',', '.');
                                                                        }
                                                                        
                                                                        // Format tanggal
                                                                        if (str_contains($fieldKey, 'tanggal') && $value && $value !== '-') {
                                                                            try {
                                                                                $value = \Carbon\Carbon::parse($value)->format('d/m/Y');
                                                                            } catch (\Exception $e) {
                                                                                // Keep original
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    {{ $value ?? '-' }}
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- CATATAN VERIFIKASI --}}
    @if($record->keterangan_verifikasi)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-lg">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
            <div>
                <h4 class="text-sm font-bold text-yellow-800 dark:text-yellow-200 mb-1">Catatan Verifikasi</h4>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 whitespace-pre-wrap">{{ $record->keterangan_verifikasi }}</p>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- JAVASCRIPT UNTUK ACCORDION --}}
<script>
    function toggleAccordion(id) {
        const content = document.getElementById(id);
        const icon = document.getElementById('icon-' + id);
        
        if (content.classList.contains('accordion-open')) {
            content.classList.remove('accordion-open');
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.classList.add('accordion-open');
            icon.style.transform = 'rotate(180deg)';
        }
    }
</script>

{{-- CSS UNTUK ACCORDION --}}
<style>
    .accordion-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    
    .accordion-content.accordion-open {
        max-height: 5000px;
        transition: max-height 0.5s ease-in;
    }
</style>