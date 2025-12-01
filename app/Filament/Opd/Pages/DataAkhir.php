<?php

namespace App\Filament\Opd\Pages;

use Filament\Pages\Page;

class DataAkhir extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.opd.pages.data-akhir';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
