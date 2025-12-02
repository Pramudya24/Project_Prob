<?php

namespace App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages;

use App\Filament\Verifikator\Resources\RombonganVerifikatorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRombonganVerifikator extends EditRecord
{
    protected static string $resource = RombonganVerifikatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Kembali
            Actions\Action::make('kembali')
                ->label('Kembali')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    protected function afterSave(): void
    {
        // Setelah save (jika ada perubahan di data rombongan)
        Notification::make()
            ->title('Data Tersimpan')
            ->body('Data rombongan berhasil disimpan.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke index dengan filter OPD yang masih aktif
        return static::getResource()::getUrl('index');
    }
}