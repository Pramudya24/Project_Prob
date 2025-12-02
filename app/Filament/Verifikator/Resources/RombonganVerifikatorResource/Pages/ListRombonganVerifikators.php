<?php

namespace App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages;

use App\Filament\Verifikator\Resources\RombonganVerifikatorResource;
use App\Models\Opd;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Illuminate\Contracts\View\View;

class ListRombonganVerifikators extends ListRecords
{
    protected static string $resource = RombonganVerifikatorResource::class;

    public string $opdSelected = '';

    public function mount(): void
    {
        parent::mount();

        // Load filter dari session
        $this->opdSelected = session('filter_opd_verifikator', '');
    }

    #[On('opd-selected')]
    public function setOpd($opd): void
    {
        $this->opdSelected = $opd;

        // Simpan ke session supaya persist setelah save
        session(['filter_opd_verifikator' => $opd]);

        $this->resetTable();
    }

    // Update opdSelected langsung dari dropdown
    public function updatedOpdSelected($value): void
    {
        session(['filter_opd_verifikator' => $value]);
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            // Header action tidak perlu lagi, pakai dropdown di header custom
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.verifikator.rombongan.header-dropdown', [
            'opdSelected' => $this->opdSelected,
            'opds' => Opd::orderBy('code')->pluck('code', 'code'),
        ]);
    }

    protected function getTableQuery(): ?Builder
    {
        $model = RombonganVerifikatorResource::getModel();

        if (empty($this->opdSelected)) {
            return $model::query()->whereRaw('1 = 0');
        }

        return $model::query()
            ->where('nama_opd', $this->opdSelected)
            ->where('status_pengiriman', '!=', 'Belum Dikirim');
    }
}