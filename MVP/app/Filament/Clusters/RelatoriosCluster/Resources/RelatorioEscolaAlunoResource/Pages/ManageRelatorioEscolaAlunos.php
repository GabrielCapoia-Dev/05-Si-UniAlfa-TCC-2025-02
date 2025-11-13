<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource\Pages;

use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource;
use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource\Widgets\RelatorioEscolaAlunoChart;
use Filament\Resources\Pages\ManageRecords;

class ManageRelatorioEscolaAlunos extends ManageRecords
{
    protected static string $resource = RelatorioEscolaAlunoResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            RelatorioEscolaAlunoChart::class,
        ];
    }

    /** Dispara os IDs das escolas atualmente listadas (respeita busca/filtros/página) */
    protected function dispatchChartPayload(): void
    {
        $query = $this->getFilteredTableQuery();

        $idsEscolas = $query->pluck('escolas.id')->values()->all();

        $hasFilters = $this->computeHasFiltersSafely();

        // captura o turno do estado dos filtros da tabela
        $filtersState = $this->getTableFiltersForm()?->getState() ?? [];
        $turno = data_get($filtersState, 'rotas.turno.value');

        // envia para o widget
        $this->dispatch('escolasFiltradasAtualizadas', $idsEscolas, $hasFilters, $turno);
    }

    /** Detecta busca/filtros em uso (sem lançar erros) */
    protected function computeHasFiltersSafely(): bool
    {
        $hasSearch = !empty($this->tableSearch ?? '');

        try {
            $filtersState = $this->getTableFiltersForm()?->getState() ?? [];
        } catch (\Throwable $e) {
            $filtersState = [];
        }

        $hasFilterValues = false;
        foreach ($filtersState as $value) {
            if (is_array($value)) {
                if (array_filter($value) !== []) { $hasFilterValues = true; break; }
            } elseif (!empty($value)) {
                $hasFilterValues = true; break;
            }
        }

        return $hasSearch || $hasFilterValues;
    }

    /** Dispara o evento quando qualquer coisa da tabela mudar */
    public function updatedTableFilters(): void
    {
        parent::updatedTableFilters();
        $this->dispatchChartPayload();
    }
    public function updatedTableSearch(): void
    {
        parent::updatedTableSearch();
        $this->dispatchChartPayload();
    }
    public function updatedTableSortColumn(): void
    {
        parent::updatedTableSortColumn();
        $this->dispatchChartPayload();
    }
    public function updatedTableSortDirection(): void
    {
        parent::updatedTableSortDirection();
        $this->dispatchChartPayload();
    }
    public function updatedTableRecordsPerPage(): void
    {
        parent::updatedTableRecordsPerPage();
        $this->dispatchChartPayload();
    }
    public function updatedTablePage(): void
    {
        parent::updatedTablePage();
        $this->dispatchChartPayload();
    }

    /** Opcional: já dispara na primeira renderização */
    protected function afterTableLoaded(): void
    {
        $this->dispatchChartPayload();
    }

    /** Este relatório não cria registros, então sem ações de header */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
