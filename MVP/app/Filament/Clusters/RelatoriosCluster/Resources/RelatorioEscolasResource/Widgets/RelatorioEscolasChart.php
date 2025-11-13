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
        // Subquery: total de alunos por rota
        $alunosTotal = DB::table('alunos')
            ->select('id_rota', DB::raw('COUNT(*) AS total_alunos_rota'))
            ->groupBy('id_rota');

        // Subquery: alunos por (rota, escola) via turma
        $alunosPorEscola = DB::table('alunos AS a')
            ->join('turmas AS t', 't.id', '=', 'a.id_turma')
            ->select('a.id_rota', 't.id_escola AS escola_id', DB::raw('COUNT(*) AS alunos_na_escola'))
            ->groupBy('a.id_rota', 't.id_escola');

        // Agregado proporcional por escola (com filtros opcionais de turno e de ids)
        $q = DB::table('escolas')
            ->leftJoin('escola_rota AS er', 'er.escola_id', '=', 'escolas.id')
            ->leftJoin('rotas', 'rotas.id', '=', 'er.rota_id')
            ->leftJoinSub($alunosTotal, 'at', 'at.id_rota', '=', 'er.rota_id')
            ->leftJoinSub($alunosPorEscola, 'ae', function ($join) {
                $join->on('ae.id_rota', '=', 'er.rota_id')
                    ->on('ae.escola_id', '=', 'er.escola_id');
            })
            ->when($this->idsFiltradas !== [], fn($qq) => $qq->whereIn('escolas.id', $this->idsFiltradas))
            ->when($this->turno, fn($qq) => $qq->where('rotas.turno', $this->turno))
            ->groupBy('escolas.id', 'escolas.nome')
            ->select([
                'escolas.id',
                'escolas.nome',
                DB::raw("
                COALESCE(
                    SUM(
                        rotas.valor_total * (
                            COALESCE(ae.alunos_na_escola, 0)
                            / NULLIF(COALESCE(at.total_alunos_rota, 0), 0)
                        )
                    ), 0
                ) AS custo_total_escola
            "),
            ])
            ->orderByDesc('custo_total_escola')
            ->limit(10)
            ->get();

        $rotulos = [];
        $valores = [];

        foreach ($q as $row) {
            $rotulos[] = (string) $row->nome;
            $valores[] = (float) number_format((float) $row->custo_total_escola, 2, '.', '');
        }

        return [$rotulos, $valores];
    }
}
