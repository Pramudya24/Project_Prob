<?php

namespace App\Filament\Verifikator\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = 1;
    /**
     * Daftarkan widget yang akan ditampilkan di dashboard
     */
    public function getWidgets(): array
    {
        return [
            \App\Filament\Verifikator\Widgets\VerifikatorStatsWidget::class,
        ];
    }

    /**
     * Atur kolom layout dashboard
     */
    public function getColumns(): int | string | array
    {
        return 2; // Atau 'full' untuk lebar penuh
    }
}