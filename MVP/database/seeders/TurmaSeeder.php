<?php

namespace Database\Seeders;

use App\Models\Escola;
use App\Models\Serie;
use App\Models\Turma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TurmaSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Garante que as séries existam
        $seriesList = [
            'Infantil 4',
            'Infantil 5',
            '1º Ano',
            '2º Ano',
            '3º Ano',
            '4º Ano',
            '5º Ano',
            '6º Ano',
            '7º Ano',
            '8º Ano',
            '9º Ano',
            '1º Ano Ensino Médio',
            '2º Ano Ensino Médio',
            '3º Ano Ensino Médio',
        ];

        foreach ($seriesList as $nome) {
            Serie::firstOrCreate(['nome' => $nome]);
        }

        // Mapeia nome da série -> id
        $series = Serie::pluck('id', 'nome')->toArray();

        // 2) Define conjuntos por tipo de escola
        $seriesMunicipais = [
            'Infantil 4',
            'Infantil 5',
            '1º Ano', '2º Ano', '3º Ano', '4º Ano', '5º Ano',
        ];

        $seriesEstaduais = [
            '6º Ano', '7º Ano', '8º Ano', '9º Ano',
            '1º Ano Ensino Médio', '2º Ano Ensino Médio', '3º Ano Ensino Médio',
        ];

        // 3) Para cada escola, cria turmas coerentes com o tipo
        $escolas = Escola::all();

        foreach ($escolas as $escola) {
            $tipo = mb_strtolower($escola->tipo ?? 'Municipal', 'UTF-8');

            $listaSeries = str_contains($tipo, 'estadual')
                ? $seriesEstaduais
                : $seriesMunicipais;

            foreach ($listaSeries as $serieNome) {
                $idSerie = $series[$serieNome] ?? null;
                if (! $idSerie) {
                    continue;
                }

                // Quantidade de turmas por série (A, B, C aleatório)
                $qtdTurmas = rand(1, 2);
                $secoes = array_slice(['A', 'B', 'C'], 0, $qtdTurmas);

                foreach ($secoes as $secao) {
                    $turno = $this->decidirTurno($serieNome);

                    // Usa firstOrCreate para não duplicar
                    Turma::firstOrCreate(
                        [
                            'id_serie'  => $idSerie,
                            'id_escola' => $escola->id,
                            'turma'     => $secao,   // A / B / C
                            'turno'     => $turno,   // Manhã / Tarde / Noite / Integral
                        ],
                        [] // sem adicionais; colunas já cobrem a definição da turma
                    );
                }
            }
        }
    }

    /**
     * Retorna um turno adequado de acordo com a série.
     */
    private function decidirTurno(string $serieNome): string
    {
        $s = mb_strtolower($serieNome, 'UTF-8');

        // Educação Infantil: Manhã/Tarde/Integral (com leve chance de Integral)
        if (str_contains($s, 'infantil')) {
            return Arr::random(['Manhã', 'Tarde', 'Integral', 'Manhã', 'Tarde']);
        }

        // 1º ao 5º ano: Manhã/Tarde
        if (preg_match('/^\dº ano$/u', $this->normalizarSerie($serieNome)) && $this->anoFundamentalInicial($serieNome)) {
            return Arr::random(['Manhã', 'Tarde']);
        }

        // 6º ao 9º ano: Manhã/Tarde
        if (preg_match('/^\dº ano$/u', $this->normalizarSerie($serieNome)) && $this->anoFundamentalFinal($serieNome)) {
            return Arr::random(['Manhã', 'Tarde']);
        }

        // Ensino Médio: Manhã/Noite
        if (str_contains($s, 'ensino médio')) {
            return Arr::random(['Manhã', 'Noite']);
        }

        // fallback
        return 'Manhã';
    }

    /**
     * Normaliza nomes como '1º Ano', '2º Ano' para padrão simples (mesmo texto).
     */
    private function normalizarSerie(string $serieNome): string
    {
        return trim($serieNome);
    }

    /**
     * Verifica se a série é 1º ao 5º ano.
     */
    private function anoFundamentalInicial(string $serieNome): bool
    {
        return in_array($serieNome, ['1º Ano','2º Ano','3º Ano','4º Ano','5º Ano'], true);
    }

    /**
     * Verifica se a série é 6º ao 9º ano.
     */
    private function anoFundamentalFinal(string $serieNome): bool
    {
        return in_array($serieNome, ['6º Ano','7º Ano','8º Ano','9º Ano'], true);
    }
}
