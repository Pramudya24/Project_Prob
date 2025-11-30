<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rombongan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_rombongan',
        'nama_opd',
        'status_verifikasi',
        'keterangan_verifikasi',
        'verifikator_id',
        'tanggal_verifikasi',
        'lolos_verif',
        'status_pengiriman',
    ];

    protected $casts = [
        'tanggal_verifikasi' => 'datetime',
        'lolos_verif' => 'boolean',
    ];


    // Relasi ke verifikator (User)
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }

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
        return $this->getItemsByType('App\Models\Nontender');
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
        $existing = $this->RombonganItems()
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->exists();

        if ($existing) {
            return false;
        }

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
                    'type' => $this->getSimpleType($rombonganItem->item_type),
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
            'App\Models\Nontender' => 'Nontender',
            'App\Models\PengadaanDarurat' => 'PengadaanDarurat',
            default => class_basename($type)
        };
    }

    public function getItemsWithData()
    {
        return $this->items->map(function ($item) {
            return [
                'type' => $item->pivot->item_type,
                'id' => $item->pivot->item_id,
            ];
        })->toArray();
    }

    // Method untuk validasi otomatis "Lolos Verif"
    public function checkAutoValidation(): bool
    {
        $rombonganItems = $this->RombonganItems()->with('item')->get();

        // Jika tidak ada item, tidak lolos
        if ($rombonganItems->isEmpty()) {
            return false;
        }

        foreach ($rombonganItems as $rombonganItem) {
            // Cek apakah item sudah diverifikasi (dicentang)
            if (!$rombonganItem->is_verified) {
                return false;
            }

            $item = $rombonganItem->item;

            if (!$item) {
                return false;
            }

            // Cek apakah semua field penting sudah terisi
            $requiredFields = [
                'nama_pekerjaan',
                'kode_rup',
                'pagu_rup',
                'nilai_kontrak',
                'jenis_pengadaan'
            ];

            foreach ($requiredFields as $field) {
                if (empty($item->$field)) {
                    return false;
                }
            }
        }

        return true;
    }

    // Method untuk mendapatkan items yang dikelompokkan berdasarkan type
    public function getGroupedItems()
    {
        $items = $this->RombonganItems()->with('item')->get();
        $grouped = [];

        foreach ($items as $rombonganItem) {
            $type = $this->getSimpleType($rombonganItem->item_type);
            $typeLabel = $this->getTypeLabel($rombonganItem->item_type);

            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'label' => $typeLabel,
                    'items' => [],
                ];
            }

            $item = $rombonganItem->item;
            if ($item) {
                $grouped[$type]['items'][] = [
                    'rombongan_item_id' => $rombonganItem->id,
                    'item_id' => $item->id,
                    'is_verified' => $rombonganItem->is_verified,
                    'keterangan_item' => $rombonganItem->keterangan_item,
                    'data' => $item->toArray(),
                ];
            }
        }

        return $grouped;
    }

    // Method untuk menghitung progress verifikasi per type
    public function getVerificationProgress()
    {
        $items = $this->RombonganItems()->with('fieldVerifications')->get();

        $totalFields = 0;
        $verifiedFields = 0;

        foreach ($items as $item) {
            $progress = $item->getVerificationProgress();
            $totalFields += $progress['total'];
            $verifiedFields += $progress['verified'];
        }

        return [
            'total' => $totalFields,
            'verified' => $verifiedFields,
            'percentage' => $totalFields > 0 ? round(($verifiedFields / $totalFields) * 100) : 0,
        ];
    }

    // Helper untuk convert simple type ke full namespace
    private function getFullType($simpleType): string
    {
        return match ($simpleType) {
            'pl' => 'App\Models\Pl',
            'tender' => 'App\Models\Tender',
            'epurcasing' => 'App\Models\Epurcasing',
            'swakelola' => 'App\Models\Swakelola',
            'nontender' => 'App\Models\Nontender',
            'pengadaan_darurat' => 'App\Models\PengadaanDarurat',
            default => ''
        };
    }

    public function getGroupedItemsWithFields()
    {
        $items = $this->RombonganItems()->with(['item', 'fieldVerifications'])->get();
        $grouped = [];

        foreach ($items as $rombonganItem) {
            $type = $this->getSimpleType($rombonganItem->item_type);
            $typeLabel = $this->getTypeLabel($rombonganItem->item_type);

            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'label' => $typeLabel,
                    'items' => [],
                ];
            }

            $item = $rombonganItem->item;
            if ($item) {
                $progress = $rombonganItem->getVerificationProgress();

                $grouped[$type]['items'][] = [
                    'rombongan_item_id' => $rombonganItem->id,
                    'item_id' => $item->id,
                    'nama_pekerjaan' => $item->nama_pekerjaan ?? 'Tidak ada nama',
                    'progress' => $progress,
                    'all_verified' => $rombonganItem->allFieldsVerified(),
                    'fields' => $rombonganItem->getFieldsWithVerificationStatus(),
                ];
            }
        }

        return $grouped;
    }
}
