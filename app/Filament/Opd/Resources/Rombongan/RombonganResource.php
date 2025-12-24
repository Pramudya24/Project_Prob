<?php

namespace App\Filament\Opd\Resources\Rombongan;

use App\Models\Rombongan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;


class RombonganResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pengajuan';
    protected static ?int $navigationSort = 8;
    protected static ?string $pluralModelLabel = 'Pengajuan';
    
    public static function getModelLabel(): string
    {
        return 'Pengajuan';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('nama_rombongan')
                    ->default(function () {
                        $user = auth()->user();
                        $lastRombongan = \App\Models\Rombongan::where('nama_opd', $user->opd_code)
                            ->latest('id')
                            ->first();
                        
                        if ($lastRombongan) {
                            // Ambil nomor terakhir dari format "Rombongan-001"
                            preg_match('/Rombongan-(\d+)/', $lastRombongan->nama_rombongan, $matches);
                            $nextNumber = isset($matches[1]) ? (intval($matches[1]) + 1) : 1;
                        } else {
                            $nextNumber = 1;
                        }
                        
                        return 'Rombongan-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                    }),
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
                    
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Jumlah Item')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai Kontrak')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                    
                Tables\Columns\TextColumn::make('status_pengiriman')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Belum Dikirim' => 'gray',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // ✅ Tombol EDIT: hanya untuk yang belum dikirim
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->status_pengiriman === 'Belum Dikirim'),

                // ✅ Tombol KIRIM: hanya untuk yang belum dikirim
                Tables\Actions\Action::make('send')
                    ->label('Kirim ke Verifikator')
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
                            'tanggal_masuk_verifikator' => now(),
                        ]);

                        Notification::make()
                            ->title('✅ Berhasil Dikirim')
                            ->body('Rombongan berhasil dikirim ke verifikator.')
                            ->success()
                            ->send();
                    }),

                // ✅ Tombol DELETE: hanya untuk yang belum dikirim
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->status_pengiriman === 'Belum Dikirim'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Belum ada rombongan yang dibuat.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRombongans::route('/'),
            'edit' => Pages\EditRombongan::route('/{record}/edit'),
            'view' => Pages\ViewRombongan::route('/{record}'),
        ];
    }
    
    // ✅ FILTER: Hanya tampilkan data dengan status "Belum Dikirim"
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('nama_opd', auth()->user()->opd_code)
            ->where('status_pengiriman', 'Belum Dikirim');
    }
}