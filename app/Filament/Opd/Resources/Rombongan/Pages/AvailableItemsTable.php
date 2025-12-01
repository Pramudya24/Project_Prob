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
use App\Models\Rombongan;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;

class AvailableItemsTable extends BaseWidget
{
    public int $rombonganId;

    protected static ?string $heading = 'Data Tersedia';

    protected int | string | array $columnSpan = 'full';

    // Mapping type alias ke class name
    protected array $typeMap = [
        'pl' => Pl::class,
        'tender' => Tender::class,
        'epurcasing' => Epurcasing::class,
        'swakelola' => Swakelola::class,
        'nontender' => Nontender::class,
        'pengadaan_darurat' => PengadaanDarurat::class,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(Pl::query()->whereRaw('1=0'))
            ->columns($this->getTableColumns())
            ->emptyStateHeading('Tidak ada data tersedia')
            ->emptyStateDescription('Silakan tambahkan data terlebih dahulu.')
            ->emptyStateIcon('heroicon-o-document');
    }

    protected function loadRecords(): Collection
    {
        // ✅ Ambil SEMUA item yang sudah ada di SEMUA rombongan
        $existingItems = \App\Models\RombonganItem::all()
            ->map(fn($item) => $item->item_type . '_' . $item->item_id)
            ->toArray();

        $collection = collect();

        $models = [
            ['class' => Pl::class, 'alias' => 'pl', 'label' => 'PL', 'color' => 'success'],
            ['class' => Tender::class, 'alias' => 'tender', 'label' => 'Tender', 'color' => 'primary'],
            ['class' => Epurcasing::class, 'alias' => 'epurcasing', 'label' => 'E-Purchasing', 'color' => 'info'],
            ['class' => Swakelola::class, 'alias' => 'swakelola', 'label' => 'Swakelola', 'color' => 'warning'],
            ['class' => Nontender::class, 'alias' => 'nontender', 'label' => 'Non Tender', 'color' => 'danger'],
            ['class' => PengadaanDarurat::class, 'alias' => 'pengadaan_darurat', 'label' => 'Pengadaan Darurat', 'color' => 'gray'],
        ];

        foreach ($models as $config) {
            $modelClass = $config['class'];
            
            if (!class_exists($modelClass)) continue;

            try {
                $items = $modelClass::all();
                
                foreach ($items as $item) {
                    // ✅ Skip jika sudah ada di ROMBONGAN MANA PUN
                    $itemKey = $modelClass . '_' . $item->id;
                    if (in_array($itemKey, $existingItems)) {
                        continue;
                    }

                    $collection->push([
                        'original_id' => $item->id,
                        'type_alias' => $config['alias'],
                        'item_label' => $config['label'],
                        'item_color' => $config['color'],
                        'nama_pekerjaan' => $item->nama_pekerjaan ?? '-',
                        'kode_rup' => $item->kode_rup ?? '-',
                        'pagu_rup' => $item->pagu_rup ?? 0,
                        'nilai_kontrak' => $item->nilai_kontrak ?? 0,
                        'created_at' => $item->created_at ?? now(),
                    ]);
                }

            } catch (\Exception $e) {
                continue;
            }
        }

        return $collection;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.widgets.available-items-table', [
            'records' => $this->loadRecords(),
            'rombonganId' => $this->rombonganId,
        ]);
    }

    /**
     * Action untuk tambah ke rombongan
     */
    public function addToRombongan(string $typeAlias, int $itemId): void
    {
        // Convert alias ke full class name
        $itemType = $this->typeMap[$typeAlias] ?? null;

        if (!$itemType) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Tipe item tidak valid: ' . $typeAlias)
                ->danger()
                ->send();
            return;
        }

        $rombongan = Rombongan::find($this->rombonganId);

        if (!$rombongan) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Rombongan tidak ditemukan')
                ->danger()
                ->send();
            return;
        }

        try {
            $result = $rombongan->addItem($itemType, $itemId);

            if ($result) {
                \Filament\Notifications\Notification::make()
                    ->title('Berhasil!')
                    ->body('Data berhasil ditambahkan ke rombongan')
                    ->success()
                    ->send();

                // Dispatch ke parent page (EditRombongan) untuk refresh semua
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
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('item_label')->label('Jenis Data'),
            Tables\Columns\TextColumn::make('nama_pekerjaan')->label('Nama Pekerjaan'),
            Tables\Columns\TextColumn::make('kode_rup')->label('Kode RUP'),
            Tables\Columns\TextColumn::make('pagu_rup')->label('Pagu RUP'),
            Tables\Columns\TextColumn::make('nilai_kontrak')->label('Nilai Kontrak'),
            Tables\Columns\TextColumn::make('created_at')->label('Dibuat'),
        ];
    }

    protected function getListeners(): array
    {
        return [
            'refreshAvailableItems' => '$refresh',
            'refreshRombonganItems' => '$refresh',
        ];
    }
}