<x-filament-panels::page>
    <div class="flex items-center justify-center min-h-[70vh]">
        <div class="flex flex-col gap-6">

            {{-- Tombol 1 --}}
            <a href="{{ \App\Filament\Opd\Pages\DataProgres::getUrl() }}"
   class="rounded-xl shadow-lg hover:opacity-90 transition
          h-16 w-[350px] px-10 flex items-center justify-center text-2xl font-semibold text-white"
   style="background-color: #2563eb !important;">
    Data Progres
</a>

<a href="{{ \App\Filament\Opd\Pages\DataSudahProgres::getUrl() }}"
   class="rounded-xl shadow-lg hover:opacity-90 transition
          h-16 w-[350px] px-10 flex items-center justify-center text-2xl font-semibold text-white"
   style="background-color: #2563eb !important;">
    Data Sudah Progres
</a>

<a href="{{ \App\Filament\Opd\Pages\DataAkhir::getUrl() }}"
   class="rounded-xl shadow-lg hover:opacity-90 transition
          h-16 w-[350px] px-10 flex items-center justify-center text-2xl font-semibold text-white"
   style="background-color: #2563eb !important;">
    Data Akhir
</a>


        </div>
    </div>
</x-filament-panels::page>
