<?php

namespace App\Filament\Opd\Resources\Pls\Pages;  // ← Ganti jadi Pls

use App\Filament\Opd\Resources\Pls\PlResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPls extends ListRecords
{
    protected static string $resource = PlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}