<?php

namespace App\Filament\Opd\Resources\Pls\Pages;

use App\Filament\Opd\Resources\Pls\PlResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePl extends CreateRecord
{
    protected static string $resource = PlResource::class;
    protected ?string $heading = 'Tambah Data Non Tender';
    // ✅ PERBAIKI: Gunakan $this->getResource()::class
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Data PL berhasil disimpan';
    }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'), // Ubah "Create" jadi "Simpan"
            
            $this->getCancelFormAction()
                ->label('Batal'), // Ubah "Cancel" jadi "Batal"
        ];
    }
}
