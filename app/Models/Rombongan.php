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

    public function getRombonganItemsData()
    {
        $items = [];
        $rombonganItems = $this->rombonganItems()->with('item')->get();

        foreach ($rombonganItems as $rombonganItem) {
            $item = $rombonganItem->item;

            if ($item) {
                $items[] = [
                    'type' => $this->getSimpleType($rombonganItem->item_type), // type sederhana
                    'id' => $item->id,
                    'nama_pekerjaan' => $item->nama_pekerjaan ?? '-',
                    'kode_rup' => $item->kode_rup ?? '-',
                    'pagu_rup' => $item->pagu_rup ?? 0,
                    'nilai_kontrak' => $item->nilai_kontrak ?? 0,
                    'jenis_pengadaan' => $item->jenis_pengadaan ?? '-',
                ];
            }
        }

        return $items;
    }

    // Tambahkan method helper ini:
    private function getSimpleType($itemType): string
    {
        return match ($itemType) {
            'App\Models\Pl' => 'pl',
            'App\Models\Tender' => 'tender',
            'App\Models\Epurcasing' => 'epurcasing',
            'App\Models\Swakelola' => 'swakelola',
            'App\Models\Nontender' => 'nontender',
            'App\Models\PengadaanDarurat' => 'pengadaan_darurat',
            default => 'unknown'
        };
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

    private function getTypeLabel($type): string
    {
        return match ($type) {
            'App\Models\Pl' => 'PL',
            'App\Models\Tender' => 'Tender',
            'App\Models\Epurcasing' => 'E-Purchasing',
            'App\Models\Swakelola' => 'Swakelola',
            'App\Models\nontender' => 'nontender',
            'App\Models\PengadaanDarurat' => 'PengadaanDarurat',
            default => class_basename($type)
        };
    }

    // Di Model Rombongan
    public function getItemsWithData()
    {
        return $this->items->map(function ($item) {
            return [
                'type' => $item->pivot->item_type,
                'id' => $item->pivot->item_id,
                // Data lainnya akan di-fetch real-time di getItemsSchema
            ];
        })->toArray();
    }

    
}