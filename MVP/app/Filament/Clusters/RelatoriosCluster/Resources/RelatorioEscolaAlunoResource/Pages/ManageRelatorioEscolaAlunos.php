<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource\Pages;

use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRelatorioEscolaAlunos extends ManageRecords
{
    protected static string $resource = RelatorioEscolaAlunoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
