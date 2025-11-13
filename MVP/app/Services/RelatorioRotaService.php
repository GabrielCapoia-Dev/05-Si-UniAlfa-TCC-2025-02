<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rota;
use Filament\Tables;
use Filament\Tables\Table;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use Illuminate\Database\Eloquent\Builder;

class RelatorioRotaService
{
    /** Query base para Rotas */
    public function findRotasData(): Builder
    {
        return Rota::query();
    }

    /** Tabela de Rotas (sempre ordenada do maior custo para o menor) */
    public function configurarTabelaRotas(Table $table, ?User $user): Table
    {
        return $table
            ->query(fn() => $this->findRotasData())
            ->modifyQueryUsing(function (Builder $query) {
                // lê o filtro de turno aplicado na tabela (Filament v3)
                $turno = data_get(request()->all(), 'tableFilters.turno.value');

                $query
                    ->when($turno, fn($q) => $q->where('turno', $turno))
                    ->reorder()                 // zera ordenações prévias
                    ->orderByDesc('valor_total'); // maior → menor
            })
            ->persistSortInSession(false)
            ->paginated([10, 25, 50, 100])
            ->columns($this->colunasTabelaRotas())
            ->bulkActions($this->acoesEmMassaRotas($user))
            ->filters($this->filtrosTabelaRotas())
            ->striped();
    }

    private function colunasTabelaRotas(): array
    {
        return [
            Tables\Columns\TextColumn::make('nome')
                ->label('Rota')
                ->searchable(),

            Tables\Columns\TextColumn::make('turno')
                ->label('Turno'),

            Tables\Columns\TextColumn::make('distancia_total')
                ->label('Distância (km)')
                ->state(fn(\App\Models\Rota $r) => (float) ($r->distancia_total ?? 0))
                ->formatStateUsing(fn($state) => number_format((float) $state, 2, ',', '.'))
                ->alignRight(),

            Tables\Columns\TextColumn::make('tempo_estimado')
                ->label('Tempo')
                ->state(function (\App\Models\Rota $r) {
                    $min = (int) ($r->tempo_estimado ?? 0);
                    $h = intdiv($min, 60);
                    $m = $min % 60;
                    return sprintf('%02dh%02dm', $h, $m);
                })
                ->alignRight(),


            Tables\Columns\TextColumn::make('custo_por_km')
                ->label('Custo por Km')
                ->state(function (\App\Models\Rota $r) {
                    $vpk  = (float) ($r->valor_por_km ?? 0);
                    if ($vpk <= 0) {
                        $dist  = (float) ($r->distancia_total ?? 0);
                        $total = (float) ($r->valor_total ?? 0);
                        $vpk   = $dist > 0 ? ($total / $dist) : 0.0;
                    }
                    return $vpk;
                })
                ->formatStateUsing(fn($state) => 'R$ ' . number_format((float) $state, 2, ',', '.'))
                ->alignRight(),

            Tables\Columns\TextColumn::make('valor_total')
                ->label('Custo da Rota')
                ->state(fn(\App\Models\Rota $r) => (float) ($r->valor_total ?? 0))
                ->formatStateUsing(fn($state) => 'R$ ' . number_format((float) $state, 2, ',', '.'))
                ->alignRight(), // sem ->sortable() para manter a ordem fixa
        ];
    }

    private function filtrosTabelaRotas(): array
    {
        return [
            \Filament\Tables\Filters\SelectFilter::make('turno')
                ->label('Turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                ])
                ->query(function (Builder $query, array $data): void {
                    $turno = is_array($data) ? ($data['value'] ?? null) : ($data ?: null);
                    if ($turno) {
                        $query->where('turno', $turno);
                    }
                })
                ->indicateUsing(
                    fn($state) => (is_array($state) ? ($state['value'] ?? null) : ($state ?: null))
                        ? 'Filtrado por turno' : null
                ),
        ];
    }

    public function acoesEmMassaRotas(?User $user): array
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
}
