<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use App\Filament\Opd\Resources\VerifikasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerifikasis extends ListRecords
{
    protected static string $resource = VerifikasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('data-progres')
                ->label('Data Progres')
                ->color('primary')
                ->url(fn () => VerifikasiResource::getUrl('data-progres')),
                
            Actions\Action::make('data-sudah-progres')
                ->label('Pembuatan SPM')
                ->color('primary')
                ->url(fn () => VerifikasiResource::getUrl('data-sudah-progres')),
                
            Actions\Action::make('data-akhir')
                ->label('History')
                ->color('primary')
                ->url(fn () => VerifikasiResource::getUrl('data-akhir')),
                
            // Actions\CreateAction::make(),
        ];
    }
}