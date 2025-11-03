<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use App\Models\Aluno;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ComparativoMensalWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    // Atualização automática a cada 30 segundos
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalCustoRotas = (float) Rota::query()->sum('valor_total');
        $quantidadeRotas = Rota::query()->count();
        $custoMedioPorRota = $quantidadeRotas > 0 ? $totalCustoRotas / $quantidadeRotas : 0;

        $distanciaTotal = (float) Rota::query()->sum('distancia_total');
        $distanciaMedia = $quantidadeRotas > 0 ? $distanciaTotal / $quantidadeRotas : 0;

        $totalAlunos = Aluno::query()->count();
        $alunosComRota = Aluno::query()->whereNotNull('id_rota')->count();
        $alunosSemRota = $totalAlunos - $alunosComRota;
        $percentualAtendidos = $totalAlunos > 0 ? round(($alunosComRota / $totalAlunos) * 100, 1) : 0;
        $custoMedioPorAluno = $alunosComRota > 0 ? $totalCustoRotas / $alunosComRota : 0;

        return [
            Stat::make('Custo Total das Rotas', 'R$ ' . number_format($totalCustoRotas, 2, ',', '.'))
                ->description('Custo médio por rota: R$ ' . number_format($custoMedioPorRota, 2, ',', '.'))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Total de Rotas', number_format($quantidadeRotas, 0, ',', '.'))
                ->description('Distância total: ' . number_format($distanciaTotal, 2, ',', '.') . ' km')
                ->descriptionIcon('heroicon-o-map')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Alunos Atendidos', number_format($alunosComRota, 0, ',', '.'))
                ->description(
                    $alunosSemRota > 0 
                        ? $alunosSemRota . ' alunos sem rota | Custo médio: R$ ' . number_format($custoMedioPorAluno, 2, ',', '.')
                        : 'Custo médio por aluno: R$ ' . number_format($custoMedioPorAluno, 2, ',', '.')
                )
                ->descriptionIcon($alunosSemRota > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-user-group')
                ->color($alunosSemRota > 0 ? 'warning' : 'success')
                ->chart([15, 4, 10, 22, 20, 27, 25]),
        ];
    }
}