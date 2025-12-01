<?php

namespace App\Filament\Opd\Resources\NonTenderResource\Pages;

use App\Filament\Opd\Resources\NonTenderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNonTenders extends ListRecords
{
    protected static string $resource = NonTenderResource::class;
    public function getSubheading(): ?string
    {
        return 'PETUNJUK! Ini adalah halaman untuk melihat dan mengelola Data Pencatatan Non Tender.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
