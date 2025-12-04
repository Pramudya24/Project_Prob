<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;  // ✅
use Illuminate\Support\Facades\Auth; 
use Illuminate\Database\Eloquent\Relations\MorphToMany;


class PengadaanDarurat extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengadaan_darurat';

    protected $fillable = [
        'nama_opd',
        'tanggal_dibuat',
        'nama_pekerjaan',
        'kode_rup',
        'pagu_rup',
        'kode_paket',
        'jenis_pengadaan',
        'nilai_kontrak',
        'pdn_tkdn_impor',
        'nilai_pdn_tkdn_impor',
        'umk_non_umk',
        'nilai_umk',
        'realisasi',
    ];

    protected $casts = [
        'pagu_rup' => 'integer',
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

public function rombongans()
    {
        return $this->belongsToMany(Rombongan::class, 'rombongan_items', 'item_id', 'rombongan_id')
                    ->wherePivot('item_type', self::class);
    }
}
