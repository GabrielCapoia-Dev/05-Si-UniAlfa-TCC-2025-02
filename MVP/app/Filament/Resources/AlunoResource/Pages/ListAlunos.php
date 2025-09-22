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
        $this->alunoSelecionado = Aluno::with(['turma.serie', 'turma.escola'])->find($id);
        
        // Adiciona um pequeno feedback visual
        $this->dispatch('$refresh');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Aluno'),
        ];
    }
}