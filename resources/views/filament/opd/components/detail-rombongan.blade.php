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

    {{-- DAFTAR ITEM --}}
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
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        {{-- HEADER TYPE --}}
                        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $group['label'] }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">({{ count($group['items']) }} item)</span>
                            </h4>
                        </div>

                        {{-- ITEMS --}}
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($group['items'] as $item)
                                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {{-- KOLOM KIRI --}}
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Nama Pekerjaan</p>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['data']['nama_pekerjaan'] ?? '-' }}</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Kode RUP</p>
                                                    <p class="text-sm text-gray-900 dark:text-white">{{ $item['data']['kode_rup'] ?? '-' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Kode Paket</p>
                                                    <p class="text-sm text-gray-900 dark:text-white">{{ $item['data']['kode_paket'] ?? '-' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- KOLOM KANAN --}}
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Jenis Pengadaan</p>
                                                <p class="text-sm text-gray-900 dark:text-white">{{ $item['data']['jenis_pengadaan'] ?? '-' }}</p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Pagu RUP</p>
                                                    <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                                        Rp {{ number_format($item['data']['pagu_rup'] ?? 0, 0, ',', '.') }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Nilai Kontrak</p>
                                                    <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                        Rp {{ number_format($item['data']['nilai_kontrak'] ?? 0, 0, ',', '.') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- INFO TAMBAHAN --}}
                                    @if(isset($item['data']['metode_pengadaan']))
                                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex flex-wrap gap-2 items-center text-xs">
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $item['data']['metode_pengadaan'] }}
                                            </span>
                                            
                                            @if(isset($item['data']['pdn_tkdn_impor']))
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ $item['data']['pdn_tkdn_impor'] }}
                                            </span>
                                            @endif
                                            
                                            @if(isset($item['data']['umk_non_umk']))
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $item['data']['umk_non_umk'] }}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            @endforeach
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