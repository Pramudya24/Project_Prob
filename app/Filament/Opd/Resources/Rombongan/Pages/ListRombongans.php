<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use App\Models\Rombongan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListRombongans extends ListRecords
{
    protected static string $resource = RombonganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('New Pengajuan')
                ->color('primary')
                ->action(function () {
                    $user = auth()->user();

                    // Ambil code OPD untuk digunakan dalam format nama rombongan
                    $opdCode = $user->opd_code; // Singkatan OPD (BKPSDM, DINKES, dll)
                    
                    // Auto-generate nama rombongan berdasarkan OPD
                    $lastRombongan = Rombongan::where('nama_opd', $user->opd_code)
                        ->latest('id')
                        ->first();
                    
                    if ($lastRombongan) {
                        // Ambil nomor dari format "BKPSDM-001"
                        preg_match('/' . preg_quote($opdCode, '/') . '-(\d+)/', $lastRombongan->nama_rombongan, $matches);
                        $nextNumber = isset($matches[1]) ? (intval($matches[1]) + 1) : 1;
                    } else {
                        $nextNumber = 1;
                    }
                    
                    // Format: BKPSDM-001, DINKES-001, dll
                    $namaRombongan = $opdCode . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                    // Create rombongan baru
                    Rombongan::create([
                        'nama_rombongan' => $namaRombongan,
                        'status_pengiriman' => 'Belum Dikirim',
                        'nama_opd' => $user->opd_code,
                        'total_items' => 0,
                        'total_nilai' => 0,
                    ]);

                    // Notifikasi sukses
                    Notification::make()
                        ->title('Rombongan Berhasil Dibuat')
                        ->body("Rombongan baru \"{$namaRombongan}\" telah dibuat.")
                        ->success()
                        ->send();
                    
                    // Refresh halaman
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}