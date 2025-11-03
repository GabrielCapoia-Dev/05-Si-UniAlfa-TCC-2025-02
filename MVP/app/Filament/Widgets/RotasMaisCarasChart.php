<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RotasMaisCarasChart extends ApexChartWidget
{
    protected static ?string $chartId = 'rotasMaisCarasChart';
    protected static ?string $heading = 'Rotas Mais Caras (Tempo Real)';
    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = '30s';

    protected function getOptions(): array
    {
        [$labels, $data] = $this->resolverCategoriasEValores();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 285,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Valor da Rota',
                    'data' => $data,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '75%',
                    'borderRadius' => 5,
                    'borderRadiusApplication' => 'end',
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'colors' => ['#22C55E'],
            'tooltip' => ['enabled' => false],
        ];
    }

    private function resolverCategoriasEValores(): array
    {
        $rotas = Rota::query()
            ->orderByDesc('valor_total')
            ->limit(10)
            ->get(['nome', 'valor_total']);

        $labels = [];
        $data   = [];
        
        foreach ($rotas as $rota) {
            $labels[] = $rota->nome;
            $data[]   = (float) $rota->valor_total;
        }

        return [$labels, $data];
    }
}