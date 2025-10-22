<?php

namespace App\Services;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
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
use Illuminate\Support\Facades\Auth;
use Filament\Actions;

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

    /** Configura a tabela completa (paginações, colunas, filtros, ações, ordenação). */
    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela($user))
            ->actions($this->acoesTabela())
            ->bulkActions($this->acoesEmMassa($user))
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }

    /** Desabilita o select de escola para usuário vinculado a uma escola (não-Admin). */
    public function deveTravarCampoEscola(?User $user): bool
    {
        return ! app(UserService::class)->ehAdmin($user) && filled($user?->id_escola);
    }

    public function escolaInicialParaForm(?Aluno $record, ?User $user): ?int
    {
        return $record?->turma?->id_escola ?? ($user?->id_escola ?? null);
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

    /** Busca alunos para listagem. */
    public function buscarAlunosParaListagem(?User $user): Builder
    {
        $query = app(ListAlunos::class)->getResource()::getEloquentQuery()
            ->with(['turma.escola', 'turma.serie']); 

        if ($user && (!$user->hasRole('Admin')) && !empty($user->id_escola)) {
            $query->whereHas('turma', function (Builder $t) use ($user) {
                $t->where($t->getModel()->getTable() . '.id_escola', $user->id_escola);
            });
        }

        return $query;
    }

    /** Ações do cabeçario da tabela. */
    public function validarAcoesCabecario(): array
    {
        $hasEscolas = Escola::exists();
        $hasTurmas = Turma::exists();

        if ($hasEscolas && $hasTurmas) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        $actions = [];

        if (! $hasEscolas) {
            $actions[] = Actions\Action::make('Cadastrar Escolas')
                ->button()
                ->color('danger')
                ->icon('heroicon-o-building-library')
                ->url(route('filament.admin.resources.escolas.index'));

            return $actions;
        }

        if ( $hasEscolas && !$hasTurmas) {
            $actions[] = Actions\Action::make('Cadastrar Turmas')
                ->button()
                ->color('danger')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(route('filament.admin.resources.turmas.index'));

            return $actions;
        }

        return $actions;
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

    /** Colunas da listagem de alunos. */
    private function colunasTabela(): array
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

            Tables\Columns\TextColumn::make('turma.escola.nome')
                ->label('Escola')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),


            Tables\Columns\TextColumn::make('turma.serie.nome')
                ->label('Série')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),


            Tables\Columns\TextColumn::make('turma.turma')
                ->label('Turma')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('turma.turno')
                ->label('Turno')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),


            Tables\Columns\TextColumn::make('nome_responsavel')
                ->label('Nome Responsavel')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('telefone_responsavel')
                ->label('Telefone Responsável')
                ->sortable()
                ->searchable()
                ->icon('heroicon-o-phone')
                ->getStateUsing(function ($record) {
                    $telefone = $record->telefone_responsavel;
                    return $this->regexTelefone($telefone);
                })
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('telefone_aluno')
                ->label('Telefone Aluno')
                ->sortable()
                ->searchable()
                ->icon('heroicon-o-phone')
                ->getStateUsing(function ($record) {
                    $telefone = $record->telefone_aluno;
                    return $this->regexTelefone($telefone);
                })
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('telefone_alternativo')
                ->label('Telefone')
                ->sortable()
                ->searchable()
                ->icon('heroicon-o-phone')
                ->getStateUsing(function ($record) {
                    $telefone = $record->telefone_alternativo;
                    return $this->regexTelefone($telefone);
                })
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('logradouro')
                ->label('Logradouro')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('bairro')
                ->label('Bairro')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('cidade')
                ->label('Cidade')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('numero')
                ->label('Número')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('cep')
                ->label('CEP')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('complemento')
                ->label('Complemento')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

        ];
    }

    /** Filtros da listagem de alunos. */
    private function filtrosTabela(?User $user): array
    {
        return [
            SelectFilter::make('id_escola')
                ->label('Escola')
                ->relationship('turma.escola', 'nome')
                ->visible(fn() => app(UserService::class)->ehAdmin($user))
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

            SelectFilter::make('id_turma')
                ->label('Turma')
                ->searchable()
                ->preload()
                ->options($this->opcoesTurmaFiltro())
                ->query(
                    fn(Builder $query, array $data) =>
                    !empty($data['value']) ? $query->where('id_turma', $data['value']) : null
                ),

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

    /** Opções de turma para filtro. */
    private function opcoesTurmaFiltro(): array
    {

        $idEscola = Auth::user()?->id_escola;
        if (!$idEscola) return [];

        return Turma::with('serie')
            ->where('id_escola', $idEscola)
            ->get()
            ->filter(fn($t) => $t->serie)
            ->mapWithKeys(fn($t) => [
                $t->id => "{$t->serie->nome} - {$t->turma}",
            ])
            ->toArray();
    }

    /** Ações da tabela. */
    private function acoesTabela(): array
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

    /** regex na coluna de telefone da tabela */
    public function regexTelefone($telefone)
    {
        $numeros = preg_replace('/\D/', '', $telefone);
        if (strlen($numeros) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($numeros, 0, 2),
                substr($numeros, 2, 5),
                substr($numeros, 7, 4)
            );
        }

        if (strlen($numeros) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($numeros, 0, 2),
                substr($numeros, 2, 4),
                substr($numeros, 6, 4)
            );
        }
        return $telefone;
    }

    /** Ações em massa da tabela. */
    private function acoesEmMassa(?User $user): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),

            FilamentExportBulkAction::make('exportar_filtrados')
                ->label('Exportar XLSX')
                ->defaultFormat('xlsx')
                ->formatStates([
                    'tem_carteirinha' => fn($record) => $record->tem_carteirinha ? 'Sim' : 'Não',
                ])
                ->directDownload(),

            BulkAction::make('exportar_carteirinhas_html')
                ->label('Imprimir Carteirinhas')
                ->color('info')
                ->icon('heroicon-o-printer')
                ->visible(function (ListAlunos $livewire) {
                    return !$livewire->getFilteredTableQuery()
                        ->clone()
                        ->where('tem_carteirinha', false)
                        ->exists();
                })
                ->requiresConfirmation()
                ->modalHeading('Abrir pagina para impressão?')
                ->action(function (Collection $records, ListAlunos $livewire) {
                    $idsSelecionados = $records->pluck('id')->all();

                    if (empty($idsSelecionados)) {
                        Notification::make()->title('Nenhum aluno selecionado.')->danger()->send();
                        return;
                    }

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
