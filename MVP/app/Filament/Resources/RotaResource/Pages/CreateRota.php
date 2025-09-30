<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRota extends CreateRecord
{
    protected static string $resource = RotaResource::class;
    protected static string $view = 'components.layouts.test';
}
