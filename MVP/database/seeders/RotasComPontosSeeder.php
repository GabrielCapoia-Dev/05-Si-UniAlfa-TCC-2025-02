<?php

namespace Database\Seeders;

use App\Models\Escola;
use App\Models\Rota;
use App\Models\PontosDeParada;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class RotasComPontosSeeder extends Seeder
{
    // Box de Umuarama (lat/lng aproximados)
    private const LAT_MIN = -23.84628485;
    private const LAT_MAX = -23.75628485;
    private const LNG_MIN = -53.40628485;
    private const LNG_MAX = -53.20628485;

    // Quantidade de rotas
    private const QTD_ROTAS = 35;

    // Turnos
    private const TURNOS = ['Manhã', 'Tarde', 'Noite', 'Integral'];

    // Faixa para sortear valor_por_km ao redor da média 9,75
    private const VALOR_KM_MIN = 4.50;
    private const VALOR_KM_MAX = 15.00; // média (min+max)/2 = 9.75

    public function run(): void
    {
        $escolas = Escola::query()->get(['id', 'nome', 'latitude', 'longitude']);

        if ($escolas->count() < 4) {
            $this->command->warn('⚠️ É necessário ter pelo menos 4 escolas cadastradas para gerar as rotas.');
            return;
        }

        for ($i = 1; $i <= self::QTD_ROTAS; $i++) {
            $turno = Arr::random(self::TURNOS);
            $nome  = sprintf('Rota %s %02d', $turno, $i);

            // Cria rota básica
            $rota = Rota::create([
                'nome'  => $nome,
                'turno' => $turno,
            ]);

            // Seleciona 2–4 escolas aleatórias e vincula
            $qtdEscolas    = rand(2, 4);
            $escolasDaRota = $escolas->random($qtdEscolas)->values();
            $rota->escolas()->attach($escolasDaRota->pluck('id')->all());

            // Monta lista de pontos: um por escola + 3–5 pontos livres
            $pontos = [];

            foreach ($escolasDaRota as $esc) {
                $lat = $this->coordOrFallback($esc->latitude, self::LAT_MIN, self::LAT_MAX);
                $lng = $this->coordOrFallback($esc->longitude, self::LNG_MIN, self::LNG_MAX);

                $pontos[] = [
                    'id_rota'   => $rota->id,
                    'id_escola' => $esc->id,
                    'latitude'  => round($lat, 8),
                    'longitude' => round($lng, 8),
                    'tipo'      => 'escola',
                ];
            }

            $qtdPontosLivres = rand(3, 5);
            for ($k = 0; $k < $qtdPontosLivres; $k++) {
                $pontos[] = [
                    'id_rota'   => $rota->id,
                    'id_escola' => null,
                    'latitude'  => round($this->randFloat(self::LAT_MIN, self::LAT_MAX), 8),
                    'longitude' => round($this->randFloat(self::LNG_MIN, self::LNG_MAX), 8),
                    'tipo'      => 'ponto',
                ];
            }

            // Ordena os pontos por "vizinho mais próximo"
            $sequenciados = $this->orderByNearest($pontos);

            // Calcula distância total (km)
            $distKm = $this->distanciaTotalKm($sequenciados);
            $distKm = round($distKm, 2);

            // Estima tempo (min) com velocidade aleatória 28–40 km/h
            $velKmH = $this->randFloat(28, 40);
            $tempoMin = max(5, (int) round(($distKm / max(0.1, $velKmH)) * 60)); // mínimo 5 min

            // Sorteia valor_por_km ~ média 9,75
            $valorPorKm = round($this->randFloat(self::VALOR_KM_MIN, self::VALOR_KM_MAX), 2);
            $valorTotal = round($distKm * $valorPorKm, 2);

            // (Opcional) geometry simples baseada na sequência (GeoJSON LineString)
            $geometry = [
                'type'        => 'LineString',
                'coordinates' => array_map(
                    fn($p) => [(float) $p['longitude'], (float) $p['latitude']],
                    $sequenciados
                ),
            ];

            // Prepara payload final com ordem e timestamps
            $ordem = 1;
            $payload = [];
            foreach ($sequenciados as $p) {
                $payload[] = [
                    'id_rota'    => $p['id_rota'],
                    'id_escola'  => $p['id_escola'],
                    'latitude'   => $p['latitude'],
                    'longitude'  => $p['longitude'],
                    'ordem'      => $ordem++,
                    'tipo'       => $p['tipo'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Persiste pontos
            PontosDeParada::insert($payload);

            // Atualiza a rota com os campos fictícios
            $rota->update([
                'distancia_total' => $distKm,
                'tempo_estimado'  => $tempoMin,
                'geometry'        => $geometry, // pode usar null se preferir
                'waypoints'       => null,
                'legs'            => null,
                'valor_por_km'    => $valorPorKm,
                'valor_total'     => $valorTotal,
            ]);
        }
    }

    private function randFloat(float $min, float $max): float
    {
        return $min + (lcg_value() * ($max - $min));
    }

    private function coordOrFallback(?float $valor, float $min, float $max): float
    {
        return is_null($valor) ? $this->randFloat($min, $max) : $valor;
    }

    /**
     * Distância total percorrendo os pontos na ordem (km).
     */
    private function distanciaTotalKm(array $pontos): float
    {
        if (count($pontos) < 2) return 0.0;

        $total = 0.0;
        for ($i = 1; $i < count($pontos); $i++) {
            $a = $pontos[$i - 1];
            $b = $pontos[$i];
            $total += $this->haversine(
                (float) $a['latitude'],
                (float) $a['longitude'],
                (float) $b['latitude'],
                (float) $b['longitude']
            );
        }
        return $total;
    }

    /**
     * Heurística do vizinho mais próximo.
     * - Começa por uma escola (se houver), senão por um ponto aleatório
     */
    private function orderByNearest(array $pontos): array
    {
        if (count($pontos) <= 1) return $pontos;

        $indicesEscolas = array_keys(array_filter($pontos, fn($p) => $p['tipo'] === 'escola'));
        $startIndex = $indicesEscolas
            ? $indicesEscolas[array_rand($indicesEscolas)]
            : array_rand($pontos);

        $visitados = [];
        $restantes = array_keys($pontos);
        $atualIdx  = $startIndex;

        $visitados[] = $pontos[$atualIdx];
        $restantes   = array_values(array_diff($restantes, [$atualIdx]));

        while (!empty($restantes)) {
            $atual = $visitados[count($visitados) - 1];

            $proxIdx = null;
            $proxDist = PHP_FLOAT_MAX;

            foreach ($restantes as $idx) {
                $cand = $pontos[$idx];
                $d = $this->haversine(
                    (float) $atual['latitude'],
                    (float) $atual['longitude'],
                    (float) $cand['latitude'],
                    (float) $cand['longitude']
                );
                if ($d < $proxDist) {
                    $proxDist = $d;
                    $proxIdx  = $idx;
                }
            }

            $visitados[] = $pontos[$proxIdx];
            $restantes   = array_values(array_diff($restantes, [$proxIdx]));
        }

        return $visitados;
    }

    /**
     * Distância Haversine (km).
     */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371; // raio da Terra em km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
