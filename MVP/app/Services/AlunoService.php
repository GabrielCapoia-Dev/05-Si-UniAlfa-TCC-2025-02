<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Serie;
use App\Models\Turma;
use App\Models\Rota;
use App\Filament\Resources\AlunoResource\Pages\ListAlunos;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

use App\Models\User;

class AlunoService
{

    /** Define nome do arquivo da foto usando o CGM. */
    public function salvarFotoComNomeComoCGM($file, $get): string
    {
        $cgm = $get('cgm') ?? 'sem-cgm';
        $ext = strtolower($file->getClientOriginalExtension());
        return "{$cgm}.{$ext}";
    }

    /** Retorna se é Admin (delegando ao UserService). */
    public function ehAdmin(?User $user): bool
    {
        return app(UserService::class)->ehAdmin($user);
    }

    /** Opções de escolas conforme perfil: Admin vê todas; secretário só a sua. */
    public function opcoesDeEscolasParaUsuario(?User $user): array
    {
        if ($this->ehAdmin($user) || empty($user?->id_escola)) {
            return $this->opcoesDeEscolas();
        }

        return Escola::query()
            ->whereKey($user->id_escola)
            ->pluck('nome', 'id')
            ->toArray();
    }

    /** Desabilita o select de escola para usuário vinculado a uma escola (não-Admin). */
    public function deveTravarCampoEscola(?User $user): bool
    {
        return ! $this->ehAdmin($user) && filled($user?->id_escola);
    }

    public function escolaInicialParaForm(?Aluno $record, ?User $user): ?int
    {
        return $record?->turma?->id_escola ?? ($user?->id_escola ?? null);
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
        return $this->ehAdmin($user);
    }

    /** Mostra toggle “tem_carteirinha” apenas para Admin. */
    public function podeVerToggleCarteirinha($user): bool
    {
        return $this->ehAdmin($user);
    }


