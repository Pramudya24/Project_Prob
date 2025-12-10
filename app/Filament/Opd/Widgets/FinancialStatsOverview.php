<?php

namespace App\Filament\Opd\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\epurcasing;
use App\Models\Pl;
use App\Models\nontender;
use App\Models\PengadaanDarurat;
use App\Models\swakelola;
use App\Models\tender;

class FinancialStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Epurcasing
        $epurcasingCount = epurcasing::count();
        $epurcasingTotal = epurcasing::sum('nilai_kontrak'); // Sesuaikan nama kolom

        // Non Tender
        $plCount = Pl::count();
        $plTotal = Pl::sum('nilai_kontrak'); // Sesuaikan nama kolom

        // Pencatatan Non Tender
        $nontenderCount = nontender::count();
        $nontenderTotal = nontender::sum('nilai_kontrak'); // Sesuaikan nama kolom

        // Pencatatan Pengadaan Darurat
        $PengadaanDaruratCount = PengadaanDarurat::count();
        $PengadaanDaruratTotal = PengadaanDarurat::sum('nilai_kontrak'); // Sesuaikan nama kolom

        // Pencatatan Swakelola
        $swakelolaCount = swakelola::count();
        $swakelolaTotal = swakelola::sum('nilai_kontrak'); // Sesuaikan nama kolom

        // Tender
        $tenderCount = Tender::count();
        $tenderTotal = Tender::sum('nilai_kontrak'); // Sesuaikan nama kolom

        $totalCount = $epurcasingCount + $plCount + $nontenderCount + 
                    $PengadaanDaruratCount + $swakelolaCount + $tenderCount;
        
        $totalAmount = $epurcasingTotal + $plTotal + $nontenderTotal + 
                    $PengadaanDaruratTotal + $swakelolaTotal + $tenderTotal;

        return [
            Stat::make('Total Keseluruhan Form', $this->formatCurrency($totalAmount))
                ->description($totalCount . ' data dari semua form')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-blue-500/10 to-purple-500/10 border-2 border-blue-500/20',
                ])
                ->chart([5, 6, 7, 8, 9, 10, 9, 8, 7, 8, 9, 10]),

            Stat::make('Epurcasing', $this->formatCurrency($epurcasingTotal))
                ->description($epurcasingCount . ' data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]), // Opsional: data untuk sparkline

            Stat::make('Non Tender', $this->formatCurrency($plTotal))
                ->description($plCount . ' data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->chart([3, 5, 4, 6, 7, 8, 5, 6]),

            Stat::make('Pencatatan Non Tender', $this->formatCurrency($nontenderTotal))
                ->description($nontenderCount . ' data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([5, 6, 7, 8, 5, 4, 3, 4]),

            Stat::make('Pencatatan Pengadaan Darurat', $this->formatCurrency($PengadaanDaruratTotal))
                ->description($PengadaanDaruratCount . ' data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([2, 3, 4, 5, 6, 5, 4, 3]),

            Stat::make('Pencatatan Swakelola', $this->formatCurrency($swakelolaTotal))
                ->description($swakelolaCount . ' data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([4, 5, 6, 7, 5, 4, 3, 5]),

            Stat::make('Tender', $this->formatCurrency($tenderTotal))
                ->description($tenderCount . ' data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success')
                ->chart([6, 7, 8, 5, 4, 3, 5, 6]),
        ];
    }

    /**
     * Format angka menjadi format Rupiah
     */
    private function formatCurrency($amount): string
    {
        if ($amount === null || $amount === 0) {
            return 'Rp 0';
        }
        
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Atur posisi widget di dashboard
     */
    protected static ?int $sort = 1;
    
    /**
     * Widget menggunakan lebar penuh
     */
    protected int | string | array $columnSpan = 'full';
    
    /**
     * Atau gunakan ini untuk layout kolom (uncomment jika ingin 3 kolom)
     */
    // protected function getColumns(): int
    // {
    //     return 3;
    // }
}