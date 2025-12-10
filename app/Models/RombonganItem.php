<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RombonganItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rombongan_id',
        'item_type',
        'item_id',
    ];

    // Relasi ke Rombongan
    public function rombongan(): BelongsTo
    {
        return $this->belongsTo(Rombongan::class);
    }

    // Relasi polymorphic ke item (PL, Tender, dll)
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    // Relasi ke field verifications
    public function fieldVerifications(): HasMany
    {
        return $this->hasMany(RombonganItemFieldVerification::class);
    }

    /**
     * Get field verification for specific field
     */
    public function getFieldVerification(string $fieldName): ?RombonganItemFieldVerification
    {
        return $this->fieldVerifications()
            ->where('field_name', $fieldName)
            ->first();
    }

    /**
     * Get atau create field verification
     */
    public function getOrCreateFieldVerification(string $fieldName): RombonganItemFieldVerification
    {
        return $this->fieldVerifications()->firstOrCreate(
            ['field_name' => $fieldName],
            [
                'is_verified' => false,
                'verified_at' => null,
                'verified_by' => null,
            ]
        );
    }

    /**
     * Check if specific field is verified
     */
    public function isFieldVerified(string $fieldName): bool
    {
        $verification = $this->getFieldVerification($fieldName);
        return $verification ? $verification->is_verified : false;
    }

    /**
     * Check if all fields are verified
     */
    public function allFieldsVerified(): bool
    {
        $item = $this->item;
        if (!$item) return false;

        $fields = $this->getVerifiableFields();
        
        foreach ($fields as $field) {
            if (!$this->isFieldVerified($field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get verifiable fields from item
     */
    public function getVerifiableFields(): array
    {
        $item = $this->item;
        if (!$item) return [];

        // Fields yang tidak perlu diverifikasi
        $excludedFields = [
            'id', 
            'created_at', 
            'updated_at', 
            'deleted_at',
            'user_id',
            'rombongans',
            'nama_opd',    
            'tanggal_dibuat', 
        ];

        // ✅ FIX: Pakai $fillable + $casts untuk mendapatkan SEMUA field
        $allFields = array_unique(array_merge(
            $item->getFillable(),
            array_keys($item->getCasts())
        ));
        
        return array_diff($allFields, $excludedFields);
    }

    /**
     * Get verification progress
     */
    public function getVerificationProgress()
    {
        // ✅ FIX: Hitung SEMUA field yang bisa diverifikasi (bukan hanya yang ada record)
        $verifiableFields = $this->getVerifiableFields();
        $total = count($verifiableFields);
        
        // Hitung yang sudah diverifikasi
        $verified = 0;
        
        foreach ($verifiableFields as $field) {
            $verification = $this->getFieldVerification($field);
            if ($verification && $verification->is_verified) {
                $verified++;
            }
        }
        
        $percentage = $total > 0 ? ($verified / $total) * 100 : 0;
        
        return [
            'total' => $total,
            'verified' => $verified,
            'percentage' => (int) round($percentage),
        ];
    }

    // Tambahkan di RombonganItem.php
    public function initializeAllFieldVerifications(): void 
    {
        $verifiableFields = $this->getVerifiableFields();
        
        foreach ($verifiableFields as $field) {
            $this->fieldVerifications()->firstOrCreate(
                ['field_name' => $field],
                [
                    'is_verified' => false,
                    'verified_at' => null,
                    'verified_by' => null,
                    'keterangan' => null,
                ]
            );
        }
    }
    
    public function getFieldsWithVerificationStatus(): array
    {
        $item = $this->item;
        if (!$item) return [];

        // Ambil SEMUA field termasuk paten
        $allFields = array_unique(array_merge(
            $item->getFillable(),
            array_keys($item->getCasts())
        ));
        
        // ✅ FIELD YANG TIDAK PERLU DITAMPILKAN SAMA SEKALI
        $hiddenFields = [
            'id', 
            'user_id', 
            'rombongans',
            'created_at',    // ← Tambah
            'updated_at',    // ← Tambah  
            'deleted_at',    // ← INI YANG HARUS DITAMBAH!
        ];
        
        $fields = array_diff($allFields, $hiddenFields);
        
        $result = [];

        foreach ($fields as $field) {
            // Tentukan apakah field ini paten (tidak ada checkbox)
            $isPatenField = in_array($field, ['nama_opd', 'tanggal_dibuat']);
            
            // Untuk field paten, tidak perlu ambil verification
            if ($isPatenField) {
                $verification = null;
                $isVerified = true; // Auto verified karena paten
            } else {
                $verification = $this->getFieldVerification($field);
                $isVerified = $verification ? $verification->is_verified : false;
            }
            
            // Ambil value dari field
            $value = $item->$field ?? '-';
            
            // Handle Enum (BackedEnum)
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }
            
            // Handle object lain
            if (is_object($value) && !($value instanceof \Carbon\Carbon)) {
                $value = method_exists($value, '__toString') ? (string) $value : '-';
            }
            
            // Handle nilai NULL
            if ($value === null || $value === '') {
                $value = '-';
            }
            
            $result[] = [
                'field_name' => $field,
                'field_label' => $this->getFieldLabel($field),
                'field_value' => $value,
                'is_verified' => $isVerified,
                'is_paten' => $isPatenField,
                'verification_id' => $verification?->id,
                'keterangan' => $verification?->keterangan,
            ];
        }

        return $result;
    }

    public function getPatenFields(): array
    {
        return [
            'nama_opd',
            'tanggal_dibuat',
            // Tambah field paten lain jika perlu
        ];
    }

    public function isPatenField(string $fieldName): bool
    {
        return in_array($fieldName, $this->getPatenFields());
    }

    /**
     * Convert field name to readable label
     */
    private function getFieldLabel(string $fieldName): string
    {
        // Custom labels untuk field tertentu
        $labels = [
            'nama_opd' => 'Nama OPD',
            'tanggal_dibuat' => 'Tanggal Dibuat',
            'nama_pekerjaan' => 'Nama Pekerjaan',
            'kode_rup' => 'Kode RUP',
            'pagu_rup' => 'Pagu RUP',
            'kode_paket' => 'Kode Paket',
            'jenis_pengadaan' => 'Jenis Pengadaan',
            'nilai_kontrak' => 'Nilai Kontrak',
            'pdn_tkdn_impor' => 'PDN/TKDN/Impor',
            'nilai_pdn_tkdn_impor' => 'Nilai PDN/TKDN/Impor',
            'umk_non_umk' => 'UMK/Non-UMK',
            'nilai_umk' => 'Nilai UMK',
            'realisasi' => 'Realisasi',
            'metode_pengadaan' => 'Metode Pengadaan',
        ];

        return $labels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
    }
}