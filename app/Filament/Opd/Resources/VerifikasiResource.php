<?php

namespace App\Filament\Opd\Resources;

use App\Filament\Opd\Resources\VerifikasiResource\Pages;
use App\Filament\Opd\Resources\VerifikasiResource\RelationManagers;
use App\Models\Verifikasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VerifikasiResource extends Resource
{
    protected static ?string $model = Verifikasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerifikasis::route('/'),
            'create' => Pages\CreateVerifikasi::route('/create'),
            'edit' => Pages\EditVerifikasi::route('/{record}/edit'),
        ];
    }
}
