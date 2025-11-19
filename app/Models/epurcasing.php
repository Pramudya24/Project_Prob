<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class epurcasing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'epurcasing';

    protected $fillable = [
        'nama_opd',
        'tanggal_dibuat',
        'nama_pekerjaan',
        'kode_rup',
        'pagu_rup',
        'kode_paket',
        'jenis_pengadaan',
        'surat_pesanan',
        'nilai_kontrak',
        'pdn_tkdn_impor',
        'nilai_pdn_tkdn_impor',
        'umk_non_umk',
        'nilai_umk',
        'serah_terima',
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
            $model->nama_opd = self::getInisial($user->name);
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
