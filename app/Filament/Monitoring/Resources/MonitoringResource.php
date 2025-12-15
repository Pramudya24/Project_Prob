<?php

namespace App\Filament\Monitoring\Resources;

use App\Filament\Monitoring\Resources\MonitoringResource\Pages;
use App\Models\Rombongan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonitoringResource extends Resource
{
    protected static ?string $model = Rombongan::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Data Monitoring';
    
    protected static ?string $modelLabel = 'Data Monitoring';
    
    protected static ?string $pluralModelLabel = 'Data Monitoring';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_rombongan')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Rombongan::query()
                    ->where('status_pengiriman', 'Data Akhir')
                    ->where('is_sent_to_monitoring', true)
                    ->orderBy('tanggal_kirim_monitoring', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_rombongan')
                    ->label('Nama Rombongan')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'OPD: ' . $record->nama_opd),
                    
                Tables\Columns\TextColumn::make('no_spm')
                    ->label('No. SPM')
                    ->searchable()
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
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nama_opd')
                    ->label('Filter OPD')
                    ->options(function () {
                        return Rombongan::where('is_sent_to_monitoring', true)
                            ->distinct()
                            ->pluck('nama_opd', 'nama_opd');
                    })
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn($record) => 'Detail: ' . $record->nama_rombongan)
                    ->modalContent(fn($record) => view('filament.monitoring.components.detail-rombongan', ['record' => $record]))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->slideOver(),
            ])
            ->bulkActions([
                // Bisa tambahkan bulk export atau bulk actions lainnya
            ])
            ->emptyStateHeading('Tidak Ada Data')
            ->emptyStateDescription('Belum ada data yang dikirim dari OPD.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitorings::route('/'),
        ];
    }
    
    // Disable global scope OPD filter untuk role Monitoring
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope('opd_filter');
    }
}
