<?php

namespace App\Filament\Opd\Resources\SwakelolaResource\Pages;

use App\Filament\Opd\Resources\SwakelolaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSwakelola extends EditRecord
{
    protected static string $resource = SwakelolaResource::class;

    // ✅ TAMBAHKAN INI - Redirect setelah edit
    protected function getRedirectUrl(): string
    {
        return SwakelolaResource::getUrl('index');
    }

    // ✅ OPSIONAL: Notifikasi sukses edit
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data PL berhasil diperbarui';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan'), // Ubah "Create" jadi "Simpan"
            
            $this->getCancelFormAction()
                ->label('Batal'), // Ubah "Cancel" jadi "Batal"
        ];
    }
}
