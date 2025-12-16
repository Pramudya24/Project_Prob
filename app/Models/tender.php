<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;  // ✅
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tender extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenders';

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
        'BAST',
        'penilaian_kinerja',
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

public function rombongans(): MorphToMany
{
    return $this->morphToMany(rombongan::class, 'item', 'rombongan_items');
}

}
