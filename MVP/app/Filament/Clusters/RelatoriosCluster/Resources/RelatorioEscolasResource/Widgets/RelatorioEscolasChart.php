<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolasResource\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RelatorioEscolasChart extends ApexChartWidget
{
    protected static ?string $chartId = 'escolasMaisCarasChart';
    protected static ?string $heading = 'Escolas Mais Caras';
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected $listeners = ['escolasFiltradasAtualizadas' => 'onIdsAtualizados'];

    public array $idsFiltradas = [];
    public bool $hasFilters = false;
    public ?string $turno = null;

    protected static bool $deferLoading = false;

    public function onIdsAtualizados(array $idsEscolas = [], bool $hasFilters = false, ?string $turno = null): void
    {
        $this->idsFiltradas = array_values($idsEscolas ?? []);
        $this->hasFilters   = (bool) $hasFilters;
        $this->turno        = $turno ?: null;

        $this->updateOptions(); // recarrega o gráfico
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
                ['name' => 'Valor por Escola', 'data' => $valores],
            ],
            'xaxis' => ['categories' => $rotulos, 'labels' => ['style' => ['fontFamily' => 'inherit']]],
            'yaxis' => [
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                    'maxWidth' => 400,
                    'minWidth' => 300,
                ]
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
            'grid' => [
                'padding' => [
                    'left' => 20, // Adiciona padding à esquerda para dar mais espaço
                ]
            ],
        ];
    }

    private function dadosDoGrafico(): array
    {
        $q = DB::table('escolas')
            ->leftJoin('escola_rota as er', 'er.escola_id', '=', 'escolas.id')
            ->leftJoin('rotas', 'rotas.id', '=', 'er.rota_id')
            ->when($this->idsFiltradas !== [], fn($qq) => $qq->whereIn('escolas.id', $this->idsFiltradas))
            ->when($this->turno, fn($qq) => $qq->where('rotas.turno', $this->turno))
            ->groupBy('escolas.id', 'escolas.nome')
            ->select([
                'escolas.id',
                'escolas.nome',
                DB::raw("
                    COALESCE(SUM(
                        rotas.valor_total / NULLIF(
                            (SELECT COUNT(*) FROM escola_rota er2 WHERE er2.rota_id = er.rota_id),
                            0
                        )
                    ), 0) as valor_rateado
                "),
            ])
            ->orderByDesc('valor_rateado')
            ->limit(10)
            ->get();

        $rotulos = [];
        $valores = [];

        foreach ($q as $row) {
            $rotulos[] = (string) $row->nome;
            $valores[] = (float) number_format((float) $row->valor_rateado, 2, '.', '');
        }

        return [$rotulos, $valores];
    }
}
