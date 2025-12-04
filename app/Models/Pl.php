<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;  // ✅
use Illuminate\Support\Facades\Auth; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Pl extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pls';

    protected $fillable = [
        'nama_opd',
        'tanggal_dibuat',
        'nama_pekerjaan',
        'kode_rup',
        'pagu_rup',
        'kode_paket',
        'jenis_pengadaan',
        'summary_report',
        'nilai_kontrak',
        'pdn_tkdn_impor',
        'nilai_pdn_tkdn_impor',
        'persentase_tkdn',
        'umk_non_umk',
        'nilai_umk',
        'serah_terima_pekerjaan',
        'bast_document',
        'penilaian_kinerja',
        'metode_pengadaan',
    ];

    protected $casts = [
        'metode_pengadaan' => MetodePengadaan::class,
        'tanggal_pls' => 'date',
        'kode_rup' => 'integer',
        'pagu_rup' => 'integer',
        'nilai_kontrak' => 'decimal:2',
        'nilai_pdn_tkdn_impor' => 'decimal:2',
        'nilai_umk' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();
                // ✅ LANGSUNG PAKAI opd_code
                $model->nama_opd = $user->opd_code;
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('opd_filter', function (Builder $builder) {
            $user = Auth::user();
            
            // Filter hanya untuk role OPD
            if ($user && $user->hasRole('opd') && $user->opd_code) {
                $builder->where('nama_opd', $user->opd_code);
            }
        });
    }

    protected function setMetodePengadaanAttribute($value)
    {
        if ($value === '' || $value === null) {
            $this->attributes['metode_pengadaan'] = null;
            return;
        }

        // Jika sudah Enum object, ambil value-nya
        if ($value instanceof MetodePengadaan) {
            $this->attributes['metode_pengadaan'] = $value->value;
            return;
        }

        $this->attributes['metode_pengadaan'] = $value;
    }

    public function getMetodePengadaanLabelAttribute(): ?string
    {
        return $this->metode_pengadaan?->value ?? null;
    }

    public function rombongans(): MorphToMany
    {
        return $this->morphToMany(rombongan::class, 'item', 'rombongan_items');
    }
}