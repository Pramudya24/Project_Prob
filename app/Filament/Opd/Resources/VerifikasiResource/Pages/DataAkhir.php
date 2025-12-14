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

class DataAkhir extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = VerifikasiResource::class;
    
    protected static string $view = 'filament.opd.resource.verifikasi-resource.pages.data-akhir';
    
    public function getTitle(): string
    {
        return 'Data Akhir - Final';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rombongan::query()
                    ->where('status_pengiriman', 'Data Akhir')
                    ->where('nama_opd', auth()->user()->opd_code)
                    ->orderBy('tanggal_verifikasi', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'Total: ' . $record->total_items . ' item'),
                    
                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn() => '✓ Final'),
                    
                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal_verifikasi')
                    ->label('Lolos Verifikasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('verifikator.name')
                    ->label('Verifikator')
                    ->placeholder('-'),
            ])
            ->actions([
                // Tables\Actions\Action::make('lihat_detail')
                //     ->label('Lihat Detail')
                //     ->icon('heroicon-o-eye')
                //     ->color('primary')
                //     ->modalHeading(fn($record) => 'Detail Final: ' . $record->nama_rombongan)
                //     ->modalContent(fn($record) => view('filament.opd.components.detail-data-akhir', ['record' => $record]))
                //     ->modalSubmitAction(false)
                //     ->modalCancelActionLabel('Tutup'),
                    
                // Tables\Actions\Action::make('export_pdf')
                //     ->label('Export PDF')
                //     ->icon('heroicon-o-document-arrow-down')
                //     ->color('danger')
                //     ->action(function ($record) {
                //         // Implementasi export PDF
                //         Notification::make()
                //             ->title('PDF sedang diproses')
                //             ->body('File akan diunduh sebentar lagi.')
                //             ->info()
                //             ->send();
                //     }),
            ])
            ->emptyStateHeading('Tidak Ada Data Final')
            ->emptyStateDescription('Belum ada data yang mencapai tahap akhir.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }
}