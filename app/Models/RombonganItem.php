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
        ];

        $allFields = array_keys($item->toArray());
        
        return array_diff($allFields, $excludedFields);
    }

    /**
     * Get verification progress
     */
    public function getVerificationProgress(): array
    {
        $fields = $this->getVerifiableFields();
        $total = count($fields);
        $verified = 0;

        foreach ($fields as $field) {
            if ($this->isFieldVerified($field)) {
                $verified++;
            }
        }

        return [
            'total' => $total,
            'verified' => $verified,
            'percentage' => $total > 0 ? round(($verified / $total) * 100) : 0,
        ];
    }

    /**
     * Get all fields with verification status
     * UPDATED: Now handles Enum properly
     */
    public function getFieldsWithVerificationStatus(): array
    {
        $item = $this->item;
        if (!$item) return [];

        $fields = $this->getVerifiableFields();
        $result = [];

        foreach ($fields as $field) {
            $verification = $this->getFieldVerification($field);
            
            // Ambil value dari field
            $value = $item->$field ?? '-';
            
            // PENTING: Handle Enum (BackedEnum)
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }
            
            // Handle object lain (kecuali Carbon untuk tanggal)
            if (is_object($value) && !($value instanceof \Carbon\Carbon)) {
                if (method_exists($value, '__toString')) {
                    $value = (string) $value;
                } else {
                    // Log warning untuk debugging
                    \Log::warning('Cannot convert object to string in RombonganItem', [
                        'field' => $field,
                        'class' => get_class($value),
                        'item_type' => get_class($item),
                        'item_id' => $item->id,
                    ]);
                    $value = '-';
                }
            }
            
            $result[] = [
                'field_name' => $field,
                'field_label' => $this->getFieldLabel($field),
                'field_value' => $value,
                'is_verified' => $verification ? $verification->is_verified : false,
                'verification_id' => $verification?->id,
                'keterangan' => $verification?->keterangan,
            ];
        }

        return $result;
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