<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use App\Filament\Opd\Resources\VerifikasiResource;
use App\Models\Rombongan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Actions;

class DataVerifikasi extends Page implements HasTable
{
  use InteractsWithTable;

  protected static string $resource = VerifikasiResource::class;

  protected static string $view = 'filament.pages.data-verifikasi';

  // Auto refresh setiap 5 detik
  protected static ?string $pollingInterval = '5s';

  public function getTitle(): string
  {
    return 'Data Verifikasi';
  }

  public function table(Table $table): Table
  {
    return $table
      ->query(
        Rombongan::query()
          ->whereIn('status_pengiriman', ['Data Progres', 'Data Sudah Progres'])
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
          ->label('Nama OPD')
          ->badge()
          ->color('primary'),

        Tables\Columns\TextColumn::make('status_pengiriman')
          ->label('Status')
          ->badge()
          ->color(fn($state) => match ($state) {
            'Data Progres' => 'danger',
            'Data Sudah Progres' => 'success',
            default => 'gray',
          })
          ->formatStateUsing(fn($state) => match ($state) {
            'Data Progres' => 'Data Progres',
            'Data Sudah Progres' => 'Pembuatan SPM',
            default => $state ?? '-',
          }),

        Tables\Columns\TextColumn::make('no_spm')
          ->label('No. SPM')
          ->placeholder('-')
          ->badge()
          ->color(fn($record) => ($record && $record->no_spm) ? 'success' : 'gray')
          ->visible(fn($record) => $record && $record->status_pengiriman === 'Data Sudah Progres'),

        Tables\Columns\TextColumn::make('tanggal_verifikasi')
          ->label('Tanggal Verifikasi')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->badge()
          ->color(fn($record) => ($record && $record->status_pengiriman === 'Data Progres') ? 'danger' : 'success'),

        Tables\Columns\TextColumn::make('verifikator.name')
          ->label('Dari')
          ->placeholder('-'),
          
        Tables\Columns\TextColumn::make('total_nilai')
          ->label('Total Nilai')
          ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
          ->sortable(),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('status_pengiriman')
          ->label('Filter Status')
          ->options([
            'Data Progres' => 'Data Progres',
            'Data Sudah Progres' => 'Pembuatan SPM',
          ])
          ->placeholder('Semua Data'),
      ])
      ->actions([
        // ACTION UNTUK DATA PROGRES (Perlu Perbaikan)
        Tables\Actions\Action::make('edit')
          ->label('Edit')
          ->icon('heroicon-o-pencil-square')
          ->color('warning')
          ->visible(fn($record) => $record && $record->status_pengiriman === 'Data Progres')
          ->url(fn($record) => $record ? route('filament.opd.resources.verifikasis.edit-data-progres', ['record' => $record->id]) : '#')
          ->tooltip('Edit dan perbaiki data sesuai catatan verifikator'),

        Tables\Actions\Action::make('kirim_ulang')
          ->label('Kirim Ulang')
          ->icon('heroicon-o-paper-airplane')
          ->color('primary')
          ->visible(fn($record) => $record && $record->status_pengiriman === 'Data Progres')
          ->requiresConfirmation()
          ->modalHeading('Kirim Ulang ke Verifikator')
          ->modalDescription('Pastikan data sudah diperbaiki sebelum mengirim ulang.')
          ->modalSubmitActionLabel('Ya, Kirim Ulang')
          ->action(function ($record) {
            if (!$record) return;

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

        // ACTION UNTUK DATA SUDAH PROGRES (Lolos Verifikasi)
        Tables\Actions\Action::make('buat_spm')
          ->label('Buat SPM')
          ->icon('heroicon-o-document-plus')
          ->color('warning')
          ->visible(fn($record) => $record && $record->status_pengiriman === 'Data Sudah Progres')
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
            if (!$record) return;

            try {
              $record->update([
                'no_spm' => $data['no_spm'],
              ]);

              Notification::make()
                ->title('✅ SPM Berhasil Disimpan')
                ->body('Nomor SPM "' . $data['no_spm'] . '" telah disimpan.')
                ->success()
                ->send();

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
          ->color(fn($record) => ($record && !empty($record->no_spm)) ? 'primary' : 'gray')
          ->visible(fn($record) => $record && $record->status_pengiriman === 'Data Sudah Progres')
          ->disabled(fn($record) => !$record || empty($record->no_spm))
          ->tooltip(fn($record) => (!$record || empty($record->no_spm)) ? 'Harap buat SPM terlebih dahulu' : 'Kirim ke Monitoring')
          ->requiresConfirmation()
          ->modalHeading('Kirim ke Monitoring')
          ->modalDescription(fn($record) => 'Data sudah lolos verifikasi 100% dan SPM sudah dibuat (' . $record->no_spm . '). Kirim ke Monitoring?')
          ->modalSubmitActionLabel('Ya, Kirim')
          ->action(function ($record) {
            if (!$record) return;

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
          ->modalDescription('Hanya data yang sudah lolos verifikasi dan memiliki SPM yang akan dikirim.')
          ->action(function ($records) {
            $sent = 0;
            $skipped = 0;

            foreach ($records as $record) {
              if ($record->status_pengiriman === 'Data Sudah Progres' && !empty($record->no_spm)) {
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
                ->body("$sent data berhasil dikirim ke Monitoring." . ($skipped > 0 ? " $skipped data dilewati (belum lolos verifikasi atau belum ada SPM)." : ''))
                ->success()
                ->send();
            } else {
              Notification::make()
                ->title('⚠️ Peringatan')
                ->body('Tidak ada data yang dikirim. Pastikan data sudah lolos verifikasi dan memiliki SPM.')
                ->warning()
                ->send();
            }
          }),
      ])
      ->defaultSort('tanggal_verifikasi', 'desc')
      ->emptyStateHeading('Tidak Ada Data')
      ->emptyStateDescription('Tidak ada data verifikasi.')
      ->emptyStateIcon('heroicon-o-clipboard-document-check');
  }

  public function getTableRecordsPerPageSelectOptions(): array
  {
    return [10, 25, 50, 100];
  }

  protected function getListeners(): array
  {
    return [
      'refreshDataVerifikasi' => '$refresh',
    ];
  }

  protected function getHeaderActions(): array
  {
    return [
      \Filament\Actions\Action::make('history')
        ->label('History')
        ->color('gray')
        ->icon('heroicon-o-archive-box')
        ->url(fn() => VerifikasiResource::getUrl('data-akhir')),
    ];
  }
}
