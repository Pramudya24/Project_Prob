<?php

namespace App\Filament\Monitoring\Resources\MonitoringResource\Pages;

use App\Filament\Monitoring\Resources\MonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonitorings extends ListRecords
{
    protected static string $resource = MonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
