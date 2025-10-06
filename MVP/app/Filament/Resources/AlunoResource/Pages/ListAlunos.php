<?php

namespace App\Filament\Resources\AlunoResource\Pages;

use App\Filament\Resources\AlunoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Aluno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListAlunos extends ListRecords
{
    protected static string $resource = AlunoResource::class;
    protected static string $view = 'components.layouts.list-alunos';

    public ?Aluno $alunoSelecionado = null;

    protected $listeners = ['abrirDetalhesAluno'];

    public function mount(): void
    {
        parent::mount();
    }

    public function abrirDetalhesAluno($id): void
    {
        $aluno = Aluno::with(['turma.serie', 'turma.escola'])->find($id);

        if (!$aluno) {
            $this->fecharDetalhesAluno();
            return;
        }

        $this->alunoSelecionado = $aluno;
        $this->dispatch('$refresh');
    }

    public function fecharDetalhesAluno(): void
    {
        $this->alunoSelecionado = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery()
            ->with(['turma.escola', 'turma.serie']); // ajuste os withs

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && (!$user->hasRole('Admin')) && !empty($user->id_escola)) {
            $query->whereHas('turma', function (Builder $t) use ($user) {
                $t->where($t->getModel()->getTable() . '.id_escola', $user->id_escola);
            });
        }

        return $query;
    }
}
