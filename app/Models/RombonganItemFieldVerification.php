<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RombonganItemFieldVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'rombongan_item_id',
        'field_name',
        'is_verified',
        'verified_at',
        'verified_by',
        'keterangan',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Relasi ke RombonganItem
    public function rombonganItem(): BelongsTo
    {
        return $this->belongsTo(RombonganItem::class);
    }

    // Relasi ke User yang memverifikasi
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Toggle status verifikasi
     */
    public function toggleVerification(): void
    {
        $this->update([
            'is_verified' => !$this->is_verified,
            'verified_at' => !$this->is_verified ? null : now(),
            'verified_by' => !$this->is_verified ? null : auth()->id(),
        ]);
    }

    /**
     * Set sebagai terverifikasi
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);
    }

    /**
     * Set sebagai belum terverifikasi
     */
    public function markAsUnverified(): void
    {
        $this->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }
}