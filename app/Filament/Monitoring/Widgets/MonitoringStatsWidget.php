<?php

namespace App\Filament\Monitoring\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\epurcasing;
use App\Models\Pl;
use App\Models\nontender;
use App\Models\PengadaanDarurat;
use App\Models\swakelola;
use App\Models\tender;
use Illuminate\Support\Facades\DB;

class MonitoringStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        \Log::info('MonitoringStatsWidget loaded');
        // Hitung total dari SEMUA OPD (tanpa filter user)
        $totalEpurcasing = epurcasing::sum('nilai_kontrak') ?? 0;
        $totalEpurcasingData = epurcasing::count();
        
        $totalNonTender = Pl::sum('nilai_kontrak') ?? 0;
        $totalNonTenderData = Pl::count();
        
        $totalPencatatanNonTender = nontender::sum('nilai_kontrak') ?? 0;
        $totalPencatatanNonTenderData = nontender::count();
        
        $totalPencatatanDarurat = PengadaanDarurat::sum('nilai_kontrak') ?? 0;
        $totalPencatatanDaruratData = PengadaanDarurat::count();
        
        $totalPencatatanSwakelola = swakelola::sum('nilai_kontrak') ?? 0;
        $totalPencatatanSwakelolaData = swakelola::count();
        
        $totalTender = Tender::sum('nilai_kontrak') ?? 0;
        $totalTenderData = Tender::count();

        // Hitung total keseluruhan form (semua jenis pengadaan)
        $totalKeseluruhan = $totalEpurcasing + $totalNonTender + $totalPencatatanNonTender + 
                            $totalPencatatanDarurat + $totalPencatatanSwakelola + $totalTender;
        $totalDataForm = $totalEpurcasingData + $totalNonTenderData + $totalPencatatanNonTenderData +
                        $totalPencatatanDaruratData + $totalPencatatanSwakelolaData + $totalTenderData;

        return [
            Stat::make('Total Keseluruhan Form', 'Rp ' . number_format($totalKeseluruhan, 0, ',', '.'))
                ->description($totalDataForm . ' data dari semua form')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart([7, 12, 8, 15, 11, 9, 13])
                ->color('warning'),

            Stat::make('Epurcasing', 'Rp ' . number_format($totalEpurcasing, 0, ',', '.'))
                ->description($totalEpurcasingData . ' data')
                ->descriptionIcon('heroicon-m-document')
                ->chart([3, 5, 4, 7, 6, 5, 8])
                ->color('success'),

            Stat::make('Non Tender', 'Rp ' . number_format($totalNonTender, 0, ',', '.'))
                ->description($totalNonTenderData . ' data')
                ->descriptionIcon('heroicon-m-document')
                ->chart([2, 4, 3, 5, 4, 3, 6])
                ->color('success'),

            Stat::make('Pencatatan Non Tender', 'Rp ' . number_format($totalPencatatanNonTender, 0, ',', '.'))
                ->description($totalPencatatanNonTenderData . ' data')
                ->descriptionIcon('heroicon-m-document')
                ->chart([4, 6, 5, 8, 7, 6, 9])
                ->color('info'),

            Stat::make('Pencatatan Pengadaan Darurat', 'Rp ' . number_format($totalPencatatanDarurat, 0, ',', '.'))
                ->description($totalPencatatanDaruratData . ' data')
                ->descriptionIcon('heroicon-m-document')
                ->chart([1, 3, 2, 4, 3, 2, 5])
                ->color('info'),

            Stat::make('Pencatatan Swakelola', 'Rp ' . number_format($totalPencatatanSwakelola, 0, ',', '.'))
                ->description($totalPencatatanSwakelolaData . ' data')
                ->descriptionIcon('heroicon-m-document')
                ->chart([2, 5, 3, 6, 4, 3, 7])
                ->color('info'),

            Stat::make('Tender', 'Rp ' . number_format($totalTender, 0, ',', '.'))
                ->description($totalTenderData . ' data')
                ->descriptionIcon('heroicon-m-document')
                ->chart([5, 8, 6, 10, 8, 7, 11])
                ->color('success'),
        ];
    }
}