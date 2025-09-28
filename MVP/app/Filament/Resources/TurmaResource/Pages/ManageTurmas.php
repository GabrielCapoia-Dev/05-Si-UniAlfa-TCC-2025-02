<?php

namespace App\Filament\Resources\TurmaResource\Pages;

use App\Filament\Resources\TurmaResource;
use App\Models\Escola;
use App\Models\Serie;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery()
            ->with(['escola', 'serie']); // ajuste os withs que quiser

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && (!$user->hasRole('Admin')) && !empty($user->id_escola)) {
            $query->where($query->getModel()->getTable() . '.id_escola', $user->id_escola);
        }

        return $query;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User|null $auth */
        $auth = Auth::user();

        // Se não for Admin e tiver escola, força o vínculo da turma à escola do criador.
        if ($auth && !$auth->hasRole('Admin') && !empty($auth->id_escola)) {
            $data['id_escola'] = $auth->id_escola;
        }

        return $data;
    }
}
