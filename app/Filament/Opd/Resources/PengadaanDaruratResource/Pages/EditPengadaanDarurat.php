<?php

namespace App\Filament\Opd\Resources\PengadaanDaruratResource\Pages;

use App\Filament\Opd\Resources\PengadaanDaruratResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPengadaanDarurat extends EditRecord
{
    protected static string $resource = PengadaanDaruratResource::class;

    // ✅ TAMBAHKAN INI - Redirect setelah edit
    protected function getRedirectUrl(): string
    {
        return PengadaanDaruratResource::getUrl('index');
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
