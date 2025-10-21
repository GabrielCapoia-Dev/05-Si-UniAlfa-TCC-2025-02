<?php

namespace App\Filament\Resources\AlunoResource\Pages;

use App\Filament\Resources\AlunoResource;
use App\Services\AlunoService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Turma;
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
        return app(AlunoService::class)->validarAcoesCabecario();
    }

    protected function getTableQuery(): Builder
    {
        return app(AlunoService::class)->buscarAlunosParaListagem(Auth::user());
    }
}
