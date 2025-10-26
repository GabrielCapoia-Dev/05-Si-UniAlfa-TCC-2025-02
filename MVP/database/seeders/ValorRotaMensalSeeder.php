<?php

namespace Database\Seeders;

use App\Models\Rota;
use App\Models\ValorRotaMensal;
use Illuminate\Database\Seeder;

class ValorRotaMensalSeeder extends Seeder
{
    public function run(): void
    {
        $topRotas = Rota::query()
            ->select('id')
            ->orderByDesc('valor_total')
            ->limit(10)
            ->pluck('id')
            ->all();

        if (count($topRotas) === 0) {
            $this->command->warn('⚠️ Nenhuma rota encontrada.');
            return;
        }

        $mapTurnoPorRota = Rota::whereIn('id', $topRotas)->pluck('turno', 'id')->all();
        $turnosFixos = ['Manhã', 'Tarde', 'Noite'];

        $anoAtual = (int) now()->year;
        $anos = range($anoAtual - 2, $anoAtual);

        foreach ($anos as $ano) {
            for ($mes = 1; $mes <= 12; $mes++) {
                $totalMes = random_int(7123, 11321);

                $n = count($topRotas);

                $pesos = [];
                $somaPesos = 0;
                for ($i = 0; $i < $n; $i++) {
                    $p = random_int(10, 100);
                    $pesos[] = $p;
                    $somaPesos += $p;
                }

                $centavosTotal = $totalMes * 100;
                $alocados = [];
                $acumulado = 0;

                for ($i = 0; $i < $n; $i++) {
                    if ($i === $n - 1) {
                        $cent = max(0, $centavosTotal - $acumulado);
                    } else {
                        $cent = (int) floor($centavosTotal * $pesos[$i] / $somaPesos);
                    }
                    $acumulado += $cent;
                    $alocados[] = $cent;
                }

                $matrizRotas = [];
                for ($i = 0; $i < $n; $i++) {
                    $matrizRotas[] = [
                        'rota_id'     => (int) $topRotas[$i],
                        'valor_total' => round($alocados[$i] / 100, 2),
                    ];
                }

                $centavosPorTurno = array_fill_keys($turnosFixos, 0);

                for ($i = 0; $i < $n; $i++) {
                    $rotaId = (int) $topRotas[$i];
                    $turno  = $mapTurnoPorRota[$rotaId] ?? null;
                    if ($turno && isset($centavosPorTurno[$turno])) {
                        $centavosPorTurno[$turno] += $alocados[$i];
                    }
                }

                $matrizTurnos = [];
                foreach ($turnosFixos as $t) {
                    $matrizTurnos[] = [
                        'turno'       => $t,
                        'valor_total' => round(($centavosPorTurno[$t] ?? 0) / 100, 2),
                    ];
                }

                ValorRotaMensal::updateOrCreate(
                    ['mes' => $mes, 'ano' => $ano],
                    [
                        'valor_total_mes'        => $totalMes,
                        'valor_total_por_rota'   => $matrizRotas,
                        'valor_total_por_turno'  => $matrizTurnos,
                    ]
                );
            }
        }

        $this->command->info('✅ ValorRotaMensal: preenchido com matrizes por rota e por turno.');
    }
}
