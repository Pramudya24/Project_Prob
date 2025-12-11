<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Rombongan;

class CreateRombongan extends CreateRecord
{
    protected static string $resource = RombonganResource::class;
    protected ?string $heading = 'Tambah Pengajuan';

    /**
     * Mengisi otomatis nama_opd dan nama_rombongan
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Auto-generate nama rombongan
        $lastRombongan = Rombongan::where('nama_opd', $user->opd_code)
            ->latest('id')
            ->first();
        
        if ($lastRombongan) {
            // Ambil nomor terakhir dari format "Rombongan-001"
            preg_match('/Rombongan-(\d+)/', $lastRombongan->nama_rombongan, $matches);
            $nextNumber = isset($matches[1]) ? (intval($matches[1]) + 1) : 1;
        } else {
            $nextNumber = 1;
        }
        
        $data['nama_rombongan'] = 'Rombongan-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $data['status_pengiriman'] = 'Belum Dikirim';
        $data['nama_opd'] = $user->opd_code;
        $data['total_items'] = 0;
        $data['total_nilai'] = 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'),
            
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}