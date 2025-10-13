<?php

namespace Database\Seeders;

use App\Models\Aluno;
use App\Models\Turma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class AlunoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        // 15 primeiros nomes (mistos)
        $primeiros = [
            'Maria',
            'Ana',
            'João',
            'Gabriel',
            'Pedro',
            'Lucas',
            'Luiza',
            'Julia',
            'Miguel',
            'Guilherme',
            'Mariana',
            'Matheus',
            'Beatriz',
            'Rafael',
            'Felipe',
        ];

        // 15 nomes do meio (bem comuns no BR)
        $meios = [
            'Aparecida',
            'Clara',
            'Eduarda',
            'Fernanda',
            'Sofia',
            'Carolina',
            'Vitória',
            'Cristina',
            'Letícia',
            'Bianca',
            'Augusto',
            'Henrique',
            'Eduardo',
            'André',
            'César',
        ];

        // 15 sobrenomes
        $sobrenomes = [
            'Silva',
            'Santos',
            'Oliveira',
            'Souza',
            'Rodrigues',
            'Ferreira',
            'Alves',
            'Pereira',
            'Lima',
            'Gomes',
            'Ribeiro',
            'Carvalho',
            'Almeida',
            'Costa',
            'Martins',
        ];

        // Bairros de Umuarama (amostra)
        $bairros = [
            'Centro',
            'Zona I',
            'Zona II',
            'Zona VI',
            'Zona VII',
            'Jardim São Cristóvão',
            'Parque Danielle',
            'Parque Jabuticabeira',
            'Jardim Panorama',
            'Conjunto Guarani',
            'Parque Industrial',
            'Conjunto Ouro Branco',
            'Cohapar I',
            'Parque Dom Pedro I',
            'Parque San Remo',
            'Parque Vitória Régia',
            'Jardim Alphaville',
            'Jardim Birigui',
            'Parque das Laranjeiras',
            'Distrito de Lovat',
            'Serra dos Dourados',
            'Santa Eliza',
            'Jardim América',
            'Parque Bonfim',
            'Porto Belo',
            'Dom Bosco',
            'Tropical'
        ];

        // Para garantir CGM único global
        $cgmsUsados = Aluno::pluck('cgm')->all();
        $cgmsIndex  = array_flip($cgmsUsados);

        // Carrega todas as turmas com suas séries (p/ idade)
        $turmas = Turma::with('serie', 'escola')->get();

        foreach ($turmas as $turma) {
            $qtd = rand(1, 4);

            for ($i = 0; $i < $qtd; $i++) {

                [$nome, $sexo] = $this->gerarNomeCompleto($primeiros, $meios, $sobrenomes);

                $idadeAlvo = $this->idadePorSerie($turma->serie?->nome ?? '');
                $dataNasc  = $faker->dateTimeBetween("-{$idadeAlvo['max']} years", "-{$idadeAlvo['min']} years")
                    ->format('Y-m-d');

                $cep = sprintf('875%02d%03d', rand(0, 99), rand(0, 999)); // 8 dígitos, sem hífen
                $bairro = Arr::random($bairros);

                $cgm = $this->gerarCGMUnico($cgmsIndex); // sempre único

                // Telefones (DDD 44)
                $telResp = $this->telefoneMovel();
                $telAluno = rand(0, 1) ? $this->telefoneMovel() : null;
                $telAlt   = rand(0, 1) ? $this->telefoneFixo()  : null;

                Aluno::create([
                    'id_turma'            => $turma->id,
                    'id_rota'             => null, // se quiser, dá pra associar futuramente via escola->rotas
                    'nome'                => $nome,
                    'data_nascimento'     => $dataNasc,
                    'cgm'                 => $cgm,
                    'sexo'                => $sexo,
                    'foto'                => null, // opcional
                    'nome_responsavel'    => $this->nomeResponsavel($primeiros, $sobrenomes),
                    'telefone_responsavel' => $telResp,
                    'telefone_aluno'      => $telAluno,
                    'telefone_alternativo' => $telAlt,
                    'latitude'            => null,
                    'longitude'           => null,
                    'raio'                => null,
                    'logradouro'          => 'Rua ' . $faker->streetName(),
                    'numero'              => (string) rand(1, 9999),
                    'bairro'              => $bairro,
                    'cidade'              => 'Umuarama',
                    'estado'              => 'PR',
                    'cep'                 => $cep, // sem hífen (sua migration espera até 8 chars)
                    'complemento'         => rand(0, 1) ? 'Próx. à escola' : null,
                    'tem_carteirinha'     => (bool) rand(0, 1), // ou deixe default false se preferir
                ]);
            }
        }
    }

    /**
     * Monta nome completo com 1º nome + (opcional) nome do meio + 1 ou 2 sobrenomes.
     * Retorna [nomeCompleto, sexo]
     */
    private function gerarNomeCompleto(array $primeiros, array $meios, array $sobrenomes): array
    {
        $sexo = rand(0, 1) ? 'Masculino' : 'Feminino';

        if ($sexo === 'Masculino') {
            $candidatos = ['João', 'Gabriel', 'Pedro', 'Lucas', 'Miguel', 'Guilherme', 'Matheus', 'Rafael', 'Felipe'];
            $primeiro = Arr::random(array_merge($candidatos, $primeiros));
        } else {
            $candidatas = ['Maria', 'Ana', 'Luiza', 'Julia', 'Mariana', 'Beatriz'];
            $primeiro = Arr::random(array_merge($candidatas, $primeiros));
        }

        $temMeio = (bool) rand(0, 1);
        $meio = $temMeio ? Arr::random($meios) : null;

        $sob1 = Arr::random($sobrenomes);
        $sob2 = rand(0, 1) ? Arr::random($sobrenomes) : null;

        $partes = [$primeiro];
        if ($meio)  $partes[] = $meio;
        $partes[]   = $sob1;
        if ($sob2 && $sob2 !== $sob1) $partes[] = $sob2;

        $nomeCompleto = implode(' ', $partes);

        return [$nomeCompleto, $sexo];
    }

    /**
     * Idade alvo por série (min/max, em anos) — aproximações realistas.
     */
    private function idadePorSerie(string $serie): array
    {
        $s = mb_strtolower($serie, 'UTF-8');

        if (str_contains($s, 'infantil 4')) return ['min' => 4,  'max' => 5];
        if (str_contains($s, 'infantil 5')) return ['min' => 5,  'max' => 6];

        if (preg_match('/^1º ano$/iu', $serie)) return ['min' => 6,  'max' => 7];
        if (preg_match('/^2º ano$/iu', $serie)) return ['min' => 7,  'max' => 8];
        if (preg_match('/^3º ano$/iu', $serie)) return ['min' => 8,  'max' => 9];
        if (preg_match('/^4º ano$/iu', $serie)) return ['min' => 9,  'max' => 10];
        if (preg_match('/^5º ano$/iu', $serie)) return ['min' => 10, 'max' => 11];

        if (preg_match('/^6º ano$/iu', $serie)) return ['min' => 11, 'max' => 12];
        if (preg_match('/^7º ano$/iu', $serie)) return ['min' => 12, 'max' => 13];
        if (preg_match('/^8º ano$/iu', $serie)) return ['min' => 13, 'max' => 14];
        if (preg_match('/^9º ano$/iu', $serie)) return ['min' => 14, 'max' => 15];

        if (str_contains($s, '1º ano ensino médio')) return ['min' => 15, 'max' => 16];
        if (str_contains($s, '2º ano ensino médio')) return ['min' => 16, 'max' => 17];
        if (str_contains($s, '3º ano ensino médio')) return ['min' => 17, 'max' => 18];

        return ['min' => 10, 'max' => 17];
    }

    /**
     * Gera um CGM único global (8 dígitos).
     */
    private function gerarCGMUnico(array &$cgmsIndex): string
    {
        do {
            $cgm = (string) rand(10000000, 99999999);
        } while (isset($cgmsIndex[$cgm]));

        $cgmsIndex[$cgm] = true;
        return $cgm;
    }

    /**
     * Telefone celular (DDD 44, 11 dígitos).
     */
    private function telefoneMovel(): string
    {
        return '449' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Telefone fixo (DDD 44, 10 ou 11 dígitos – aqui 10).
     */
    private function telefoneFixo(): string
    {
        return '44' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Nome do responsável (simples).
     */
    private function nomeResponsavel(array $primeiros, array $sobrenomes): string
    {
        $p = Arr::random($primeiros);
        $s1 = Arr::random($sobrenomes);
        $s2 = rand(0, 1) ? Arr::random($sobrenomes) : null;

        return $p . ' ' . $s1 . ($s2 && $s2 !== $s1 ? ' ' . $s2 : '');
    }
}
