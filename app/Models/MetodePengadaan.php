<?php

namespace App\Models;

enum MetodePengadaan: string
{
    case DIKECUALIKAN = 'Dikecualikan';
    case PENGADAAN_LANGSUNG = 'Pengadaan Langsung';
    case PENUNJUKAN_LANGSUNG = 'Penunjukan Langsung';
    case EPENUNJUKAN_LANGSUNG = 'EPenunjukan Langsung';

    // Helper untuk ambil semua values
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public function label(): string
    {
        return match($this) {
            self::Dikecualikan => 'Dikecualikan',
            self::PengadaanLangsung => 'Pengadaan Langsung',
            self::PenunjukanLangsung => 'Penunjukan Langsung',
        };
    }

    // Helper untuk options di Filament
    public static function options(): array
    {
        return [
            self::DIKECUALIKAN->value => 'Dikecualikan',
            self::PENGADAAN_LANGSUNG->value => 'Pengadaan Langsung',
            self::PENUNJUKAN_LANGSUNG->value => 'Penunjukan Langsung',
            self::EPENUNJUKAN_LANGSUNG->value => 'E-Penunjukan Langsung',
        ];
    }
    public static function fromNullable(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return self::tryFrom($value);
    }
}