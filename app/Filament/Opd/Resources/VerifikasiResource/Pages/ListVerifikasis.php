<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use App\Filament\Opd\Resources\VerifikasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerifikasis extends ListRecords
{
    protected static string $resource = VerifikasiResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\Action::make('data-verifikasi')
    //             ->label('Data Verifikasi')
    //             ->color('primary')
    //             ->icon('heroicon-o-clipboard-document-check')
    //             ->url(fn () => VerifikasiResource::getUrl('data-verifikasi')),
                
    //         Actions\Action::make('data-akhir')
    //             ->label('History')
    //             ->color('gray')
    //             ->icon('heroicon-o-archive-box')
    //             ->url(fn () => VerifikasiResource::getUrl('data-akhir')),
    //     ];
    // }
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    public function mount(): void
    {
        // Redirect otomatis ke data-verifikasi
        redirect()->route('filament.opd.resources.verifikasis.data-verifikasi');
    }
}