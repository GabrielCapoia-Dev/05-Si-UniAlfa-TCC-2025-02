<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class EscolasMaisCarasChart extends ApexChartWidget
{
    protected static ?string $chartId = 'escolasMaisCarasChart';
    protected static ?string $heading = 'Escolas Mais Caras';
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '30s';

    protected function getOptions(): array
    {
        [$labels, $data] = $this->resolverCategoriasEValores();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 450,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Valor por Escola',
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                ],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'colors' => ['#22C55E'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'columnWidth' => '75%',
                    'borderRadius' => 5,
                    'borderRadiusApplication' => 'end',
                ],
            ],
            'tooltip' => ['enabled' => false],
        ];
    }

    private function resolverCategoriasEValores(): array
    {
        $rotas = Rota::with(['escolas:id,nome'])
            ->whereHas('escolas')
            ->get(['id', 'valor_total']);

        $acum = [];
        $nomes = [];

        foreach ($rotas as $rota) {
            $valor = (float) ($rota->valor_total ?? 0);
            $ids = $rota->escolas->pluck('id')->all();
            $names = $rota->escolas->pluck('nome', 'id')->all();
            $nomes += $names;

            $qtd = count($ids);
            if ($qtd <= 0) {
                continue;
            }
            
            $quota = $valor / $qtd;
            foreach ($ids as $eid) {
                $acum[$eid] = ($acum[$eid] ?? 0) + $quota;
            }
        }

        $pares = [];
        foreach ($acum as $eid => $tot) {
            $pares[] = [
                'name'  => (string) ($nomes[$eid] ?? "Escola #$eid"),
                'value' => (float) $tot,
            ];
        }

        return $this->ordenarEFormatar($pares);
    }

    private function ordenarEFormatar(array $pares): array
    {
        usort($pares, fn($a, $b) => $b['value'] <=> $a['value']);
        $pares = array_slice($pares, 0, 10);

        $labels = [];
        $data   = [];
        foreach ($pares as $p) {
            $labels[] = $p['name'] . ' — ' . $this->formatBRL($p['value']);
            $data[]   = (float) number_format($p['value'], 2, '.');
        }
        return [$labels, $data];
    }

    private function formatBRL(float $v): string
    {
        return 'R$ ' . number_format($v, 2, ',', '.');
    }
}