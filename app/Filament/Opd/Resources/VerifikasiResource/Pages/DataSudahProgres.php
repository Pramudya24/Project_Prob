<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use App\Filament\Opd\Resources\VerifikasiResource;
use App\Models\Rombongan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable; // ← GANTI INI (hapus "Pages\")
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class DataSudahProgres extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = VerifikasiResource::class;
    
    protected static string $view = 'filament.opd.resource.verifikasi-resource.pages.data-sudah-progres';
    
    public function getTitle(): string
    {
        return 'Data Sudah Progres - Lolos Verifikasi';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rombongan::query()
                    ->where('status_pengiriman', 'Data Sudah Progres')
                    ->where('nama_opd', auth()->user()->opd_code)
                    ->orderBy('tanggal_verifikasi', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'Total: ' . $record->total_items . ' item'),
                    
                Tables\Columns\TextColumn::make('verification_progress')
                    ->label('Status Verifikasi')
                    ->getStateUsing(fn($record) => '✓ 100% Lolos')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('keterangan_verifikasi')
                    ->label('Catatan Verifikator')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn($record) => $record->keterangan_verifikasi),
                    
                Tables\Columns\TextColumn::make('tanggal_verifikasi')
                    ->label('Lolos Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('verifikator.name')
                    ->label('Verifikator')
                    ->placeholder('-'),
                    
                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->actions([
                // Tables\Actions\Action::make('lihat_detail')
                //     ->label('Lihat Detail')
                //     ->icon('heroicon-o-eye')
                //     ->color('success')
                //     ->modalHeading(fn($record) => 'Detail: ' . $record->nama_rombongan)
                //     ->modalContent(fn($record) => view('filament.opd.components.detail-verifikasi-lolos', ['record' => $record]))
                //     ->modalSubmitAction(false)
                //     ->modalCancelActionLabel('Tutup'),
                    
                Tables\Actions\Action::make('kirim_ke_data_akhir')
                    ->label('Kirim ke Data Akhir')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim ke Data Akhir')
                    ->modalDescription('Data sudah lolos verifikasi 100%. Kirim ke Data Akhir?')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function ($record) {
                        $record->update([
                            'status_pengiriman' => 'Data Akhir',
                            'tanggal_finalisasi' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('✅ Berhasil')
                            ->body('Data "' . $record->nama_rombongan . '" telah dikirim ke Data Akhir.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Tidak ada data yang sudah lolos verifikasi.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}