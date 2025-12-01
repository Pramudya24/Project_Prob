<?php

namespace App\Filament\Opd\Resources\EpurcasingResource\Pages;

use App\Filament\Opd\Resources\EpurcasingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEpurcasings extends ListRecords
{
    protected static string $resource = EpurcasingResource::class;

    public function getSubheading(): ?string
    {
        return 'PETUNJUK! Ini adalah halaman untuk melihat dan mengelola Data Epurcasing.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
