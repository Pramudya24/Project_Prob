<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rombongan extends Model
{
    use HasFactory;

    protected $fillable = [
    'nama_rombongan',
    'keterangan', 
    'status',
];

protected $casts = [
    'status' => 'boolean',
];

    // Relasi ke rombongan_items
    public function RombonganItems(): HasMany
    {
        return $this->hasMany(RombonganItem::class);
    }

    // Dynamic method untuk berbagai jenis item
    public function getItemsByType($type)
    {
        return $this->RombonganItems()->where('item_type', $type)->get();
    }

    // app/Models/Rombongan.php
public function items()
{
    return $this->hasMany(RombonganItem::class);
}

    // Convenience methods
    public function pls()
    {
        return $this->getItemsByType('App\Models\Pl');
    }
    public function nontenders()
    {
        return $this->getItemsByType('App\Models\nontender');
    }
    public function pengadaan_darurats()
    {
        return $this->getItemsByType('App\Models\PengadaanDarurat');
    }

    public function tenders()
    {
        return $this->getItemsByType('App\Models\Tender');
    }

    public function epurcasings()
    {
        return $this->getItemsByType('App\Models\Epurcasing');
    }

    public function swakelolas()
    {
        return $this->getItemsByType('App\Models\Swakelola');
    }

    // Method untuk menambahkan item ke rombongan
    // Method untuk menambahkan item ke rombongan
public function addItem($itemType, $itemId)
{
    // Cek apakah sudah ada
    $existing = $this->RombonganItems()
        ->where('item_type', $itemType)
        ->where('item_id', $itemId)
        ->exists();

    if ($existing) {
        return false; // Sudah ada
    }

    // Tambahkan item
    return $this->RombonganItems()->create([
        'item_type' => $itemType,
        'item_id' => $itemId,
    ]);
}

    // Method untuk menghapus item dari rombongan
    public function removeItem($itemType, $itemId)
    {
        return $this->RombonganItems()
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->delete();
    }

    // Method untuk data items
    // Method untuk data items - DENGAN DEBUG
    // Method untuk data items - VERSI BERSIH
        public function getRombonganItemsData()
        {
            $items = [];
            $RombonganItems = $this->RombonganItems()->with('item')->get();

            foreach ($RombonganItems as $RombonganItem) {
                $item = $RombonganItem->item;
                
                if ($item) {
                    $tanggal = $RombonganItem->created_at ?? now();
                    
                    $items[] = [
                        'id' => (int) $RombonganItem->id,
                        'item_id' => (int) $item->id,
                        'type' => $this->getTypeLabel($RombonganItem->item_type),
                        'nama_pekerjaan' => (string) ($item->nama_pekerjaan ?? '-'),
                        'kode_rup' => (string) ($item->kode_rup ?? '-'),
                        'pagu_rup' => (float) ($item->pagu_rup ?? 0),
                        'nilai_kontrak' => (float) ($item->nilai_kontrak ?? 0),
                        'tanggal' => (string) $tanggal->format('d/m/Y H:i'),
                        'item_type' => (string) $RombonganItem->item_type,
                    ];
                }
            }

            // Sort by ID descending
            usort($items, function($a, $b) {
                return $b['id'] - $a['id'];
            });

            return $items;
        }

    // Helper method untuk total items
    public function getTotalItemsAttribute()
    {
        return $this->RombonganItems()->count();
    }

    // Helper method untuk total nilai
    public function getTotalNilaiAttribute()
    {
        $total = 0;
        $RombonganItems = $this->RombonganItems()->with('item')->get();
        
        foreach ($RombonganItems as $RombonganItem) {
            if ($RombonganItem->item && isset($RombonganItem->item->nilai_kontrak)) {
                $total += $RombonganItem->item->nilai_kontrak;
            }
        }
        
        return $total;
    }

    // Helper untuk label type
    private function getTypeLabel($type): string
    {
        return match($type) {
            'App\Models\Pl' => 'PL',
            'App\Models\Tender' => 'Tender',
            'App\Models\Epurcasing' => 'E-Purchasing',
            'App\Models\Swakelola' => 'Swakelola',
            'App\Models\nontender' => 'nontender',
            'App\Models\PengadaanDarurat' => 'PengadaanDarurat',
            default => class_basename($type)
        };
    }
}