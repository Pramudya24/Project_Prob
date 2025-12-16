<?php

namespace App\Filament\Monitoring\Resources;

use App\Filament\Monitoring\Resources\MonitoringResource\Pages;
use App\Models\Rombongan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MonitoringResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Data Monitoring';
    protected static ?string $modelLabel = 'Data Monitoring';
    protected static ?string $pluralModelLabel = 'Data Monitoring';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'OPD: ' . $record->nama_opd),

                Tables\Columns\TextColumn::make('no_spm')
                    ->label('No. SPM')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Item')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_kirim_monitoring')
                    ->label('Tanggal Terima')
                    ->dateTime('d/m/Y H:i')
                    ->badge()
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')

                    // Judul modal
                    ->modalHeading(fn($record) => 'Detail Rombongan: ' . $record->nama_rombongan)

                    // ISI MODAL → VIEW BLADE
                    ->modalContent(
                        fn($record) =>
                        view('filament.monitoring.components.detail-rombongan', [
                            'record' => $record,
                        ])
                    )

                    // Style modal
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->slideOver(),
            ])
            ->emptyStateHeading('Pilih OPD')
            ->emptyStateDescription('Pilih OPD lalu klik tombol Cari untuk menampilkan data.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitorings::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('opd_filter');
    }
}
