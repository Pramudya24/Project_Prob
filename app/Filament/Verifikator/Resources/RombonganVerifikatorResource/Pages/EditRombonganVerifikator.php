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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set verifikator
        $data['verifikator_id'] = auth()->user()->id;
        $data['tanggal_verifikasi'] = now();

        // Cek validasi otomatis
        $isValid = $this->record->checkAutoValidation();
        $data['lolos_verif'] = $isValid;

        // Jika status verifikasi *Sudah*
        if ($data['status_verifikasi'] === 'Sudah') {

            // Auto-generate keterangan jika lolos verifikasi otomatis
            if ($isValid) {
                $text = "Lolos Verif - Semua item sudah diverifikasi. Diverifikasi pada " . now()->format('d/m/Y H:i');

                if (empty($data['keterangan_verifikasi'])) {
                    $data['keterangan_verifikasi'] = $text;
                } else {
                    $data['keterangan_verifikasi'] .= "\n\n[$text]";
                }

                // Update status pengiriman jika lolos
                $data['status_pengiriman'] = 'Terkirim ke Verifikator';

            } else {
                // Status verifikasi "Sudah" tetapi auto validation gagal
                $data['status_pengiriman'] = 'Terkirim ke Verifikator';
            }

        } else {
            // Jika status verifikasi "Belum"
            $data['status_pengiriman'] = 'Terkirim ke Verifikator';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $progress = $record->getVerificationProgress();

        if ($record->lolos_verif && $record->status_verifikasi === 'Sudah') {

            Notification::make()
                ->title('Verifikasi Berhasil')
                ->body('Semua item telah diverifikasi (' . $progress['verified'] . '/' . $progress['total'] . '). Silakan kirim ke SPM dari halaman list.')
                ->success()
                ->duration(8000)
                ->send();

        } elseif ($record->status_verifikasi === 'Sudah') {

            Notification::make()
                ->title('Verifikasi Disimpan')
                ->body('Progress verifikasi: ' . $progress['verified'] . '/' . $progress['total'] . ' item. Mohon verifikasi semua item terlebih dahulu.')
                ->warning()
                ->duration(8000)
                ->send();

        } else {

            Notification::make()
                ->title('Data Disimpan')
                ->body('Progress verifikasi: ' . $progress['verified'] . '/' . $progress['total'] . ' item.')
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}