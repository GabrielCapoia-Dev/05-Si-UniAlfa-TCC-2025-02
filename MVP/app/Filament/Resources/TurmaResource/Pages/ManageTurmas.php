<?php

namespace App\Filament\Resources\TurmaResource\Pages;

use App\Filament\Resources\TurmaResource;
use App\Models\Escola;
use App\Models\Serie;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTurmas extends ManageRecords
{
    protected static string $resource = TurmaResource::class;

    protected function getHeaderActions(): array
    {
        $hasEscolas = Escola::exists();
        $hasSeries = Serie::exists();

        if ($hasEscolas && $hasSeries) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        $actions = [];

        if (! $hasEscolas) {
            $actions[] = Actions\Action::make('Cadastrar Escolas')
                ->button()
                ->color('danger')
                ->icon('heroicon-o-academic-cap')
                ->url(route('filament.admin.resources.escolas.index'));
        }

        if (! $hasSeries) {
            $actions[] = Actions\Action::make('Cadastrar Séries')
                ->button()
                ->color('danger')
                ->icon('heroicon-o-collection')
                ->url(route('filament.admin.resources.series.index'));
        }

        return $actions;
    }
}
