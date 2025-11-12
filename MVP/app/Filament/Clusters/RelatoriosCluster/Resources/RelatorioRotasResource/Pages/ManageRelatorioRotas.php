<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioRotasResource\Pages;

use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioRotasResource;
use App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioRotasResource\Widgets\RelatorioRotasChart;
use Filament\Resources\Pages\ManageRecords;

class ManageRelatorioRotas extends ManageRecords
{
    protected static string $resource = RelatorioRotasResource::class;

    protected function getHeaderWidgets(): array
    {
        return [RelatorioRotasChart::class];
    }

    /** dispara os IDs de rotas atualmente listados (+ turno + flag de filtros) */
    protected function dispatchChartPayload(): void
    {
        // mesma query da tabela já com busca/filtros/paginação aplicados
        $query = $this->getFilteredTableQuery();

        // ids das rotas na listagem atual
        $idsRotas = $query->pluck('rotas.id')->values()->all();

        // detecta se há busca/filtros ativos
        $hasFilters = $this->computeHasFiltersSafely();

        // pega turno do estado de filtros (sua tabela usa SelectFilter::make('turno'))
        $filtersState = $this->getTableFiltersForm()?->getState() ?? [];
        $turno = data_get($filtersState, 'turno.value');

        // dispara para o widget
        $this->dispatch('rotasFiltradasAtualizadas', $idsRotas, $hasFilters, $turno);
    }

    /** igual ao que você já usa em escolas */
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
            } elseif (!empty($value)) { $hasFilterValues = true; break; }
        }

        return $hasSearch || $hasFilterValues;
    }

    /** dispare o evento quando a tabela mudar */
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

    /** dispara na primeira renderização */
    protected function afterTableLoaded(): void
    {
        $this->dispatchChartPayload();
    }
}
