<?php

namespace App\Services;

use App\Models\Escola;
use App\Models\User;
use Filament\Tables;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RelatorioEscolaService
{
    /** Query base: apenas carrega relacionamentos necessários. */
    public function findEscolasData(): Builder
    {
        return Escola::query()
            ->with([
                'rotas:id,nome,turno,valor_total',
            ]);
    }

    public function configurarTabelaEscolas(Table $table, ?User $user): Table
    {
        return $table
            ->query(fn() => $this->findEscolasData())
            ->modifyQueryUsing(function (Builder $query) {
                // Turno atual do filtro da tabela (Filament v3)
                $turno = data_get(request()->all(), 'tableFilters.rotas.turno.value');

                // ---- Subqueries de apoio ----
                // Total de alunos por rota
                $alunosTotal = DB::table('alunos')
                    ->select('id_rota', DB::raw('COUNT(*) as total_alunos_rota'))
                    ->groupBy('id_rota');

                // Alunos por (rota, escola) — escola vem via turma
                $alunosPorEscola = DB::table('alunos as a')
                    ->join('turmas as t', 't.id', '=', 'a.id_turma')
                    ->select('a.id_rota', 't.id_escola as escola_id', DB::raw('COUNT(*) as alunos_na_escola'))
                    ->groupBy('a.id_rota', 't.id_escola');

                // Agregado proporcional por escola:
                // soma, para cada rota da escola, de (valor_total * alunos_na_escola / total_alunos_rota)
                $agregado = DB::table('escola_rota as er')
                    ->join('rotas', 'rotas.id', '=', 'er.rota_id')
                    ->leftJoinSub($alunosTotal, 'at', 'at.id_rota', '=', 'er.rota_id')
                    ->leftJoinSub($alunosPorEscola, 'ae', function ($join) {
                        $join->on('ae.id_rota', '=', 'er.rota_id')
                            ->on('ae.escola_id', '=', 'er.escola_id');
                    })
                    ->when($turno, fn($q) => $q->where('rotas.turno', $turno))
                    ->groupBy('er.escola_id')
                    ->select([
                        'er.escola_id',
                        DB::raw("
                            COALESCE(
                                SUM(
                                    rotas.valor_total * (
                                        COALESCE(ae.alunos_na_escola, 0)
                                        / NULLIF(COALESCE(at.total_alunos_rota, 0), 0)
                                    )
                                ), 0
                            ) as custo_total_escola
                        "),
                    ]);

                // Aplica o agregado na listagem e ordena MAIOR → MENOR
                $query->select('escolas.*')
                    ->leftJoinSub($agregado, 'agg', 'agg.escola_id', '=', 'escolas.id')
                    ->addSelect(DB::raw('COALESCE(agg.custo_total_escola, 0) AS custo_total_escola')) // << AQUI
                    ->reorder()
                    ->orderByDesc('custo_total_escola');
            })
            // Evita que um sort antigo da sessão sobrescreva nossa ordem
            ->persistSortInSession(false)
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabelaEscolas())
            ->filters($this->filtrosTabelaEscolas())
            ->bulkActions($this->acoesEmMassaEscolas($user))
            ->striped();
    }

    public function acoesEmMassaEscolas(?User $user): array
    {
        return [
            FilamentExportBulkAction::make('exportar_xlsx')
                ->label('Exportar XLSX')
                ->defaultFormat('xlsx')
                ->formatStates([
                    'tem_carteirinha' => fn($record) => $record->tem_carteirinha ? 'Sim' : 'Não',
                ])
                ->directDownload(),
            FilamentExportBulkAction::make('exportar_pdf')
                ->label('Exportar PDF')
                ->defaultFormat('pdf')
                ->color('danger')
                ->formatStates([
                    'tem_carteirinha' => fn($record) => $record->tem_carteirinha ? 'Sim' : 'Não',
                ])
                ->directDownload(),
        ];
    }

    // dentro de RelatorioEscolaService
    private function extrairTurno(mixed $state): ?string
    {
        return is_array($state) ? (data_get($state, 'value') ?? null) : ($state ?: null);
    }

    private function colunasTabelaEscolas(): array
    {
        return [
            Tables\Columns\TextColumn::make('nome')
                ->label('Escola')
                ->searchable(),

            Tables\Columns\TextColumn::make('rotas_list')
                ->label('Rotas')
                ->state(function (Escola $record, $livewire) {
                    $state = $livewire->getTableFiltersForm()?->getState();
                    $turno = $this->extrairTurno(data_get($state, 'rotas.turno'));

                    return $record->rotas()
                        ->when($turno, fn($q) => $q->where('turno', $turno))
                        ->orderBy('nome')
                        ->limit(5)
                        ->pluck('nome')
                        ->all();
                })
                ->badge()
                ->separator(' | ')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('valor_rateado')
                ->label('Valor por Escola')
                ->state(fn($record) => (float) ($record->custo_total_escola ?? 0)) // lê o alias hidratado
                ->formatStateUsing(fn($state) => 'R$ ' . number_format((float) $state, 2, ',', '.'))
                ->alignRight(),

        ];
    }

    private function filtrosTabelaEscolas(): array
    {
        return [
            \Filament\Tables\Filters\SelectFilter::make('rotas.turno')
                ->label('Turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                ])
                ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): void {
                    // $data pode ser ['value' => 'Manhã'] ou 'Manhã' conforme versão/config
                    $turno = $this->extrairTurno($data);
                    if (!$turno) return;

                    // Mantém o filtro "visual" coerente (o cálculo real está no modifyQueryUsing)
                    $query->whereHas('rotas', fn($q) => $q->where('turno', $turno));
                })
                ->indicateUsing(function ($state) {
                    $turno = $this->extrairTurno($state);
                    return $turno ? "Turno: {$turno}" : null;
                }),
        ];
    }
}
