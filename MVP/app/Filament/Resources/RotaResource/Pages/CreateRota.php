<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRota extends CreateRecord
{
    protected static string $resource = RotaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        dd($data);
        if (empty($data['pontos']) || count($data['pontos']) < 2) {
        }

        return $data;
    }
}
