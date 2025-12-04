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
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->required()
                    ->unique(ignoreRecord: true)
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
                        'Terkirim ke Verifikator' => 'info',
                        'Data Progres' => 'warning',     // ← ubah dari 'revisi'
                        'Data Sudah Progres' => 'success', // ← ubah dari 'lolos'
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                // Tombol EDIT: bisa edit jika:
                // 1. Status 'Data Progres' (dikembalikan verifikator)
                // 2. Status 'Terkirim ke Verifikator' TAPI belum ada tanggal_masuk_verifikator (data baru)
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        // Data Data Progres dari verifikator
                        if ($record->status_pengiriman === 'Data Progres') {
                            return true;
                        }
                        
                        // Data baru: status 'Terkirim ke Verifikator' tapi belum dikirim
                        if ($record->status_pengiriman === 'Terkirim ke Verifikator' && 
                            !$record->tanggal_masuk_verifikator) {
                            return true;
                        }
                        
                        return false;
                    }),

                // Tombol KIRIM: hanya muncul untuk data baru atau data Data Progres
                Tables\Actions\Action::make('send')
                    ->label('Kirim ke Verifikator')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(function ($record) {
                        // Data Data Progres bisa dikirim ulang
                        if ($record->status_pengiriman === 'Data Progres') {
                            return true;
                        }
                        
                        // Data baru yang belum dikirim
                        if ($record->status_pengiriman === 'Terkirim ke Verifikator' && 
                            !$record->tanggal_masuk_verifikator) {
                            return true;
                        }
                        
                        return false;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Rombongan ke Verifikator')
                    ->modalDescription(function ($record) {
                        if ($record->status_pengiriman === 'Data Progres') {
                            return 'Data dari Data Progres akan dikirim ulang ke verifikator. Apakah Anda yakin?';
                        }
                        return 'Apakah Anda yakin ingin mengirim rombongan ini ke verifikator?';
                    })
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
                            'tanggal_masuk_verifikator' => now(), // Tandai sudah dikirim
                        ]);

                        $message = $record->status_pengiriman === 'Data Progres' 
                            ? 'Data dari Data Progres berhasil dikirim ulang ke verifikator.'
                            : 'Rombongan baru berhasil dikirim ke verifikator.';

                        Notification::make()
                            ->title('Berhasil Dikirim')
                            ->body($message)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    // OPD bisa hapus jika belum pernah dikirim ke verifikator
                    ->visible(fn($record) => !$record->tanggal_masuk_verifikator),
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
    
    // Tampilkan semua data untuk OPD kecuali yang status 'Data Sudah Progres' (karena sudah final)
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status_pengiriman', [
                'Terkirim ke Verifikator', 
                'Data Progres', 
                'Data Sudah Progres' // tetap tampilkan di sini, tapi di DataSudahProgres.php
            ]);
    }
}