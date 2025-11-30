<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('opd')
            ->login()

            // ⭐ Tambahkan navigation di sini
            ->navigation([
                NavigationItem::make('Menu Baru')
                    ->icon('heroicon-o-folder')
                    ->url(route('filament.admin.pages.menu-baru')),
            ])

            // Hook kamu tetap ada
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.hooks.sidebar-tooltips')
            );
    }
}

//                 <script>
//                     document.addEventListener('DOMContentLoaded', function() {
//                         addSidebarTooltips();
//                     });
                    
//                     function addSidebarTooltips() {
//                         const tooltips = {
//                             'Epurcasing': 'Form untuk keperluan elektronik purchasing',
//                             'Non Tender': 'Form pengadaan barang/jasa tanpa proses tender',
//                             'Pencatatan Non Tender': 'Form untuk mencatat transaksi non tender',
//                             'Pencatatan Pengadaan Darurat': 'Form pencatatan untuk kondisi darurat',
//                             'Pencatatan Swakelola': 'Form pencatatan untuk pengerjaan swakelola',
//                             'Tender': 'Form dan proses tender pengadaan',
//                         };
                        
//                         document.querySelectorAll('.fi-sidebar-item-button').forEach(button => {
//                             const label = button.querySelector('.fi-sidebar-item-label');
//                             if (label) {
//                                 const menuName = label.textContent.trim();
//                                 if (tooltips[menuName]) {
//                                     // Buat tooltip element
//                                     button.setAttribute('x-data', `{ showTooltip: false }`);
//                                     button.setAttribute('@mouseenter', 'showTooltip = true');
//                                     button.setAttribute('@mouseleave', 'showTooltip = false');
                                    
//                                     const tooltipDiv = document.createElement('div');
//                                     tooltipDiv.setAttribute('x-show', 'showTooltip');
//                                     tooltipDiv.setAttribute('x-transition', '');
//                                     tooltipDiv.className = 'custom-sidebar-tooltip';
//                                     tooltipDiv.textContent = tooltips[menuName];
                                    
//                                     button.style.position = 'relative';
//                                     button.appendChild(tooltipDiv);
//                                 }
//                             }
//                         });
//                     }
//                 </script>
                
//                 <style>
//                     .custom-sidebar-tooltip {
//                         position: absolute;
//                         left: 100%;
//                         top: 50%;
//                         transform: translateY(-50%);
//                         margin-left: 15px;
//                         background-color: #002e6eff;
//                         color: white;
//                         padding: 10px 14px;
//                         border-radius: 6px;
//                         font-size: 13px;
//                         white-space: nowrap;
//                         z-index: 9999;
//                         box-shadow: 0 4px 14px rgba(156, 0, 0, 0.4);
//                         pointer-events: none;
//                     }
                    
//                     .custom-sidebar-tooltip::before {
//                         content: '';
//                         position: absolute;
//                         right: 100%;
//                         top: 50%;
//                         transform: translateY(-50%);
//                         border: 7px solid transparent;
//                         border-right-color: #0055cbff;
//                     }
//                 </style>
//             HTML)
//         );
// }