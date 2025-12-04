<?php

namespace App\Filament\Opd\Resources\NonTenderResource\Pages;

use App\Filament\Opd\Resources\NonTenderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNonTender extends CreateRecord
{
    protected static string $resource = NonTenderResource::class;
    protected ?string $heading = 'Tambah Data Pencatatan Non Tender';
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
