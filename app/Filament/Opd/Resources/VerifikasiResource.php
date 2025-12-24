<?php

namespace App\Filament\Opd\Resources;

use App\Filament\Opd\Resources\VerifikasiResource\Pages;
use App\Models\Rombongan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class VerifikasiResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Verifikasi';
    protected static ?string $pluralModelLabel = 'Verifikasi';
    protected static ?int $navigationSort = 9;

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
            'index' => Pages\DataVerifikasi::route('/'),
            'data-verifikasi' => Pages\DataVerifikasi::route('/data-verifikasi'),
            'edit-data-progres' => Pages\EditDataProgres::route('/data-verifikasi/{record}/edit'),
            'data-akhir' => Pages\DataAkhir::route('/data-akhir'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}