<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CustoPorTurnoChart extends ApexChartWidget
{
    protected static ?string $chartId = 'custoPorTurnoChart';
    protected static ?string $heading = 'Custo por Turno';
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

    protected function getOptions(): array
    {
        [$rotulos, $series, $cores] = $this->resolverSeriesRotulosECores();

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'labels' => $rotulos,
            'series' => $series,
            'colors' => $cores,
            'legend' => ['position' => 'bottom'],
            'dataLabels' => ['enabled' => false],
            'tooltip' => ['enabled' => false],
            'stroke' => [
                'show' => true,
                'colors' => ['#FFFFFF'],
            ],
            'noData' => [
                'text' => 'Sem dados para exibir',
            ],
        ];
    }

    private function resolverSeriesRotulosECores(): array
    {
        $totaisPorTurno = Rota::query()
            ->selectRaw('turno, SUM(COALESCE(valor_total, 0)) as total')
            ->whereNotNull('turno')
            ->groupBy('turno')
            ->get();

        $valoresPorTurno = [];

        foreach ($totaisPorTurno as $registroTurno) {
            $turno = (string) $registroTurno->turno;

            if ($turno !== '') {
                $valoresPorTurno[$turno] = (float) $registroTurno->total;
            }
        }

        $valoresPorTurno = $this->garantirTurnosPadrao($valoresPorTurno);

        return $this->ordenarFormatarEAplicarCores($valoresPorTurno);
    }

    private function garantirTurnosPadrao(array $valoresPorTurno): array
    {
        $turnosPadrao = ['Manhã', 'Tarde', 'Noite'];

        foreach ($turnosPadrao as $turno) {
            $valoresPorTurno[$turno] = (float) ($valoresPorTurno[$turno] ?? 0);
        }

        return $valoresPorTurno;
    }

    private function ordenarFormatarEAplicarCores(array $valoresPorTurno): array
    {
        $coresPorTurno = [
            'Manhã' => '#22C55E',
            'Tarde' => '#1E87CE',
            'Noite' => '#A3BE28',
        ];

        $linhasOrdenadas = [];

        foreach ($valoresPorTurno as $turno => $valorTotal) {
            $linhasOrdenadas[] = [
                'turno' => $turno,
                'valor' => (float) $valorTotal,
            ];
        }

        usort(
            $linhasOrdenadas,
            fn (array $linhaTurnoA, array $linhaTurnoB) =>
                $linhaTurnoB['valor'] <=> $linhaTurnoA['valor']
        );

        $rotulos = [];
        $series  = [];
        $cores   = [];

        foreach ($linhasOrdenadas as $linhaTurno) {
            $rotulos[] = $linhaTurno['turno'] . ' — ' . $this->formatarBRL($linhaTurno['valor']);
            $series[]  = $linhaTurno['valor'];
            $cores[]   = $coresPorTurno[$linhaTurno['turno']] ?? '#22C55E';
        }

        return [$rotulos, $series, $cores];
    }

    private function formatarBRL(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}
