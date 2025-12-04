<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;  // ✅
use Illuminate\Database\Eloquent\Relations\MorphToMany;


class Swakelola extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'swakelolas';

    protected $fillable = [
        'nama_opd',
        'tanggal_dibuat',
        'nama_pekerjaan',
        'kode_rup',
        'pagu_rup',
        'kode_paket',
        'jenis_pengadaan',
        'nilai_kontrak',
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

// Ambil inisial dari setiap kata
protected static function getInisial($namaOpd)
{
    $words = explode(' ', $namaOpd);
    $inisial = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $inisial .= strtoupper(substr($word, 0, 1));
        }
    }
    
    return $inisial;
}

public function rombongans(): MorphToMany
{
    return $this->morphToMany(rombongan::class, 'item', 'rombongan_items');
}
}
