<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use App\Models\ValorRotaMensal;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\On;

class CustoPorTurnoChart extends ApexChartWidget
{
    protected static ?string $chartId = 'custoPorTurnoChart';
    protected static ?string $heading = 'Custo por Turno';

    public ?int $mesSelecionado = null;
    public ?int $anoSelecionado = null;

    #[On('competencia-atualizada')]
    public function onCompetenciaAtualizada(int $mes, int $ano): void
    {
        $this->mesSelecionado = $mes;
        $this->anoSelecionado = $ano;
    }

    protected function getOptions(): array
    {
        [$labels, $series, $colors] = $this->resolverSeriesLabelsECores();

        return [
            'chart'   => ['type' => 'pie', 'height' => 300, 'toolbar' => ['show' => false]],
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
        if ($this->mesSelecionado && $this->anoSelecionado) {
            $map = $this->totaisPorTurnoDoSnapshot($this->mesSelecionado, $this->anoSelecionado);
            if (!empty($map)) {
                return $this->ordenarFormatarEColorir($map);
            }
        }

        $mesAno = request()->string('mes_ano')->toString();
        if ($mesAno && preg_match('/^(\d{2})\/(\d{4})$/', $mesAno, $m)) {
            $map = $this->totaisPorTurnoDoSnapshot((int) $m[1], (int) $m[2]);
            if (!empty($map)) {
                return $this->ordenarFormatarEColorir($map);
            }
        }

        $mes = request()->integer('mes');
        $ano = request()->integer('ano');
        if ($mes && $ano) {
            $map = $this->totaisPorTurnoDoSnapshot($mes, $ano);
            if (!empty($map)) {
                return $this->ordenarFormatarEColorir($map);
            }
        }

        $ordem = Rota::query()
            ->whereNotNull('turno')
            ->select('turno')
            ->distinct()
            ->orderByDesc('turno')
            ->pluck('turno')
            ->values()
            ->all();


        $totais = Rota::query()
            ->selectRaw('turno, SUM(COALESCE(valor_total, 0)) as total')
            ->whereIn('turno', $ordem)
            ->groupBy('turno')
            ->pluck('total', 'turno')
            ->map(fn($v) => (float) $v)
            ->all();

        foreach ($ordem as $t) {
            $totais[$t] = (float) ($totais[$t] ?? 0);
        }

        return $this->ordenarFormatarEColorir($totais);
    }

    private function totaisPorTurnoDoSnapshot(int $mes, int $ano): array
    {
        $snap = ValorRotaMensal::where('mes', $mes)->where('ano', $ano)->first();
        if (!$snap) {
            return [];
        }

        if (is_array($snap->valor_total_por_turno) && !empty($snap->valor_total_por_turno)) {
            $map = [];
            foreach ($snap->valor_total_por_turno as $row) {
                $turno = (string) ($row['turno'] ?? '');
                $val   = (float)  ($row['valor_total'] ?? 0);
                if ($turno !== '') {
                    $map[$turno] = ($map[$turno] ?? 0) + $val;
                }
            }
            return $this->completarTurnos($map);
        }

        if (is_array($snap->valor_total_por_rota) && !empty($snap->valor_total_por_rota)) {
            $rows = $snap->valor_total_por_rota;
            $ids  = array_column($rows, 'rota_id');
            $vals = array_map('floatval', array_column($rows, 'valor_total'));
            $turnos = Rota::whereIn('id', $ids)->pluck('turno', 'id')->all();

            $map = ['Manhã' => 0.0, 'Tarde' => 0.0, 'Noite' => 0.0, 'Integral' => 0.0];
            foreach ($ids as $i => $id) {
                $t = $turnos[$id] ?? null;
                if ($t && isset($map[$t])) {
                    $map[$t] += $vals[$i] ?? 0.0;
                }
            }
            return $map;
        }

        return [];
    }

    /** Garante que todas as chaves de turno existam */
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
