<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use App\Models\ValorRotaMensal;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\On;

class RotasMaisCarasChart extends ApexChartWidget
{
    protected static ?string $chartId = 'rotasMaisCarasChart';
    protected static ?string $heading = 'Gráfico de Rotas Mais Caras';

    /** Guarda competência selecionada por evento (sem reload) */
    public ?int $mesSelecionado = null;
    public ?int $anoSelecionado = null;

    #[On('competencia-atualizada')]
    public function onCompetenciaAtualizada(int $mes, int $ano): void
    {
        $this->mesSelecionado = $mes;
        $this->anoSelecionado = $ano;

    }
    private function resolverCategoriasEValores(): array
    {
        if ($this->mesSelecionado && $this->anoSelecionado) {
            $pares = $this->pairsDoSnapshot($this->mesSelecionado, $this->anoSelecionado);
            if (!empty($pares)) {
                return $this->ordenarEFormatar($pares);
            }
        }

        $mesAno = request()->string('mes_ano')->toString();
        if ($mesAno && preg_match('/^(\d{2})\/(\d{4})$/', $mesAno, $m)) {
            $pares = $this->pairsDoSnapshot((int) $m[1], (int) $m[2]);
            if (!empty($pares)) {
                return $this->ordenarEFormatar($pares);
            }
        }

        $mes = request()->integer('mes');
        $ano = request()->integer('ano');
        if ($mes && $ano) {
            $pares = $this->pairsDoSnapshot($mes, $ano);
            if (!empty($pares)) {
                return $this->ordenarEFormatar($pares);
            }
        }

        $rotas = Rota::query()
            ->orderByDesc('valor_total')
            ->limit(10)
            ->get(['id', 'nome', 'valor_total']);

        $pares = $rotas->map(fn($r) => [
            'name'  => (string) $r->nome,
            'value' => (float) $r->valor_total,
        ])->all();

        return $this->ordenarEFormatar($pares);
    }

    /** Busca snapshot e devolve pares [name, value] */
    private function pairsDoSnapshot(int $mes, int $ano): array
    {
        $snap = ValorRotaMensal::where('mes', $mes)->where('ano', $ano)->first();
        if (!$snap || !is_array($snap->valor_total_por_rota) || empty($snap->valor_total_por_rota)) {
            return [];
        }

        $rows = $snap->valor_total_por_rota;
        $ids  = array_column($rows, 'rota_id');
        $vals = array_map('floatval', array_column($rows, 'valor_total'));
        $nomes = Rota::whereIn('id', $ids)->pluck('nome', 'id')->all();

        $pares = [];
        foreach ($ids as $idx => $id) {
            $pares[] = [
                'name'  => $nomes[$id] ?? ('Rota #' . $id),
                'value' => $vals[$idx] ?? 0.0,
            ];
        }
        return $pares;
    }

    /** Ordena desc por value e formata labels com BRL */
    private function ordenarEFormatar(array $pares): array
    {
        usort($pares, fn($a, $b) => $b['value'] <=> $a['value']);

        $labels = [];
        $data   = [];
        foreach ($pares as $p) {
            $labels[] = $p['name'];
            $data[]   = (float) $p['value'];
        }
        return [$labels, $data];
    }
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
                    'show' => false,
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
        ];
    }
}
