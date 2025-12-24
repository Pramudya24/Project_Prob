<?php

namespace App\Filament\Opd\Resources\EpurcasingResource\Pages;

use App\Filament\Opd\Resources\EpurcasingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEpurcasing extends EditRecord
{
    protected static string $resource = EpurcasingResource::class;

    // ✅ TAMBAHKAN INI - Redirect setelah edit
    protected function getRedirectUrl(): string
    {
        return EpurcasingResource::getUrl('index');
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
