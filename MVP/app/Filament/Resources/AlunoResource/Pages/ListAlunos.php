<?php

namespace App\Filament\Resources\AlunoResource\Pages;

use App\Filament\Resources\AlunoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Aluno;

class ListAlunos extends ListRecords
{
    protected static string $resource = AlunoResource::class;
    protected static string $view = 'components.layouts.list-with-sidebar';

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
            Actions\CreateAction::make()
                ->label('Novo Aluno')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}