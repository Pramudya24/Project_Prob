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
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
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
            // Tambahkan render hook ini untuk custom CSS
            ->renderHook(
                'panels::styles.after',
                fn () => <<<'HTML'
                <style>
                    /* Force override warna sidebar */
                    span.fi-sidebar-item-label.flex-1.truncate.text-sm.font-medium.text-gray-700.dark\:text-gray-200 {
                        color: #10b981 !important;
                    }
                    
                    .fi-sidebar-item:hover span.fi-sidebar-item-label {
                        color: #34d399 !important;
                    }
                    
                    .fi-sidebar-item.fi-active span.fi-sidebar-item-label {
                        color: #3b82f6 !important;
                    }
                    
                    /* Override untuk semua state */
                    .fi-sidebar-nav span.fi-sidebar-item-label {
                        color: #10b981 !important;
                    }
                </style>
                HTML
            )
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.hooks.sidebar-tooltips')
            );
    }
}