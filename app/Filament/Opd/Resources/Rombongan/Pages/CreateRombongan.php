<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use App\Models\Rombongan;
use Filament\Resources\Pages\CreateRecord;

class CreateRombongan extends CreateRecord
{
    protected static string $resource = RombonganResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate nama rombongan
        $lastRombongan = Rombongan::orderBy('id', 'desc')->first();
        $nextNumber = $lastRombongan ? 
            intval(str_replace('Rombongan ', '', $lastRombongan->nama_rombongan)) + 1 : 1;
        
        $data['nama_rombongan'] = 'Rombongan ' . $nextNumber;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}