<?php

namespace App\Filament\Opd\Resources\Rombongan;

use App\Models\Rombongan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;


class RombonganResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pengajuan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->required()
                    ->unique()
                    ->maxLength(255)
                    ->placeholder('Masukkan nama rombongan'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai Kontrak'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => in_array($record->status_pengiriman, ['Belum Dikirim', 'Revisi'])),

                Tables\Actions\Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn($record) => $record->status_pengiriman === 'Belum Dikirim')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Rombongan ke Verifikator')
                    ->modalDescription('Apakah Anda yakin ingin mengirim rombongan ini ke verifikator?')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function ($record) {
                        // Cek apakah ada item dalam rombongan
                        if ($record->total_items === 0) {
                            Notification::make()
                                ->title('Gagal Mengirim')
                                ->body('Rombongan harus memiliki minimal 1 item.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $record->update([
                            'status_pengiriman' => 'Terkirim ke Verifikator',
                            'tanggal_masuk_verifikator' => now(), // ← Tambah ini
                        ]);

                        Notification::make()
                            ->title('Rombongan Berhasil Dikirim')
                            ->body('Rombongan telah dikirim ke verifikator.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->status_pengiriman === 'Belum Dikirim'),
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
            'index' => Pages\ListRombongans::route('/'),
            'create' => Pages\CreateRombongan::route('/create'),
            'edit' => Pages\EditRombongan::route('/{record}/edit'),
            'view' => Pages\ViewRombongan::route('/{record}'),
        ];
    }
}
