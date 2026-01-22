<?php

namespace App\Console\Commands;

use App\Models\Escola;
use App\Models\Serie;
use App\Models\Turma;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarTurmasCommand extends Command
{
    protected $signature = 'importar:turmas {arquivo : Caminho do arquivo CSV}';
    protected $description = 'Importa turmas a partir de um arquivo CSV';

    public function handle(): int
    {
        $arquivo = $this->argument('arquivo');

        if (!file_exists($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");
            return self::FAILURE;
        }

        $linhas = array_map(
            fn($linha) => str_getcsv($linha),
            file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        );

        // Remove cabeçalho
        $cabecalho = array_shift($linhas);

        if ($cabecalho !== ['Escola', 'Seriação', 'Turma', 'Turno']) {
            $this->error('Formato do CSV inválido. Esperado: Escola,Seriação,Turma,Turno');
            return self::FAILURE;
        }

        $this->info('Processando séries...');
        $seriesNovas = $this->processarSeries($linhas);
        $this->info("Séries criadas: {$seriesNovas}");

        $this->info('Processando turmas...');
        $resultado = $this->processarTurmas($linhas);

        $this->info("Turmas criadas: {$resultado['criadas']}");
        $this->info("Turmas ignoradas (já existiam): {$resultado['ignoradas']}");

        if (count($resultado['erros']) > 0) {
            $this->warn('Erros encontrados:');
            foreach ($resultado['erros'] as $erro) {
                $this->warn("  - {$erro}");
            }
        }

        return self::SUCCESS;
    }

    private function processarSeries(array $linhas): int
    {
        $seriacoes = collect($linhas)
            ->pluck(1)
            ->unique()
            ->values();

        $existentes = Serie::whereIn('nome', $seriacoes)->pluck('nome');
        $novas = $seriacoes->diff($existentes);

        foreach ($novas as $nome) {
            Serie::create(['nome' => $nome]);
        }

        return $novas->count();
    }

    private function processarTurmas(array $linhas): array
    {
        $criadas = 0;
        $ignoradas = 0;
        $erros = [];

        // Cache de escolas e séries
        $escolas = Escola::pluck('id', 'nome');
        $series = Serie::pluck('id', 'nome');

        foreach ($linhas as $index => $linha) {
            [$nomeEscola, $nomeSerie, $turma, $turno] = $linha;
            $linhaNum = $index + 2;

            if (!isset($escolas[$nomeEscola])) {
                $erros[] = "Linha {$linhaNum}: Escola não encontrada - '{$nomeEscola}'";
                continue;
            }

            if (!isset($series[$nomeSerie])) {
                $erros[] = "Linha {$linhaNum}: Série não encontrada - '{$nomeSerie}'";
                continue;
            }

            $existe = Turma::where([
                'id_escola' => $escolas[$nomeEscola],
                'id_serie' => $series[$nomeSerie],
                'turma' => $turma,
                'turno' => $turno,
            ])->exists();

            if ($existe) {
                $ignoradas++;
                continue;
            }

            Turma::create([
                'id_escola' => $escolas[$nomeEscola],
                'id_serie' => $series[$nomeSerie],
                'turma' => $turma,
                'turno' => $turno,
            ]);

            $criadas++;
        }

        return compact('criadas', 'ignoradas', 'erros');
    }
}