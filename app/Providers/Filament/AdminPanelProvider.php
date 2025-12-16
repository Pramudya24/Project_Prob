<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Path ke logo - GANTI DENGAN PATH LOGO ANDA
        $logoPath = asset('images/logo.jpg'); // atau 'logo.png'
        
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandLogo($logoPath) // Logo untuk halaman login (optional)
            ->brandLogoHeight('2.5rem')
            ->brandName('SIVERA')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Menu Baru')
                    ->icon('heroicon-o-folder')
                    ->url(fn() => route('filament.admin.pages.menu-baru'))
                    ->visible(fn() => false),
            ])
            // 1. CUSTOM HEADER DENGAN LOGO (SIDEBAR HEADER)
            ->renderHook(
                'panels::sidebar.header',
                fn () => <<<HTML
                <div class="flex items-center gap-3 px-6 py-4">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <img 
                            src="$logoPath" 
                            alt="SIVERA Logo" 
                            class="h-10 w-10 object-contain rounded"
                            onerror="this.style.display='none'; console.log('Logo gagal dimuat: $logoPath')"
                        />
                    </div>
                    
                    <!-- Nama Aplikasi -->
                    <div class="flex flex-col">
                        <span class="text-xl font-bold text-white">
                            SIVERA
                        </span>
                        <span class="text-xs text-gray-300">
                            Procurement System
                        </span>
                    </div>
                </div>
                HTML
            )
            // 2. CUSTOM CSS (untuk warna menu)
            ->renderHook(
                'panels::styles.after',
                fn () => <<<'HTML'
                <style>
                    /* Warna teks menu sidebar */
                    .fi-sidebar-nav span.fi-sidebar-item-label {
                        color: #10b981 !important;
                    }
                    
                    .fi-sidebar-item:hover span.fi-sidebar-item-label {
                        color: #34d399 !important;
                    }
                    
                    .fi-sidebar-item.fi-active span.fi-sidebar-item-label {
                        color: #3b82f6 !important;
                    }
                    
                    /* Pastikan header sidebar tidak ada margin aneh */
                    .fi-sidebar-header {
                        margin: 0 !important;
                        padding: 1.5rem !important;
                        border-bottom: 1px solid rgba(255,255,255,0.1) !important;
                    }
                    
                    /* Sembunyikan default Filament logo jika ada */
                    .fi-sidebar-header .fi-logo {
                        display: none !important;
                    }
                </style>
                HTML
            )
            // 3. HOOK LAIN YANG SUDAH ADA (jangan dihapus)
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.hooks.sidebar-tooltips')
            );
    }
}