<?php

namespace App\Services;

use App\Models\Escola;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RelatorioEscolaAlunosService
{
    public function findEscolasData(): Builder
    {
        return Escola::query();
    }

    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->query(fn() => $this->findEscolasData())
            ->modifyQueryUsing(function (Builder $query) {
                $turno = data_get(request()->all(), 'tableFilters.rotas.turno.value');

                $alunosTotal = DB::table('alunos')
                    ->select('id_rota', DB::raw('COUNT(*) as total_alunos_rota'))
                    ->groupBy('id_rota');

                $alunosPorEscola = DB::table('alunos')
                    ->join('turmas', 'turmas.id', '=', 'alunos.id_turma')
                    ->select([
                        'alunos.id_rota',
                        'turmas.id_escola as escola_id',
                        DB::raw('COUNT(*) as alunos_na_escola'),
                    ])
                    ->groupBy('alunos.id_rota', 'turmas.id_escola');

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
                        DB::raw('COALESCE(SUM(COALESCE(ae.alunos_na_escola, 0)), 0) as alunos_atendidos'),
                        DB::raw('COALESCE(SUM( rotas.valor_total * ( COALESCE(ae.alunos_na_escola,0) / NULLIF(COALESCE(at.total_alunos_rota,0),0) ) ), 0) as custo_total_escola'),
                    ]);

                $query->select('escolas.*')
                    ->leftJoinSub($agregado, 'agg', 'agg.escola_id', '=', 'escolas.id')
                    ->addSelect([
                        'agg.alunos_atendidos',
                        'agg.custo_total_escola',
                        DB::raw('CASE WHEN COALESCE(agg.alunos_atendidos,0) > 0 THEN agg.custo_total_escola / agg.alunos_atendidos ELSE 0 END as custo_por_aluno'),
                    ])
                    ->reorder()
                    ->orderByDesc('agg.custo_total_escola');
            })
            ->persistSortInSession(false)
            ->bulkActions($this->acoesEmMassa($user))
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunas())
            ->filters($this->filtros())
            ->striped();
    }

    private function acoesEmMassa(?User $user): array
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

    private function colunas(): array
    {
        return [
            Tables\Columns\TextColumn::make('nome')
                ->label('Escola')
                ->searchable(),

            Tables\Columns\TextColumn::make('alunos_atendidos')
                ->label('Alunos Atendidos')
                ->state(fn($record) => (int) ($record->alunos_atendidos ?? 0))
                ->numeric()
                ->alignRight(),

            Tables\Columns\TextColumn::make('custo_total_escola')
                ->label('Custo Total (Escola)')
                ->state(fn($record) => (float) ($record->custo_total_escola ?? 0))
                ->formatStateUsing(static fn(mixed $state): string => 'R$ ' . number_format((float) $state, 2, ',', '.'))
                ->alignRight(),

            Tables\Columns\TextColumn::make('custo_por_aluno')
                ->label('Custo por Aluno')
                ->state(fn($record) => (float) ($record->custo_por_aluno ?? 0))
                ->formatStateUsing(static fn(mixed $state): string => 'R$ ' . number_format((float) $state, 2, ',', '.'))
                ->alignRight(),
        ];
    }

    private function filtros(): array
    {
        return [
            \Filament\Tables\Filters\SelectFilter::make('rotas.turno')
                ->label('Turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                ])
                ->query(function (Builder $query, array $data): void {
                    $turno = is_array($data) ? ($data['value'] ?? null) : ($data ?: null);
                    if (! $turno) return;

                    $query->whereHas('rotas', fn($q) => $q->where('turno', $turno));
                })
                ->indicateUsing(function ($state) {
                    $turno = is_array($state) ? ($state['value'] ?? null) : ($state ?: null);
                    return $turno ? "Turno: {$turno}" : null;
                }),
        ];
    }
}
