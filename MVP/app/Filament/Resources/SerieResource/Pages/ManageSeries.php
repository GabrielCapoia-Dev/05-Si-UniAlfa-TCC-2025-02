<?php

namespace App\Filament\Resources\SerieResource\Pages;

use App\Filament\Resources\SerieResource;
use App\Http\Controllers\SerieController;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSeries extends ManageRecords
{
    protected static string $resource = SerieResource::class;


    protected function getHeaderActions(): array
    {
        return [

            Actions\CreateAction::make()
                ->action(function ($data) {
                    app(SerieController::class)->criarSerie($data);
                }),

        ];
    }
}
