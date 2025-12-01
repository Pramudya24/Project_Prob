<?php

namespace App\Filament\Opd\Resources\VerifikasiResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Opd\Resources\VerifikasiResource;

class ListVerifikasis extends Page
{
    protected static string $resource = VerifikasiResource::class;

    protected static string $view = 'filament.opd.verifikasi.custom-buttons';

    protected static ?string $title = 'Verifikasi';
}
