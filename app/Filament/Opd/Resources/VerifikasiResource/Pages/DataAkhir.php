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

class DataAkhir extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = VerifikasiResource::class;
    
    protected static string $view = 'filament.opd.resource.verifikasi-resource.pages.data-akhir';
    
    public function getTitle(): string
    {
        return 'History';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rombongan::query()
                    ->where('status_pengiriman', 'Data Akhir')
                    ->where('nama_opd', auth()->user()->opd_code)
                    ->orderBy('tanggal_kirim_monitoring', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'Total: ' . $record->total_items . ' item'),
                    
                Tables\Columns\TextColumn::make('no_spm')
                    ->label('No. SPM')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('status_monitoring')
                    ->label('Status')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn($record) => $record->is_sent_to_monitoring ? 'Terkirim ke Monitoring' : 'Final'),
                    
                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('tanggal_kirim_monitoring')
                    ->label('Tanggal Kirim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Detail: ' . $record->nama_rombongan)
                    ->modalContent(fn($record) => view('filament.opd.components.detail-rombongan', ['record' => $record]))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->slideOver(),
            ])
            ->emptyStateHeading('Tidak Ada Data Final')
            ->emptyStateDescription('Belum ada data yang mencapai tahap akhir.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }
}