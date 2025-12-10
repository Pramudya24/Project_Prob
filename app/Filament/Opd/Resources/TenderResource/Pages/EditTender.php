<?php

namespace App\Filament\Opd\Resources\TenderResource\Pages;

use App\Filament\Opd\Resources\TenderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTender extends EditRecord
{
    protected static string $resource = TenderResource::class;

    // ✅ TAMBAHKAN INI - Redirect setelah edit
    protected function getRedirectUrl(): string
    {
        return TenderResource::getUrl('index');
    }

    // ✅ OPSIONAL: Notifikasi sukses edit
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data PL berhasil diperbarui';
    }
}
