<?php

namespace App\Filament\Opd\Resources\Pls\Pages;  // ← Ganti jadi Pls

use App\Filament\Opd\Resources\Pls\PlResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPls extends ListRecords
{
    protected static string $resource = PlResource::class;

    public function getSubheading(): ?string
    {
        return 'PETUNJUK! Ini adalah halaman untuk melihat dan mengelola Data Non Tender.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}