<?php

namespace App\Filament\Opd\Resources; // ← Tambahkan "Resources"

use App\Filament\Opd\Resources\VerifikasiResource\Pages; // ← Ganti ini
use App\Models\Rombongan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class VerifikasiResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Verifikasi';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([])->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasis::route('/'), // ← Tambahkan ini biar ada halaman index
            'data-progres' => Pages\DataProgres::route('/data-progres'),
            'data-sudah-progres' => Pages\DataSudahProgres::route('/data-sudah-progres'),
            'data-akhir' => Pages\DataAkhir::route('/data-akhir'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}