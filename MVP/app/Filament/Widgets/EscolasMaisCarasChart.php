<?php

namespace App\Filament\Widgets;

use App\Models\Escola;
use App\Models\Rota;
use App\Models\ValorRotaMensal;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\On;

class EscolasMaisCarasChart extends ApexChartWidget
{
    protected static ?string $chartId = 'escolasMaisCarasChart';
    protected static ?string $heading = 'Escolas Mais Caras';

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
        if ($this->mesSelecionado && $this->anoSelecionado) {
            $pares = $this->paresPorEscolaDoSnapshot($this->mesSelecionado, $this->anoSelecionado);
            if (!empty($pares)) {
                return $this->ordenarEFormatar($pares);
            }
        }

        // 2) via ?mes_ano
        $mesAno = request()->string('mes_ano')->toString();
        if ($mesAno && preg_match('/^(\d{2})\/(\d{4})$/', $mesAno, $m)) {
            $pares = $this->paresPorEscolaDoSnapshot((int) $m[1], (int) $m[2]);
            if (!empty($pares)) {
                return $this->ordenarEFormatar($pares);
            }
        }

        // 3) via ?mes=&ano=
        $mes = request()->integer('mes');
        $ano = request()->integer('ano');
        if ($mes && $ano) {
            $pares = $this->paresPorEscolaDoSnapshot($mes, $ano);
            if (!empty($pares)) {
                return $this->ordenarEFormatar($pares);
            }
        }

        return $this->ordenarEFormatar($this->paresPorEscolaDoEstadoAtual());
    }

    /**
     * Snapshot do mês: usa valor_total_por_rota e reparte por escola da rota.
     * Retorna lista de pares ['name'=>nome_escola, 'value'=>total_estimado]
     */
    private function paresPorEscolaDoSnapshot(int $mes, int $ano): array
    {
        $snap = ValorRotaMensal::where('mes', $mes)->where('ano', $ano)->first();
        if (!$snap || !is_array($snap->valor_total_por_rota) || empty($snap->valor_total_por_rota)) {
            return [];
        }

        $valorTotalRota = $snap->valor_total_por_rota;
        $rotaIds = array_column($valorTotalRota, 'rota_id');
        $valores = array_map('floatval', array_column($valorTotalRota, 'valor_total'));

        $rotas = Rota::with(['escolas:id,nome'])
            ->whereIn('id', $rotaIds)
            ->get(['id']);

        $mapEscolasPorRota = [];
        $todasEscolaIds = [];
        foreach ($rotas as $rota) {
            $ids = $rota->escolas->pluck('id')->all();
            $mapEscolasPorRota[$rota->id] = $ids;
            $todasEscolaIds = array_merge($todasEscolaIds, $ids);
        }

        if (empty($todasEscolaIds)) {
            return [];
        }

        $nomesEscolas = Escola::whereIn('id', array_unique($todasEscolaIds))
            ->pluck('nome', 'id')
            ->all();

        $acum = [];
        foreach ($rotaIds as $i => $rotaId) {
            $valorRota = $valores[$i] ?? 0.0;
            $escolasDaRota = $mapEscolasPorRota[$rotaId] ?? [];
            $qtd = count($escolasDaRota);
            if ($qtd <= 0) {
                continue;
            }
            $quota = $valorRota / $qtd;
            foreach ($escolasDaRota as $eid) {
                $acum[$eid] = ($acum[$eid] ?? 0) + $quota;
            }
        }

        $pares = [];
        foreach ($acum as $eid => $tot) {
            $pares[] = [
                'name'  => (string) ($nomesEscolas[$eid] ?? "Escola #$eid"),
                'value' => (float) $tot,
            ];
        }

        return $pares;
    }

    /**
     * Estado atual: usa todas as rotas com valor_total atual e reparte por escola.
     */
    private function paresPorEscolaDoEstadoAtual(): array
    {
        $rotas = Rota::with(['escolas:id,nome'])
            ->get(['id', 'valor_total']);

        $acum = []; // escola_id => total
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
        return $pares;
    }

    /** Ordena desc por value e formata BRL nos labels */
    private function ordenarEFormatar(array $pares): array
    {
        usort($pares, fn($a, $b) => $b['value'] <=> $a['value']);
        $pares = array_slice($pares, 0, 10); // Top 10

        $labels = [];
        $data   = [];
        foreach ($pares as $p) {
            $labels[] = $p['name'] . ' — ' . $this->formatBRL($p['value']);
            $data[]   = (float) $p['value'];
        }
        return [$labels, $data];
    }

    private function formatBRL(float $v): string
    {
        return 'R$ ' . number_format($v, 2, ',', '.');
    }
}
