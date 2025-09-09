<?php

namespace App\Filament\Resources\PontosDeParadaResource\Pages;

use App\Filament\Resources\PontosDeParadaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPontosDeParada extends EditRecord
{
    protected static string $resource = PontosDeParadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
