<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Opd;

class CreateRombongan extends CreateRecord
{
    protected static string $resource = RombonganResource::class;
    protected ?string $heading = 'Tambah Pengajuan';

    /**
     * Mengisi otomatis nama_opd berdasarkan user login (OPD)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['status_pengiriman'] = 'Belum Dikirim';        // ← AUTO SET OPD
        $data['nama_opd'] = auth()->user()->opd_code;       // ← AUTO SET NAMA OPD
        $data['total_items'] = 0;                           // ← Default 0 item
        $data['total_nilai'] = 0;     // Langsung ambil dari user

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
                ->label('Simpan'), // Ubah "Create" jadi "Simpan"
            
            $this->getCancelFormAction()
                ->label('Batal'), // Ubah "Cancel" jadi "Batal"
        ];
    }
}
