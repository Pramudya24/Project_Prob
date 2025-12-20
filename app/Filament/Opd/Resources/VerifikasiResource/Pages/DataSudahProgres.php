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
use Filament\Forms;

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
                    ->getStateUsing(fn($record) => 'Lolos')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('no_spm')
                    ->label('No. SPM')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn($record) => $record->no_spm ? 'success' : 'gray'),
                    
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
                    
                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('buat_spm')
                    ->label('Buat SPM')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('no_spm')
                            ->label('Nomor SPM')
                            ->placeholder('Contoh: SPM/2024/001')
                            ->required()
                            ->maxLength(100)
                            ->default(fn($record) => $record->no_spm)
                            ->helperText('Masukkan nomor SPM untuk rombongan ini'),
                    ])
                    ->fillForm(fn($record) => [
                        'no_spm' => $record->no_spm,
                    ])
                    ->modalHeading(fn($record) => $record->no_spm ? 'Edit SPM' : 'Buat SPM')
                    ->modalDescription(fn($record) => ($record->no_spm ? 'Edit' : 'Buat') . ' nomor SPM untuk: ' . $record->nama_rombongan)
                    ->modalSubmitActionLabel(fn($record) => $record->no_spm ? 'Update SPM' : 'Simpan SPM')
                    ->action(function ($record, array $data) {
                        try {
                            $record->update([
                                'no_spm' => $data['no_spm'],
                            ]);
                            
                            Notification::make()
                                ->title('✅ SPM Berhasil Disimpan')
                                ->body('Nomor SPM "' . $data['no_spm'] . '" telah disimpan.')
                                ->success()
                                ->send();
                                
                            // Refresh table
                            $this->dispatch('$refresh');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Gagal Menyimpan')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('kirim_ke_monitoring')
                    ->label('Kirim ke Monitoring')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color(fn($record) => !empty($record->no_spm) ? 'primary' : 'gray')
                    ->disabled(fn($record) => empty($record->no_spm))
                    ->tooltip(fn($record) => empty($record->no_spm) ? 'Harap buat SPM terlebih dahulu' : 'Kirim ke Monitoring')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim ke Monitoring')
                    ->modalDescription(fn($record) => 'Data sudah lolos verifikasi 100% dan SPM sudah dibuat (' . $record->no_spm . '). Kirim ke Monitoring?')
                    ->modalSubmitActionLabel('Ya, Kirim')
                    ->action(function ($record) {
                        try {
                            $record->update([
                                'status_pengiriman' => 'Data Akhir',
                                'is_sent_to_monitoring' => true,
                                'tanggal_kirim_monitoring' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('✅ Berhasil')
                                ->body('Data "' . $record->nama_rombongan . '" telah dikirim ke Data Akhir dan Monitoring.')
                                ->success()
                                ->send();
                                
                            $this->dispatch('$refresh');
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Gagal Mengirim')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('kirim_bulk_ke_monitoring')
                    ->label('Kirim ke Monitoring (Bulk)')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Bulk ke Monitoring')
                    ->modalDescription('Hanya data yang sudah memiliki SPM yang akan dikirim.')
                    ->action(function ($records) {
                        $sent = 0;
                        $skipped = 0;
                        
                        foreach ($records as $record) {
                            if (!empty($record->no_spm)) {
                                $record->update([
                                    'status_pengiriman' => 'Data Akhir',
                                    'is_sent_to_monitoring' => true,
                                    'tanggal_kirim_monitoring' => now(),
                                ]);
                                $sent++;
                            } else {
                                $skipped++;
                            }
                        }
                        
                        if ($sent > 0) {
                            Notification::make()
                                ->title('✅ Berhasil')
                                ->body("$sent data berhasil dikirim ke Monitoring." . ($skipped > 0 ? " $skipped data dilewati (belum ada SPM)." : ''))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('⚠️ Peringatan')
                                ->body('Tidak ada data yang dikirim. Semua data belum memiliki SPM.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Tidak ada data yang sudah lolos verifikasi.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}