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
        [$rotulos, $valores] = $this->resolverCategoriasEValores();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 450,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Valor por Escola',
                    'data' => $valores,
                ],
            ],
            'xaxis' => [
                'categories' => $rotulos,
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
        // Rotas que têm pelo menos uma escola vinculada
        $rotasComEscolas = Rota::with(['escolas:id,nome'])
            ->whereHas('escolas')
            ->get(['id', 'valor_total']);

        // [id_escola => valor_rateado_total]
        $custoTotalPorEscola = [];

        // [id_escola => nome_escola]
        $nomesEscolas = [];

        foreach ($rotasComEscolas as $rota) {
            $valorTotalRota = (float) ($rota->valor_total ?? 0);

            $idsEscolasDaRota   = $rota->escolas->pluck('id')->all();
            $nomesEscolasDaRota = $rota->escolas->pluck('nome', 'id')->all();

            // Garante mapa de nomes
            $nomesEscolas += $nomesEscolasDaRota;

            $quantidadeEscolasNaRota = count($idsEscolasDaRota);

            if ($quantidadeEscolasNaRota <= 0) {
                continue;
            }

            // Rateia o valor total da rota entre as escolas atendidas
            $custoRateadoPorEscola = $valorTotalRota / $quantidadeEscolasNaRota;

            foreach ($idsEscolasDaRota as $idEscola) {
                $custoTotalPorEscola[$idEscola] = ($custoTotalPorEscola[$idEscola] ?? 0) + $custoRateadoPorEscola;
            }
        }

        $dadosCustoEscolas = [];

        foreach ($custoTotalPorEscola as $idEscola => $custoTotal) {
            $dadosCustoEscolas[] = [
                'nome'  => (string) ($nomesEscolas[$idEscola] ?? "Escola #{$idEscola}"),
                'valor' => (float) $custoTotal,
            ];
        }

        return $this->ordenarEFormatar($dadosCustoEscolas);
    }

    private function ordenarEFormatar(array $dadosCustoEscolas): array
    {
        // Ordena da escola mais cara para a mais barata
        usort(
            $dadosCustoEscolas,
            fn (array $escolaA, array $escolaB) =>
                $escolaB['valor'] <=> $escolaA['valor']
        );

        // Limita para top 10
        $dadosCustoEscolas = array_slice($dadosCustoEscolas, 0, 10);

        $rotulos = [];
        $valores = [];

        foreach ($dadosCustoEscolas as $dadosEscola) {
            $rotulos[] = $dadosEscola['nome'] . ' — ' . $this->formatarBRL($dadosEscola['valor']);
            $valores[] = (float) number_format($dadosEscola['valor'], 2, '.', '');
        }

        return [$rotulos, $valores];
    }

    private function formatarBRL(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}
