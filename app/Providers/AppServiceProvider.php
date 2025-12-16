<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Livewire\Livewire;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'panels::auth.login.form.after',
            fn(): string => Blade::render('@vite(\'resources/css/custom-login.css\')'),
        );

        // Register AvailableItemsTable sebagai Livewire component
        Livewire::component(
            'available-items-table',
            \App\Filament\Opd\Resources\Rombongan\Pages\AvailableItemsTable::class
        );

        Livewire::component(
        'rombongan-items-table', 
        \App\Filament\Opd\Resources\Rombongan\Pages\RombonganItemsTable::class
    );
    }
}
