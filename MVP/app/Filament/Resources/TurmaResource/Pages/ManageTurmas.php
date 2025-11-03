<?php

namespace App\Filament\Resources\TurmaResource\Pages;

use App\Filament\Resources\TurmaResource;
use App\Models\Escola;
use App\Models\Serie;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\TurmaService;

class ManageTurmas extends ManageRecords
{
    protected static string $resource = TurmaResource::class;

    protected function getHeaderActions(): array
    {
        return app(TurmaService::class)->acoesDoCabecalhoDaPagina();
    }

    protected function getTableQuery(): Builder
    {
        return app(TurmaService::class)->queryTabela();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(TurmaService::class)->forcarVinculoComEscola($data, Auth::user());
    }
}
