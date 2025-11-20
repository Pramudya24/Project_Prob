<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRombongan extends CreateRecord
{
    protected static string $resource = RombonganResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}