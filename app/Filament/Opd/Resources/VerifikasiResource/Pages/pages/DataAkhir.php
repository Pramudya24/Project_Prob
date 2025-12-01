<?php

namespace App\Filament\Resources\VerifikasiResource\Pages;

use App\Filament\Resources\VerifikasiResource;
use Filament\Resources\Pages\Page;

class DataAkhir extends Page
{
    protected static string $resource = VerifikasiResource::class;

    protected static string $view = 'filament.resources.verifikasi-resource.pages.data-akhir';

    protected static ?string $title = 'Data Akhir';
}