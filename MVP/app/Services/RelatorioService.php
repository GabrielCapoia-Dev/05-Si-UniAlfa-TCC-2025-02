<?php

namespace App\Services;

use App\Models\Escola;
use App\Models\User;
use Filament\Tables;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RelatorioService
{
    /** Query base: apenas carrega relacionamentos necessários. */
    public function findEscolasData(): Builder
    {
        return Escola::query()
            ->with([
                'rotas:id,nome,turno,valor_total',
            ]);
    }

    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->query(fn() => $this->findEscolasData())
            ->modifyQueryUsing(function (Builder $query) {
                // turno atual do filtro da tabela (Filament v3)
                $turno = data_get(request()->all(), 'tableFilters.rotas.turno.value');

                // subselect correlacionado que calcula o rateio por escola (respeita o turno se houver)
                $sub = DB::table('escola_rota as er')
                    ->join('rotas', 'rotas.id', '=', 'er.rota_id')
                    ->whereColumn('er.escola_id', 'escolas.id')
                    ->when($turno, fn($q) => $q->where('rotas.turno', $turno))
                    ->selectRaw("
                    COALESCE(SUM(
                        rotas.valor_total / NULLIF(
                            (SELECT COUNT(*) FROM escola_rota er2 WHERE er2.rota_id = er.rota_id),
                            0
                        )
                    ), 0)
                ");

                // ⚠️ limpa qualquer ORDER BY anterior e impõe a ordem desejada (maior → menor)
                $query->reorder()
                    ->orderByRaw('(' . $sub->toSql() . ') DESC', $sub->getBindings());
            })
            // evita que um sort antigo da sessão sobrescreva nossa ordem
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
            FilamentExportBulkAction::make('exportar_filtrados')
                ->label('Exportar XLSX')
                ->defaultFormat('xlsx')
                ->formatStates([
                    'tem_carteirinha' => fn($record) => $record->tem_carteirinha ? 'Sim' : 'Não',
                ])
                ->directDownload(),
        ];
    }


    // dentro de RelatorioService
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
                ->state(function (Escola $record, $livewire) {
                    $state = $livewire->getTableFiltersForm()?->getState();
                    $turno = data_get($state, 'rotas.turno.value');

                    $rotas = $record->rotas()
                        ->when($turno, fn($q) => $q->where('turno', $turno))
                        ->withCount('escolas')
                        ->get(['rotas.id', 'rotas.valor_total']);

                    $valor = $rotas->sum(function ($rota) {
                        $den = max(1, (int) ($rota->escolas_count ?? 0));
                        return (float) ($rota->valor_total ?? 0) / $den;
                    });

                    return $valor;
                })
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

                    $query->whereHas('rotas', fn($q) => $q->where('turno', $turno));
                })
                ->indicateUsing(function ($state) {
                    $turno = $this->extrairTurno($state);
                    return $turno ? "Turno: {$turno}" : null;
                }),
        ];
    }
}
