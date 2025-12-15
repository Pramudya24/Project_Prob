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
                        'Belum Dikirim' => 'gray',           // ← TAMBAH INI
                        'Terkirim ke Verifikator' => 'info',
                        'Data Progres' => 'warning',
                        'Data Sudah Progres' => 'success',
                        'Data Akhir' => 'primary',           // ← TAMBAH INI
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
            Tables\Actions\ViewAction::make(),

            // Tombol EDIT: bisa edit jika belum dikirim atau status Data Progres
            Tables\Actions\EditAction::make()
                ->visible(function ($record) {
                    return in_array($record->status_pengiriman, [
                        'Belum Dikirim',
                        'Data Progres'
                    ]);
                }),

            // Tombol KIRIM: hanya muncul untuk status Belum Dikirim atau Data Progres
            Tables\Actions\Action::make('send')
                ->label('Kirim ke Verifikator')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(function ($record) {
                    return in_array($record->status_pengiriman, [
                        'Belum Dikirim',
                        'Data Progres'
                    ]);
                })
                ->requiresConfirmation()
                ->modalHeading('Kirim Rombongan ke Verifikator')
                ->modalDescription(function ($record) {
                    if ($record->status_pengiriman === 'Data Progres') {
                        return 'Data yang sudah diperbaiki akan dikirim ulang ke verifikator. Apakah Anda yakin?';
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
                        'tanggal_masuk_verifikator' => now(),
                    ]);

                    $message = $record->status_pengiriman === 'Data Progres' 
                        ? 'Data berhasil dikirim ulang ke verifikator.'
                        : 'Rombongan berhasil dikirim ke verifikator.';

                    Notification::make()
                        ->title('✅ Berhasil Dikirim')
                        ->body($message)
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
            // 'create' => Pages\CreateRombongan::route('/create'),
            'edit' => Pages\EditRombongan::route('/{record}/edit'),
            'view' => Pages\ViewRombongan::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('nama_opd', auth()->user()->opd_code) // ← TAMBAH FILTER OPD
            ->whereIn('status_pengiriman', [
                'Belum Dikirim',              // ← Data baru
                'Terkirim ke Verifikator',    // ← Data di verifikator
                'Data Progres',               // ← Data perlu perbaikan
        ]);
    }
}