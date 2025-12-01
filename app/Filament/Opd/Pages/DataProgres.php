<?php

namespace App\Filament\Opd\Pages;

use Filament\Pages\Page;

class DataProgres extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.opd.pages.data-progres';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
