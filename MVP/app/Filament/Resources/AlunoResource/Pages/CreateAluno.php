<?php

namespace App\Filament\Resources\AlunoResource\Pages;

use App\Filament\Resources\AlunoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAluno extends CreateRecord
{
    protected static string $resource = AlunoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}