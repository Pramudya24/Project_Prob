<?php

namespace App\Filament\Monitoring\Resources\MonitoringResource\Pages;

use App\Filament\Monitoring\Resources\MonitoringResource;
use App\Models\Rombongan;
use App\Models\Opd;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;

class ListMonitorings extends ListRecords
{
    protected static string $resource = MonitoringResource::class;

    protected static ?string $pollingInterval = '5s';

    /** Dropdown value (belum dipakai query) */
    public string $opdSelected = '';

    /** Value yang sudah diklik tombol Cari */
    public string $opdApplied = '';

    public function mount(): void
    {
        parent::mount();

        $this->opdApplied  = session('monitoring_opd_filter', '');
        $this->opdSelected = $this->opdApplied;
    }

    /** Klik tombol Cari */
    public function applyFilter(): void
    {
        $this->opdApplied = $this->opdSelected;

        session(['monitoring_opd_filter' => $this->opdApplied]);

        $this->resetTable();
    }

    public function getHeader(): ?View
    {
        return view('filament.monitoring.components.opd-filter-header', [
            'opdSelected' => $this->opdSelected,
            'opdApplied' => $this->opdApplied,
            'opds' => Opd::orderBy('code')->pluck('code', 'code'),
        ]);
    }

    protected function getTableQuery(): Builder
    {
        if (blank($this->opdApplied)) {
            // Belum klik Cari → tabel kosong
            return Rombongan::query()->whereRaw('1 = 0');
        }

        return Rombongan::query()
            ->where('nama_opd', $this->opdApplied)
            ->where('is_sent_to_monitoring', true)
            ->where('status_pengiriman', 'Data Akhir')
            ->orderBy('tanggal_kirim_monitoring', 'desc');
    }

    protected function getListeners(): array
    {
        return [
            'rombongan-updated' => 'notifyRefresh',
        ];
    }

    public function notifyRefresh(): void
    {
        Notification::make()
            ->title('🔄 Data Baru Masuk')
            ->body('Ada data rombongan baru dari OPD terkait.')
            ->success()
            ->send();
    }
}
