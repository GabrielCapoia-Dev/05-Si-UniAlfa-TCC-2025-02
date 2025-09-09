<?php

namespace App\Filament\Resources\PontosDeParadaResource\Pages;

use App\Filament\Resources\PontosDeParadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPontosDeParadas extends ListRecords
{
    protected static string $resource = PontosDeParadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
