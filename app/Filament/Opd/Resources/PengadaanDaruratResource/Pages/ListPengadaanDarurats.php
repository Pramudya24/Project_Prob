<?php

namespace App\Filament\Opd\Resources\PengadaanDaruratResource\Pages;

use App\Filament\Opd\Resources\PengadaanDaruratResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengadaanDarurats extends ListRecords
{
    protected static string $resource = PengadaanDaruratResource::class;

    public function getSubheading(): ?string
    {
        return 'PETUNJUK! Ini adalah halaman untuk melihat dan mengelola Data Pencatatan Pengadaan Darurat.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
