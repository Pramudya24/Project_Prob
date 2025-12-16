<?php

namespace App\Models;

enum MetodePengadaan: string
{
    case DIKECUALIKAN = 'Dikecualikan';
    case EPENGADAAN_LANGSUNG = 'EPengadaan Langsung';
    case PENUNJUKAN_LANGSUNG = 'Penunjukan Langsung';
    case EPENUNJUKAN_LANGSUNG = 'EPenunjukan Langsung';
    case TOKO_DARING = 'Toko Daring';
    case EKATALOG = 'E-Katalog';
    // Helper untuk ambil semua values
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public function label(): string
    {
        return match($this) {
            self::Dikecualikan => 'Dikecualikan',
            self::EPENGADAAN_LANGSUNG => 'EPengadaan Langsung',
            self::PENUNJUKAN_LANGSUNG => 'Penunjukan Langsung',
            self::EPENUNJUKAN_LANGSUNG => 'EPenunjukan Langsung',
            self::TokoDaring => 'Toko Daring',
            self::Ekatalog => 'E-Katalog',
        };
    }

    // Helper untuk options di Filament
    public static function options(): array
    {
        return [
            self::DIKECUALIKAN->value => 'Dikecualikan',
            self::EPENGADAAN_LANGSUNG->value => 'EPengadaan Langsung',
            self::PENUNJUKAN_LANGSUNG->value => 'Penunjukan Langsung',
            self::EPENUNJUKAN_LANGSUNG->value => 'EPenunjukan Langsung',
            self::TOKO_DARING->value => 'Toko Daring',
            self::EKATALOG->value => 'E-Katalog',
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