<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Pl;
use App\Models\Tender;
use App\Models\Epurcasing;
use App\Models\Swakelola;
use App\Models\Nontender;
use App\Models\PengadaanDarurat;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AvailableItemsTable extends BaseWidget
{
    public int $rombonganId;

    protected static ?string $heading = 'Data Tersedia';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $rombongan = \App\Models\Rombongan::find($this->rombonganId);

        if (!$rombongan) {
            return $table
                ->query(Pl::query()->whereRaw('1=0'))
                ->columns($this->getTableColumns())
                ->emptyStateHeading('Rombongan tidak ditemukan');
        }

        return $table
            ->query(Pl::query()->whereRaw('1=0'))
            ->columns($this->getTableColumns())
            ->actions([
                Tables\Actions\Action::make('add_to_rombongan')
                    ->label('+ Tambah ke Rombongan')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->action(function (Model $record) use ($rombongan) {
                        try {
                            $result = $rombongan->addItem($record->item_type, $record->original_id);

                            if ($result) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Berhasil!')
                                    ->body('Data berhasil ditambahkan ke rombongan')
                                    ->success()
                                    ->send();

                                $this->dispatch('refreshRombonganItems');
                                $this->dispatch('refreshAvailableItems');
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Peringatan')
                                    ->body('Data sudah ada dalam rombongan')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Tambahkan ke Rombongan')
                    ->modalDescription(fn (Model $record) => "Tambahkan data \"{$record->nama_pekerjaan}\" ke rombongan?")
                    ->modalSubmitActionLabel('Ya, Tambahkan'),
            ])
            ->emptyStateHeading('Tidak ada data tersedia')
            ->emptyStateDescription('Silakan tambahkan data terlebih dahulu di halaman masing-masing form.')
            ->emptyStateIcon('heroicon-o-document');
    }

    /**
     * SOLUSI: Gunakan model Pl sebagai base untuk semua data
     */
    public function getTableRecords(): Collection 
    {
        $collection = new Collection();
        $uniqueId = 1;

        $models = [
            ['class' => Pl::class, 'label' => 'PL', 'color' => 'success'],
            ['class' => Tender::class, 'label' => 'Tender', 'color' => 'primary'],
            ['class' => Epurcasing::class, 'label' => 'E-Purchasing', 'color' => 'info'],
            ['class' => Swakelola::class, 'label' => 'Swakelola', 'color' => 'warning'],
            ['class' => Nontender::class, 'label' => 'Non Tender', 'color' => 'danger'],
            ['class' => PengadaanDarurat::class, 'label' => 'Pengadaan Darurat', 'color' => 'gray'],
        ];

        foreach ($models as $config) {
            $modelClass = $config['class'];
            
            if (!class_exists($modelClass)) continue;

            try {
                $items = $modelClass::all();
                
                foreach ($items as $item) {
                    // Buat instance Pl sebagai base model
                    $fakeModel = new Pl();
                    
                    // Set properties manual
                    $fakeModel->id = $uniqueId++; // ID UNIK
                    $fakeModel->original_id = $item->id; // ID asli untuk addItem
                    $fakeModel->nama_pekerjaan = $item->nama_pekerjaan ?? '-';
                    $fakeModel->kode_rup = $item->kode_rup ?? '-';
                    $fakeModel->pagu_rup = $item->pagu_rup ?? 0;
                    $fakeModel->nilai_kontrak = $item->nilai_kontrak ?? 0;
                    $fakeModel->created_at = $item->created_at ?? now();
                    $fakeModel->item_type = $modelClass;
                    $fakeModel->item_label = $config['label'];
                    $fakeModel->item_color = $config['color'];
                    
                    $collection->add($fakeModel);
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        return $collection;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('item_label')
                ->label('Jenis Data')
                ->badge()
                ->color(fn ($record) => $this->getTypeColor($record->item_type)),

            Tables\Columns\TextColumn::make('nama_pekerjaan')
                ->label('Nama Pekerjaan')
                ->searchable()
                ->limit(50),

            Tables\Columns\TextColumn::make('kode_rup')
                ->label('Kode RUP')
                ->searchable(),

            Tables\Columns\TextColumn::make('pagu_rup')
                ->label('Pagu RUP')
                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),

            Tables\Columns\TextColumn::make('nilai_kontrak')
                ->label('Nilai Kontrak')
                ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-'),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d/m/Y H:i'),
        ];
    }

    protected function getTypeColor(string $type): string
    {
        return match ($type) {
            'App\Models\Pl' => 'success',
            'App\Models\Tender' => 'primary',
            'App\Models\Epurcasing' => 'info',
            'App\Models\Swakelola' => 'warning',
            'App\Models\Nontender' => 'danger',
            'App\Models\PengadaanDarurat' => 'gray',
            default => 'gray'
        };
    }

    protected function getListeners(): array
    {
        return [
            'refreshAvailableItems' => '$refresh',
            'refreshRombonganItems' => '$refresh',
        ];
    }
}