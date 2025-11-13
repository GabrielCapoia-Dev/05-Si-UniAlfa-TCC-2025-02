<?php

namespace App\Filament\Clusters\RelatoriosCluster\Resources\RelatorioEscolaAlunoResource\Widgets;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RelatorioEscolaAlunoChart extends ApexChartWidget
{
    protected static ?string $chartId = 'escolasCustoMedioAlunoChart';
    protected static ?string $heading = 'Custo médio por aluno (Top 10)';
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

        $this->updateOptions();
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
                ['name' => 'Custo médio por aluno', 'data' => $valores],
            ],
            'xaxis' => [
                'categories' => $rotulos,
                'labels' => ['style' => ['fontFamily' => 'inherit']],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => ['fontFamily' => 'inherit'],
                    'maxWidth' => 400,
                    'minWidth' => 300,
                ],
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
            'grid' => ['padding' => ['left' => 20]],
        ];
    }

    private function dadosDoGrafico(): array
    {
        // total de alunos por rota
        $alunosTotal = DB::table('alunos')
            ->select('id_rota', DB::raw('COUNT(*) AS total_alunos_rota'))
            ->groupBy('id_rota');

        // alunos por (rota, escola) via turma
        $alunosPorEscola = DB::table('alunos AS a')
            ->join('turmas AS t', 't.id', '=', 'a.id_turma')
            ->select('a.id_rota', 't.id_escola AS escola_id', DB::raw('COUNT(*) AS alunos_na_escola'))
            ->groupBy('a.id_rota', 't.id_escola');

        // agregado proporcional por escola
        $q = DB::table('escolas')
            ->leftJoin('escola_rota AS er', 'er.escola_id', '=', 'escolas.id')
            ->leftJoin('rotas', 'rotas.id', '=', 'er.rota_id')
            ->leftJoinSub($alunosTotal, 'at', 'at.id_rota', '=', 'er.rota_id')
            ->leftJoinSub($alunosPorEscola, 'ae', function ($join) {
                $join->on('ae.id_rota', '=', 'er.rota_id')
                     ->on('ae.escola_id', '=', 'er.escola_id');
            })
            ->when($this->idsFiltradas !== [], fn ($qq) => $qq->whereIn('escolas.id', $this->idsFiltradas))
            ->when($this->turno, fn ($qq) => $qq->where('rotas.turno', $this->turno))
            ->groupBy('escolas.id', 'escolas.nome')
            ->select([
                'escolas.id',
                'escolas.nome',
                // total de alunos atendidos pela escola (somatório das parcelas por rota)
                DB::raw('COALESCE(SUM(COALESCE(ae.alunos_na_escola, 0)), 0) AS alunos_atendidos'),
                // custo total da escola proporcional por alunos
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
                // custo médio por aluno
                DB::raw("
                    CASE
                        WHEN COALESCE(SUM(COALESCE(ae.alunos_na_escola, 0)), 0) > 0
                        THEN
                            COALESCE(
                                SUM(
                                    rotas.valor_total * (
                                        COALESCE(ae.alunos_na_escola, 0)
                                        / NULLIF(COALESCE(at.total_alunos_rota, 0), 0)
                                    )
                                ), 0
                            ) / COALESCE(SUM(COALESCE(ae.alunos_na_escola, 0)), 0)
                        ELSE 0
                    END AS custo_medio_aluno
                "),
            ])
            ->orderByDesc('custo_medio_aluno') // top 10 mais caros por aluno
            ->limit(10)
            ->get();

        $rotulos = [];
        $valores = [];

        foreach ($q as $row) {
            $rotulos[] = (string) $row->nome;
            // número puro (ponto) para o Apex Charts
            $valores[] = (float) number_format((float) $row->custo_medio_aluno, 2, '.', '');
        }

        return [$rotulos, $valores];
    }
}