    /** Configura a tabela completa (paginações, colunas, filtros, ações, ordenação). */
    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela($user))
            ->actions($this->acoesTabela())
            ->bulkActions($this->acoesEmMassa($user))
            ->defaultSort('nome')
            ->striped();
    }

    /** Colunas da listagem de alunos. */
    public function colunasTabela(): array
    {
        return [
            Tables\Columns\ToggleColumn::make('tem_carteirinha')
                ->label('Usa o Transporte?')
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
            Tables\Columns\TextColumn::make('cgm')
                ->label('CGM')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('grupo_escola')
                ->label('Escola')
                ->html()
                ->wrap()
                ->getStateUsing(function ($record) {
                    $escola = optional($record->turma?->escola)->nome ?? '-';
                    $serie  = optional($record->turma?->serie)->nome;
                    $turma  = $record->turma?->turma ?? '-';
                    $turno  = $record->turma?->turno ?? '-';
                    $rota   = $record->rota?->nome ?? '-';

                    $seriesTurma = $serie ? ($serie . ' - ' . $turma) : '-';

                    return collect([
                        "<div><strong>Escola:</strong> {$escola}</div>",
                        "<div><strong>Turma:</strong> {$seriesTurma}</div>",
                        "<div><strong>Turno:</strong> {$turno}</div>",
                        "<div><strong>Rota:</strong> {$rota}</div>",
                    ])->implode('');
                })
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('grupo_contato')
                ->label('Contato')
                ->html()
                ->wrap()
                ->getStateUsing(function ($record) {
                    $resp  = $record->nome_responsavel ?? '-';
                    $telR  = $record->telefone_responsavel ?? '-';
                    $telA  = $record->telefone_aluno ?? '-';
                    $telAlt = $record->telefone_alternativo ?? '-';

                    return collect([
                        "<div><strong>Responsável:</strong> {$resp}</div>",
                        "<div><strong>Tel. Resp.:</strong> {$telR}</div>",
                        "<div><strong>Tel. Aluno:</strong> {$telA}</div>",
                        "<div><strong>Tel. Alt.:</strong> {$telAlt}</div>",
                    ])->implode('');
                })
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('grupo_endereco')
                ->label('Endereço')
                ->html()
                ->wrap()
                ->getStateUsing(function ($record) {
                    $logradouro = $record->logradouro ?? '-';
                    $numero     = $record->numero ?? '-';
                    $bairro     = $record->bairro ?? '-';
                    $cidade     = $record->cidade ?? '-';
                    $estado     = $record->estado ?? '-';
                    $cep        = $record->cep ?? '-';
                    $compl      = $record->complemento ?? null;

                    return collect([
                        "<div><strong>Logradouro:</strong> {$logradouro}, {$numero}</div>",
                        "<div><strong>Bairro:</strong> {$bairro}</div>",
                        "<div><strong>Cidade/UF:</strong> {$cidade}/{$estado}</div>",
                        "<div><strong>CEP:</strong> {$cep}</div>",
                        $compl ? "<div><strong>Compl.:</strong> {$compl}</div>" : null,
                    ])->filter()->implode('');
                })
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /** Filtros da listagem de alunos. */
    public function filtrosTabela(?User $user): array
    {
        return [
            SelectFilter::make('id_escola')
                ->label('Escola')
                ->relationship('turma.escola', 'nome')
                ->visible(fn() => $this->ehAdmin($user))
                ->searchable(),


            SelectFilter::make('id_serie')
                ->label('Série')
                ->preload()
                ->searchable()
                ->options($this->opcoesSeries())
                ->query(function (Builder $query, array $data) {
                    $serie = $data['value'] ?? null;
                    if ($serie) {
                        $query->whereHas('turma', fn($q) => $q->where('id_serie', $serie));
                    }
                }),

            SelectFilter::make('turma.turno')
                ->label('Turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                    'Integral' => 'Integral',
                ])
                ->query(function (Builder $query, array $data) {
                    $turno = $data['value'] ?? null;
                    if ($turno) {
                        $query->whereHas('turma', fn($q) => $q->where('turno', $turno));
                    }
                }),

            TernaryFilter::make('tem_carteirinha')
                ->label('Tem Carteirinha')
                ->placeholder('Todos')
                ->trueLabel('Sim')
                ->falseLabel('Não')



        ];
    }

    /** Ações da tabela. */
    public function acoesTabela(): array
    {
        return [
            Tables\Actions\Action::make('visualizar')
                ->label('Ver Detalhes')
                ->icon('heroicon-m-eye')
                ->color('info')
                ->action(function (Aluno $record, $livewire) {
                    $livewire->dispatch('abrirDetalhesAluno', $record->id);
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->before(function ($record, Tables\Actions\DeleteAction $action) {
                    $livewire = $action->getLivewire();

                    if ($livewire instanceof ListAlunos) {
                        if ($livewire->alunoSelecionado && $livewire->alunoSelecionado->id === $record->id) {
                            $livewire->fecharDetalhesAluno();
                        }
                    }
                }),
        ];
    }

    /** Ações em massa da tabela. */
    public function acoesEmMassa(?User $user): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),

            BulkAction::make('exportar_carteirinhas_html')
                ->label('Exportar (Impressão)')
                ->icon('heroicon-o-printer')
                ->visible(function (ListAlunos $livewire) {
                    // (opcional) manter a regra: só aparece se ninguém na LISTAGEM tiver tem_carteirinha = false
                    return !$livewire->getFilteredTableQuery()
                        ->clone()
                        ->where('tem_carteirinha', false)
                        ->exists();
                })
                ->requiresConfirmation()
                ->modalHeading('Exportar para impressão')
                ->modalDescription('Abriremos uma página HTML pronta para impressão (Ctrl/Cmd+P).')
                ->action(function (Collection $records, ListAlunos $livewire) {
                    // SOMENTE selecionados:
                    $idsSelecionados = $records->pluck('id')->all();

                    if (empty($idsSelecionados)) {
                        Notification::make()->title('Nenhum aluno selecionado.')->danger()->send();
                        return;
                    }

                    // Garante que todos os selecionados têm carteirinha = true
                    $temTodos = $records->every(fn($r) => (bool) $r->tem_carteirinha === true);
                    if (! $temTodos) {
                        Notification::make()
                            ->title('Seleção inválida')
                            ->body('Todos os alunos selecionados devem ter carteirinha para exportar.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $url = route('filament.admin.carteirinhas', [
                        'ids' => implode(',', $idsSelecionados),
                    ]);

                    $livewire->js('window.open("' . $url . '","_blank");');

                    Notification::make()
                        ->title('Página de impressão aberta com sucesso')
                        ->success()
                        ->send();
                }),
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
