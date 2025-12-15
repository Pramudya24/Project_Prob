<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use Filament\Infolists\Infolist;
use Filament\Forms\Components\Card;
use Filament\Infolists\Components\Tabs;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Opd\Resources\Rombongan\RombonganResource;

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
            $schema[] = Section::make('Data ' . ($index + 1) . ' - ' . $item['nama_pekerjaan'])
                ->schema([
                    TextEntry::make("item_{$index}_type")
                        ->label('Jenis')
                        ->state($this->getTypeLabel($item['type']))
                        ->badge()
                        ->color($this->getTypeColor($item['type'])),

                    TextEntry::make("item_{$index}_nama")
                        ->label('Nama Pekerjaan')
                        ->state($item['nama_pekerjaan']),

                    TextEntry::make("item_{$index}_kode_rup")
                        ->label('Kode RUP')
                        ->state($item['kode_rup']),

                    TextEntry::make("item_{$index}_pagu")
                        ->label('Pagu RUP')
                        ->state('Rp ' . number_format($item['pagu_rup'], 0, ',', '.')),

                    TextEntry::make("item_{$index}_nilai")
                        ->label('Nilai Kontrak')
                        ->state($item['nilai_kontrak'] ? 'Rp ' . number_format($item['nilai_kontrak'], 0, ',', '.') : '-'),

                    TextEntry::make("item_{$index}_jenis")
                        ->label('Jenis Pengadaan')
                        ->state($item['jenis_pengadaan'] ?? '-'),
                ])
                ->columns(3)
                ->collapsible();
        }

        return $schema;
    }

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'pl' => 'PL',
            'tender' => 'Tender',
            'epurcasing' => 'E-Purchasing',
            'swakelola' => 'Swakelola',
            'nontender' => 'Pencatatan Non Tender',
            'pengadaan_darurat' => 'Pengadaan Darurat',
            default => $type
        };
    }

    private function getTypeColor(string $type): string
    {
        return match ($type) {
            'pl' => 'success',
            'tender' => 'danger',
            'epurcasing' => 'info',
            'swakelola' => 'primary',
            'nontender' => 'gray',
            'pengadaan_darurat' => 'warning',
            default => 'secondary'
        };
    }
}