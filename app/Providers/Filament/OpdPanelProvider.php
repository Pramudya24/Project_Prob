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
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;

class OpdPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('opd')
            ->path('opd')
            ->brandName('SIVERA')
            ->renderHook(
                'panels::global-search.after',
                fn (): string => Blade::render('
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        ' . (auth()->user()->name ?? 'Nama OPD Anda') . '
                    </div>
                ')
            )
            ->colors([
                'primary' => Color::Blue,
            ])
            // MATIKAN discoverResources, daftar manual saja
            ->resources([
                \App\Filament\Opd\Resources\EpurcasingResource::class,
                \App\Filament\Opd\Resources\Pls\PlResource::class,
                \App\Filament\Opd\Resources\NonTenderResource::class,
                \App\Filament\Opd\Resources\PengadaanDaruratResource::class,
                \App\Filament\Opd\Resources\SwakelolaResource::class,
                \App\Filament\Opd\Resources\TenderResource::class,
                \App\Filament\Opd\Resources\VerifikasiResource::class,
                \App\Filament\Opd\Resources\Rombongan\RombonganResource::class,
                // Tambahkan resource lain jika ada (PlResource, dll)
            ])
            ->pages([
                \App\Filament\Opd\Pages\Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Opd/Widgets'),
                for: 'App\\Filament\\Opd\\Widgets'
            )
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Form')
                    ->collapsible(),
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
                \App\Http\Middleware\OpdMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}