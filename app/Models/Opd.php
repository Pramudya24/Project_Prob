<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Opd extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    // Relasi ke user (opsional kalau mau)
    public function users()
    {
        return $this->hasMany(User::class, 'name', 'name');
    }

    // Relasi ke rombongan
    public function rombongans()
    {
        return $this->hasMany(Rombongan::class, 'nama_opd', 'code');
    }

    
}
