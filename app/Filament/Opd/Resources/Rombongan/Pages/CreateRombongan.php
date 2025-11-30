<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Opd;

class CreateRombongan extends CreateRecord
{
    protected static string $resource = RombonganResource::class;
    protected ?string $heading = 'Tambah Pengajuan';

    /**
     * Mengisi otomatis nama_opd berdasarkan user login (OPD)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['nama_opd'] = $user->opd_code;  // Langsung ambil dari user

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
