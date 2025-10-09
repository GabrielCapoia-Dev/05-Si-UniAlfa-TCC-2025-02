<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Serie;
use App\Models\Turma;
use App\Models\Rota;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;


class AlunoService
{

    /** Define nome do arquivo da foto usando o CGM. */
    public function salvarFotoComNomeComoCGM($file, $get): string
    {
        $cgm = $get('cgm') ?? 'sem-cgm';
        $ext = strtolower($file->getClientOriginalExtension());
        return "{$cgm}.{$ext}";
    }

    /** Opções de escolas ordenadas. */
    public function opcoesDeEscolas(): array
    {
        return Escola::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    /** Escola padrão no form (record->turma->escola ou do usuário). */
    public function escolaPadrao(?Aluno $record, ?int $idEscolaUsuario): ?int
    {
        return $record?->turma?->id_escola ?? $idEscolaUsuario;
    }

    /** Opções de turmas filtradas pela escola escolhida. */
    public function opcoesDeTurmasParaEscola(?int $idEscola): array
    {
        if (! $idEscola) {
            return [];
        }

        return Turma::with('serie')
            ->where('id_escola', $idEscola)
            ->get()
            ->filter(fn($t) => $t->serie) // garante série carregada
            ->mapWithKeys(fn($turma) => [
                $turma->id => "{$turma->serie->nome} - {$turma->turma}",
            ])
            ->toArray();
    }

    /** Desabilita select de turma quando não há escola selecionada. */
    public function desabilitarSelectTurma(?int $idEscola): bool
    {
        return blank($idEscola);
    }

    /** Opções de rotas vinculadas à escola da turma informada. */
    public function opcoesDeRotasParaTurma(?int $idTurma): array
    {
        if (! $idTurma) {
            return [];
        }

        $idEscola = Turma::whereKey($idTurma)->value('id_escola');
        if (! $idEscola) {
            return [];
        }

        return Rota::query()
            ->whereHas('escolas', fn(Builder $q) => $q->where('escolas.id', $idEscola))
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    /** Mostra seção/field de Rotas apenas para Admin. */
    public function podeVerCampoRota($user): bool
    {
        return app(UserService::class)->ehAdmin($user);
    }

    /** Mostra toggle “tem_carteirinha” apenas para Admin. */
    public function podeVerToggleCarteirinha($user): bool
    {
        return app(UserService::class)->ehAdmin($user);
    }


    /** Configura a tabela completa (paginações, colunas, filtros, ações, ordenação). */
    public function configurarTabela(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela())
            ->actions($this->acoesTabela())
            ->defaultSort('nome')
            ->striped();
    }

    /** Colunas da listagem de alunos. */
    public function colunasTabela(): array
    {
        return [
            Tables\Columns\ToggleColumn::make('tem_carteirinha')
                ->label('Tem Carteirinha')
                ->sortable()
                ->inline(false)
                ->onColor('success')
                ->offColor('danger')
                ->onIcon('heroicon-s-check')
                ->offIcon('heroicon-s-x-mark'),

            Tables\Columns\TextColumn::make('nome')
                ->label('Nome')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('turma.escola.nome')
                ->label('Escola')
                ->sortable()
                ->formatStateUsing(
                    fn($record) => optional($record->turma?->escola)->nome ?? '-'
                ),

            Tables\Columns\TextColumn::make('turma.turma')
                ->label('Turma')
                ->formatStateUsing(function ($record) {
                    $serie = optional($record->turma?->serie)->nome;
                    return $serie ? ($serie . ' - ' . ($record->turma?->turma ?? '-')) : '-';
                }),

            Tables\Columns\TextColumn::make('cgm')
                ->label('CGM')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('telefone_responsavel')
                ->label('Telefone Resp.'),

            Tables\Columns\TextColumn::make('telefone_aluno')
                ->label('Telefone Aluno'),

            Tables\Columns\TextColumn::make('cidade')
                ->label('Cidade'),

            Tables\Columns\TextColumn::make('estado')
                ->label('UF'),
        ];
    }

    /** Filtros da listagem de alunos. */
    public function filtrosTabela(): array
    {
        return [
            SelectFilter::make('id_escola')
                ->label('Escola')
                ->relationship('turma.escola', 'nome')
                ->searchable(),


            SelectFilter::make('id_serie')
                ->label('Série')
                ->options($this->opcoesSeries())
                ->query(function (Builder $query, array $data) {
                    $serie = $data['value'] ?? null;
                    if ($serie) {
                        $query->whereHas('turma', fn($q) => $q->where('id_serie', $serie));
                    }
                }),








        ];
    }

    /** Ações da tabela. */
    public function acoesTabela(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    /** Opções de séries (para filtros). */
    public function opcoesSeries(): array
    {
        return Serie::query()
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }
}
