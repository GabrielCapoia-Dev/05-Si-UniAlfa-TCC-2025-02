<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRotas extends ListRecords
{
    protected static string $resource = RotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
