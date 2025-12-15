<?php

namespace App\Filament\Opd\Resources\Rombongan\Pages;

use Filament\Widgets\Widget;
use App\Models\Pl;
use App\Models\Tender;
use App\Models\Epurcasing;
use App\Models\Swakelola;
use App\Models\nontender;
use App\Models\PengadaanDarurat;
use App\Models\Rombongan;
use Illuminate\Support\Collection;

class AvailableItemsTable extends Widget
{
    protected static string $view = 'filament.widgets.available-items-table';
    public int $rombonganId;

    // Mapping type alias ke class name
    protected array $typeMap = [
        'pl' => Pl::class,
        'tender' => Tender::class,
        'epurcasing' => Epurcasing::class,
        'swakelola' => Swakelola::class,
        'nontender' => Nontender::class,
        'pengadaan_darurat' => PengadaanDarurat::class,
    ];  

    protected function loadRecords(): Collection
    {
        // ✅ Ambil SEMUA item yang sudah ada di SEMUA rombongan
        $existingItems = \App\Models\RombonganItem::all()
            ->map(fn($item) => $item->item_type . '_' . $item->item_id)
            ->toArray();

        $collection = collect();

        $models = [
            ['class' => Pl::class, 'alias' => 'pl', 'label' => 'Non Tender', 'color' => 'success'],
            ['class' => Tender::class, 'alias' => 'tender', 'label' => 'Tender', 'color' => 'danger'],
            ['class' => Epurcasing::class, 'alias' => 'epurcasing', 'label' => 'EPurcasing', 'color' => 'info'],
            ['class' => Swakelola::class, 'alias' => 'swakelola', 'label' => 'Pencatatan Swakelola', 'color' => 'primary'],
            ['class' => Nontender::class, 'alias' => 'nontender', 'label' => 'Pencatatan Non Tender', 'color' => 'gray'],
            ['class' => PengadaanDarurat::class, 'alias' => 'pengadaan_darurat', 'label' => 'Pencatatan Pengadaan Darurat', 'color' => 'warning'],
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
            // ✅ REFRESH KEDUA TABLE
            $this->dispatch('refreshRombonganItems');
            $this->dispatch('refreshAvailableItems');
            
            // ✅ NOTIFIKASI
            \Filament\Notifications\Notification::make()
                ->title('Berhasil!')
                ->body('Data berhasil ditambahkan ke rombongan')
                ->success()
                ->send();
                $this->js('window.location.reload()');
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

    protected function getListeners(): array
    {
        return [
            'refreshAvailableItems' => '$refresh',
            'refreshRombonganItems' => '$refresh',
        ];
    }

    protected function getViewData(): array
    {
        return [
            'records' => $this->loadRecords(),
            'rombonganId' => $this->rombonganId,
        ];
    }
}

