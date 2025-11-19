<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use App\Filament\Opd\Resources\Rombongan\RombonganResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewRombongan extends ViewRecord
{
    protected static string $resource = RombonganResource::class;

    protected ?string $heading = 'Detail Rombongan';

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;
        $itemsData = $record->getRombonganItemsData();

        return $infolist
            ->schema([
                Tabs::make('Rombongan')
                    ->tabs([
                        Tab::make('Informasi Rombongan')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Detail Rombongan')
                                    ->description('Informasi dasar rombongan')
                                    ->schema([
                                        TextEntry::make('nama_rombongan')
                                            ->label('Nama Rombongan'),
                                            
                                        TextEntry::make('created_at')
                                            ->label('Dibuat Tanggal')
                                            ->dateTime('d/m/Y H:i'),
                                    ])
                                    ->columns(2),
                            ]),
                            
                        Tab::make('Data dalam Rombongan')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Items dalam Rombongan')
                                    ->description('Data pekerjaan yang termasuk dalam rombongan ini')
                                    ->schema([
                                        // Tampilkan info summary
                                        TextEntry::make('total_items_info')
                                            ->label('Total Data')
                                            ->state($record->total_items)
                                            ->badge()
                                            ->color('success'),
                                            
                                        TextEntry::make('total_nilai_info')
                                            ->label('Total Nilai')
                                            ->state('Rp ' . number_format($record->total_nilai, 0, ',', '.'))
                                            ->color('success'),
                                            
                                        // Tampilkan data dalam bentuk simple list
                                        ...$this->getItemsSchema($itemsData),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function getItemsSchema(array $items): array
    {
        if (empty($items)) {
            return [
                TextEntry::make('no_data')
                    ->label('')
                    ->state('Belum ada data dalam rombongan')
                    ->color('gray'),
            ];
        }

        $schema = [];
        foreach ($items as $index => $item) {
            $schema[] = Section::make('Data ' . ($index + 1))
                ->schema([
                    TextEntry::make("item_{$index}_type")
                        ->label('Jenis')
                        ->state($item['type'])
                        ->badge()
                        ->color(match($item['type']) {
                            'PL' => 'success',
                            'Tender' => 'primary',
                            'E-Purchasing' => 'info',
                            'Swakelola' => 'warning',
                            'nontender' => 'warning',
                            'PengadaanDarurat' => 'warning',
                            default => 'secondary'
                        }),
                        
                    TextEntry::make("item_{$index}_nama")
                        ->label('Nama Pekerjaan')
                        ->state($item['nama_pekerjaan']),
                        
                    TextEntry::make("item_{$index}_nilai")
                        ->label('Nilai Kontrak')
                        ->state('Rp ' . number_format($item['nilai_kontrak'], 0, ',', '.')),
                ])
                ->columns(3)
                ->collapsible();
        }

        return $schema;
    }
}