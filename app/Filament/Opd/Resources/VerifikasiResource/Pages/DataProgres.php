<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use App\Filament\Opd\Resources\VerifikasiResource;
use App\Models\Rombongan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class DataProgres extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = VerifikasiResource::class;

    protected static string $view = 'filament.opd.resource.verifikasi-resource.pages.data-progres';

    // ✅ Auto refresh setiap 5 detik
    protected static ?string $pollingInterval = '5s';

    public function getTitle(): string
    {
        return 'Data Progres - Perlu Perbaikan';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rombongan::query()
                    ->where('status_pengiriman', 'Data Progres')
                    ->where('nama_opd', auth()->user()->opd_code)
                    ->orderBy('tanggal_verifikasi', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'Total: ' . $record->total_items . ' item'),

                Tables\Columns\TextColumn::make('nama_opd')
                    ->label('OPD')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('tanggal_verifikasi')
                    ->label('Dikembalikan Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('verifikator.name')
                    ->label('Verifikator')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('keterangan_verifikasi')
                    ->label('Keterangan')
                    ->wrap()
                    ->limit(50),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->url(fn($record) => VerifikasiResource::getUrl('edit-data-progres', ['record' => $record]))
                    ->tooltip('Edit dan perbaiki data sesuai catatan verifikator'),

                Tables\Actions\Action::make('kirim_ulang')
                    ->label('Kirim Ulang')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Ulang ke Verifikator')
                    ->modalDescription('Pastikan data sudah diperbaiki sebelum mengirim ulang.')
                    ->modalSubmitActionLabel('Ya, Kirim Ulang')
                    ->action(function ($record) {
                        $record->update([
                            'status_pengiriman' => 'Terkirim ke Verifikator',
                            'tanggal_masuk_verifikator' => now(),
                            'status_verifikasi' => 'Belum',
                        ]);

                        Notification::make()
                            ->title('✅ Berhasil Dikirim Ulang')
                            ->body('Data "' . $record->nama_rombongan . '" telah dikirim ulang ke verifikator.')
                            ->success()
                            ->send();
                    }),
            ])
            // ✅ Tambahkan default sort
            ->defaultSort('tanggal_verifikasi', 'desc')
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Tidak ada data yang perlu diperbaikan.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    // Tambahkan method ini
    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    // Optional: Refresh otomatis dengan notifikasi
    protected function getListeners(): array
    {
        return [
            'refreshDataProgres' => '$refresh',
        ];
    }
}
