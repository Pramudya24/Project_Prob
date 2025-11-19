<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RombonganItem extends Model
{
    use HasFactory;

    protected $table = 'rombongan_items';

    protected $fillable = [
        'rombongan_id',
        'item_type',
        'item_id'
    ];

    public function rombongan(): BelongsTo
    {
        return $this->belongsTo(Rombongan::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}