<?php

namespace App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages;

use App\Filament\Verifikator\Resources\RombonganVerifikatorResource;
use App\Models\Opd;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

class ListRombonganVerifikators extends ListRecords
{
    protected static string $resource = RombonganVerifikatorResource::class;

    // ✅ Polling interval untuk auto refresh
    protected static ?string $pollingInterval = '5s';

    /** Dropdown value (belum dipakai query) */
    public string $opdSelected = '';

    /** Value yang sudah diklik tombol Cari */
    public string $opdApplied = '';

    public function mount(): void
    {
        parent::mount();
        
        // Ambil dari session
        $this->opdApplied = session('filter_opd_verifikator', '');
        $this->opdSelected = $this->opdApplied; // Sync awal
    }

    #[On('opd-selected')]
    public function setOpd($opd): void
    {
        $this->opdSelected = $opd;
        session(['filter_opd_verifikator' => $opd]);
        $this->resetTable();
    }

    public function updatedOpdSelected($value): void
    {
        // Hanya update selected, TIDAK applied
        // Filter belum diterapkan sampai tombol Cari ditekan
        session(['opd_selected_temp' => $value]);
    }

    /** ✅ Klik tombol Cari */
    public function applyFilter(): void
    {
        $this->opdApplied = $this->opdSelected;
        session(['filter_opd_verifikator' => $this->opdApplied]);
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeader(): ?View
    {
        return view('filament.verifikator.rombongan.header-dropdown', [
            'opdSelected' => $this->opdSelected,
            'opdApplied' => $this->opdApplied,
            'opds' => Opd::orderBy('code')->pluck('code', 'code'),
        ]);
    }

    protected function getTableQuery(): ?Builder
    {
        $model = RombonganVerifikatorResource::getModel();

        // ✅ Pakai $opdApplied (setelah klik Cari), bukan $opdSelected
        if (empty($this->opdApplied)) {
            return $model::query()->whereRaw('1 = 0');
        }

        return $model::query()
            ->where('nama_opd', $this->opdApplied)
            ->where('status_pengiriman', '!=', 'Belum Dikirim');
    }

    protected function getListeners(): array
    {
        return [
            'rombongan-updated' => 'refreshTable',
        ];
    }

    public function refreshTable(): void
    {
        Notification::make()
            ->title('🔄 Data Baru Masuk')
            ->body('Ada data rombongan yang baru dikirim dari OPD.')
            ->success()
            ->duration(5000)
            ->send();
    }
}