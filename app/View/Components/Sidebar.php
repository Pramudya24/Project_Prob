<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public $menus;
    
    public function __construct()
    {
        $this->menus = [
            [
                'name' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'fas fa-home',
                'tooltip' => null,
                'children' => []
            ],
            [
                'name' => 'Pengajuan',
                'route' => 'pengajuan',
                'icon' => 'fas fa-file-alt',
                'tooltip' => 'Halaman untuk mengajukan permohonan baru',
                'children' => []
            ],
            [
                'name' => 'Form',
                'route' => null,
                'icon' => null,
                'tooltip' => null,
                'children' => [
                    [
                        'name' => 'Epurcasing',
                        'route' => 'epurcasing',
                        'icon' => 'fas fa-shopping-cart',
                        'tooltip' => 'Form untuk keperluan elektronik purchasing'
                    ],
                    [
                        'name' => 'Non Tender',
                        'route' => 'non-tender',
                        'icon' => 'fas fa-file-invoice',
                        'tooltip' => 'Form pengadaan barang/jasa tanpa proses tender'
                    ],
                    [
                        'name' => 'Pencatatan Non Tender',
                        'route' => 'pencatatan-non-tender',
                        'icon' => 'fas fa-edit',
                        'tooltip' => 'Form untuk mencatat transaksi non tender'
                    ],
                    [
                        'name' => 'Pencatatan Penjualan Darurat',
                        'route' => 'pencatatan-penjualan-darurat',
                        'icon' => 'fas fa-exclamation-triangle',
                        'tooltip' => 'Form pencatatan untuk kondisi darurat atau mendesak'
                    ],
                    [
                        'name' => 'Pencatatan Swakelola',
                        'route' => 'pencatatan-swakelola',
                        'icon' => 'fas fa-tools',
                        'tooltip' => 'Form pencatatan untuk pengerjaan swakelola'
                    ],
                    [
                        'name' => 'Tender',
                        'route' => 'tender',
                        'icon' => 'fas fa-briefcase',
                        'tooltip' => 'Form dan proses tender pengadaan'
                    ],
                ]
            ],
        ];
    }

    public function render(): View
    {
        return view('components.sidebar');
    }
}