<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CustoPorTurnoChart extends ApexChartWidget
{
    protected static ?string $chartId = 'custoPorTurnoChart';
    protected static ?string $heading = 'Custo por Turno (Tempo Real)';
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

    protected function getOptions(): array
    {
        [$labels, $series, $colors] = $this->resolverSeriesLabelsECores();

        return [
            'chart'   => [
                'type' => 'pie', 
                'height' => 300, 
                'toolbar' => ['show' => false]
            ],
            'labels'  => $labels,
            'series'  => $series,
            'colors'  => $colors,
            'legend'  => ['position' => 'bottom'],
            'dataLabels' => ['enabled' => false],
            'tooltip' => ['enabled' => false],
            'stroke'  => ['show' => true, 'colors' => ['#FFFFFF']],
            'noData'  => ['text' => 'Sem dados para exibir'],
        ];
    }

    private function resolverSeriesLabelsECores(): array
    {
        $totais = Rota::query()
            ->selectRaw('turno, SUM(COALESCE(valor_total, 0)) as total')
            ->whereNotNull('turno')
            ->groupBy('turno')
            ->get();

        $map = [];
        foreach ($totais as $item) {
            $turno = (string) $item->turno;
            if ($turno !== '') {
                $map[$turno] = (float) $item->total;
            }
        }

        $map = $this->completarTurnos($map);

        return $this->ordenarFormatarEColorir($map);
    }

    private function completarTurnos(array $map): array
    {
        foreach (['Manhã', 'Tarde', 'Noite', 'Integral'] as $t) {
            $map[$t] = (float) ($map[$t] ?? 0);
        }
        return $map;
    }

    private function ordenarFormatarEColorir(array $map): array
    {
        $coresPorTurno = [
            'Manhã'    => '#22C55E',
            'Tarde'    => '#1E87CE',
            'Noite'    => '#A3BE28',
            'Integral' => '#CA401D',
        ];

        $rows = [];
        foreach ($map as $turno => $valor) {
            $rows[] = ['turno' => $turno, 'valor' => (float) $valor];
        }
        usort($rows, fn($a, $b) => $b['valor'] <=> $a['valor']);

        $labels = [];
        $series = [];
        $colors = [];
        foreach ($rows as $r) {
            $labels[] = $r['turno'] . ' — ' . $this->formatBRL($r['valor']);
            $series[] = $r['valor'];
            $colors[] = $coresPorTurno[$r['turno']] ?? '#22C55E';
        }

        return [$labels, $series, $colors];
    }

    private function formatBRL(float $v): string
    {
        return 'R$ ' . number_format($v, 2, ',', '.');
    }
}