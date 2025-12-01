<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VerifikasiResource\Pages;
use App\Models\Verifikasi;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VerifikasiResource extends Resource
{
    protected static ?string $model = Verifikasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Verifikasi';

    protected static ?string $navigationGroup = null; // atau 'Form' kalau mau di grup Form

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom tabel kamu
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasis::route('/'),
            'progres' => Pages\DataProgres::route('/progres'),
            'valid' => Pages\DataValid::route('/valid'),
            'akhir' => Pages\DataAkhir::route('/akhir'),
            'create' => Pages\CreateVerifikasi::route('/create'),
            'edit' => Pages\EditVerifikasi::route('/{record}/edit'),
        ];
    }
}