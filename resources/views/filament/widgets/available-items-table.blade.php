{{-- resources/views/filament/widgets/available-items-table.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Data Tersedia
        </x-slot>

        <x-slot name="description">
            Pilih data untuk ditambahkan ke rombongan
        </x-slot>

        @if($records->isEmpty())
            <div class="text-center py-8">
                <div class="flex justify-center">
                    <x-heroicon-o-document class="w-12 h-12 text-gray-400" />
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada data tersedia</h3>
                <p class="mt-1 text-sm text-gray-500">Semua data sudah ditambahkan ke rombongan atau belum ada data.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3">Jenis Data</th>
                            <th class="px-4 py-3">Nama Pekerjaan</th>
                            <th class="px-4 py-3">Kode RUP</th>
                            <th class="px-4 py-3">Pagu RUP</th>
                            <th class="px-4 py-3">Nilai Kontrak</th>
                            <th class="px-4 py-3">Dibuat</th>
                            <th class="px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600" wire:key="item-{{ $record['type_alias'] }}-{{ $record['original_id'] }}">
                                <td class="px-4 py-3">
                                    <x-filament::badge 
                                        :color="$record['item_color']"
                                    >
                                        {{ $record['item_label'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ \Illuminate\Support\Str::limit($record['nama_pekerjaan'], 50) }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $record['kode_rup'] }}
                                </td>
                                <td class="px-4 py-3">
                                    Rp {{ number_format($record['pagu_rup'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($record['nilai_kontrak'])
                                        Rp {{ number_format($record['nilai_kontrak'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    {{ \Carbon\Carbon::parse($record['created_at'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-filament::button
                                        size="sm"
                                        color="success"
                                        icon="heroicon-o-plus"
                                        wire:click="addToRombongan('{{ $record['type_alias'] }}', {{ $record['original_id'] }})"
                                        wire:loading.attr="disabled"
                                    >
                                        Tambah
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>