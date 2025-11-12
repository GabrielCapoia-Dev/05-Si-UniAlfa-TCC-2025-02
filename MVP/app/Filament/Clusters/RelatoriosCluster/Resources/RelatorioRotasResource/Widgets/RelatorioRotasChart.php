<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioRotasResource\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RelatorioRotasChart extends ApexChartWidget
{
    protected static ?string $chartId  = 'rotasMaisCarasChart';
    protected static ?string $heading  = 'Rotas Mais Caras';
    protected static ?int    $sort     = 3;
    protected int|string|array $columnSpan = 'full';

    // o widget "ouve" a página
    protected $listeners = ['rotasFiltradasAtualizadas' => 'onIdsAtualizados'];

    /** estado serializável */
    public array   $idsFiltradas = [];
    public bool    $hasFilters   = false;
    public ?string $turno        = null;

    // carrega já na primeira render
    protected static bool $deferLoading = false;

    /** recebe payload da página */
    public function onIdsAtualizados(array $idsRotas = [], bool $hasFilters = false, ?string $turno = null): void
    {
        $this->idsFiltradas = array_values($idsRotas ?? []);
        $this->hasFilters   = (bool) $hasFilters;
        $this->turno        = $turno ?: null;

        $this->updateOptions();
    }

    protected function getOptions(): array
    {
        [$rotulos, $valores] = $this->dadosDoGrafico();

        if ($this->hasFilters && $rotulos === []) {
            return [
                'chart'  => ['type' => 'bar', 'height' => 320, 'toolbar' => ['show' => false]],
                'series' => [],
                'xaxis'  => ['categories' => []],
                'noData' => ['text' => 'Sem dados para os filtros selecionados'],
            ];
        }

        return [
            'chart' => ['type' => 'bar', 'height' => 320, 'toolbar' => ['show' => false]],
            'series' => [
                ['name' => 'Custo da Rota', 'data' => $valores],
            ],
            'xaxis' => [
                'categories' => $rotulos,
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                    'maxWidth' => 400,
                    'minWidth' => 300,
                ],
            ],
            'colors' => ['#22C55E'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'columnWidth' => '60%',
                    'borderRadius' => 5,
                    'borderRadiusApplication' => 'end',
                ],
            ],
            'tooltip' => ['enabled' => true],
            'grid' => ['padding' => ['left' => 20]],
        ];
    }

    /** monta TOP 10 por valor_total, respeitando ids e turno */
    private function dadosDoGrafico(): array
    {
        $q = DB::table('rotas')
            ->when($this->idsFiltradas !== [], fn ($qq) => $qq->whereIn('rotas.id', $this->idsFiltradas))
            ->when($this->turno, fn ($qq) => $qq->where('rotas.turno', $this->turno))
            ->orderByDesc('rotas.valor_total')
            ->limit(10)
            ->get(['rotas.nome', 'rotas.valor_total']);

        $rotulos = [];
        $valores = [];

        foreach ($q as $row) {
            $rotulos[] = (string) $row->nome;
            $valores[] = (float) number_format((float) ($row->valor_total ?? 0), 2, '.', '');
        }

        return [$rotulos, $valores];
    }
}
