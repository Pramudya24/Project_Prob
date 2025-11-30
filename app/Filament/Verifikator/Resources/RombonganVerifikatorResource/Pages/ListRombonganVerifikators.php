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

        $this->opdSelected = session('filter_opd_verifikator', '');
    }

    #[On('opd-selected')]
    public function setOpd($opd): void
    {
        $this->opdSelected = $opd;

        session(['filter_opd_verifikator' => $opd]);

        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pilih_opd')
                ->label('Pilih OPD')
                ->icon('heroicon-o-building-office')
                ->form([
                    Forms\Components\Select::make('opd')
                        ->label('Pilih OPD')
                        ->options(Opd::pluck('code', 'code'))
                        ->searchable()
                        ->required(),
                ])
                ->modalHeading('Pilih OPD')
                ->action(function (array $data): void {
                    $this->dispatch('opd-selected', opd: $data['opd']);
                }),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.verifikator.rombongan.header-dropdown', [
            'opdSelected' => $this->opdSelected,
            'opds' => \App\Models\Opd::pluck('code', 'code'),
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
