<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use App\Models\Rombongan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRombongans extends ListRecords
{
    protected static string $resource = RombonganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Rombongan')
                ->action(function () {
                    $lastRombongan = Rombongan::orderBy('id', 'desc')->first();
                    $nextNumber = $lastRombongan ? intval(str_replace('Rombongan ', '', $lastRombongan->nama_rombongan)) + 1 : 1;
                    
                    $rombongan = Rombongan::create([
                        'kode_rombongan' => 'ROMB-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT),
                        'nama_rombongan' => 'Rombongan ' . $nextNumber,
                        'deskripsi' => 'Deskripsi rombongan ' . $nextNumber,
                        'status' => true,
                    ]);
                    
                    return redirect(RombonganResource::getUrl('index'));
                }),
        ];
    }
}