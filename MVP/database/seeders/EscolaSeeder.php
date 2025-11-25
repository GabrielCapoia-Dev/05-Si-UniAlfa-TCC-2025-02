<?php

namespace Database\Seeders;

use App\Models\Escola;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Faker\Factory as Faker;

class EscolaSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        // Lista fixa de escolas com coordenadas pré-definidas
        $escolas = [
            [
                'nome'      => 'Escola Municipal Dr. Ângelo Moreira da Fonseca',
                'latitude'  => -23.769901,
                'longitude' => -53.330741,
            ],
            [
                'nome'      => 'Escola Municipal Evangélica',
                'latitude'  => -23.742038,
                'longitude' => -53.310514,
            ],
            [
                'nome'      => 'Escola Municipal Jardim União',
                'latitude'  => -23.778888,
                'longitude' => -53.327737,
            ],
            [
                'nome'      => 'CMEI Cecília Meireles',
                'latitude'  => -23.745282,
                'longitude' => -53.313365,
            ],
            [
                'nome'      => 'CMEI Cora Coralina',
                'latitude'  => -23.771337,
                'longitude' => -53.302628,
            ],
            [
                'nome'      => 'CMEI Graciliano Ramos',
                'latitude'  => -23.780667,
                'longitude' => -53.337263,
            ],
            [
                'nome'      => 'Colégio Estadual Profª Hilda Trautwein Kamal',
                'latitude'  => -23.767802,
                'longitude' => -53.311931,
            ],
            [
                'nome'      => 'Escola Estadual Izabel',
                'latitude'  => -23.749319,
                'longitude' => -53.301457,
            ],
            [
                'nome'      => 'Escola Estadual Jardim Canadá',
                'latitude'  => -23.775549,
                'longitude' => -53.295275,
            ],
        ];

        $bairros = [
            'Centro','Zona I','Zona II','Zona VI','Zona VII','Jardim São Cristóvão','Parque Danielle',
            'Parque Jabuticabeira','Jardim Panorama','Conjunto Guarani','Parque Industrial',
            'Conjunto Ouro Branco','Cohapar I','Parque Dom Pedro I','Parque San Remo','Parque Vitória Régia',
            'Jardim Alphaville','Jardim Birigui','Parque das Laranjeiras','Distrito de Lovat','Serra dos Dourados',
            'Santa Eliza','Jardim América','Parque Bonfim','Porto Belo','Dom Bosco','Tropical',
        ];

        $payload = [];

        foreach ($escolas as $dadosEscola) {
            $nome      = $dadosEscola['nome'];
            $latitude  = $dadosEscola['latitude'];
            $longitude = $dadosEscola['longitude'];

            // Detecta tipo pela string
            $nomeLower = Str::lower($nome);
            $tipo = str_contains($nomeLower, 'colégio')
                 || str_contains($nomeLower, 'escola estadual')
                 || str_contains($nomeLower, 'ceebja')
                ? 'Estadual'
                : (str_contains($nomeLower, 'cmei') || str_contains($nomeLower, 'cei') ? 'Municipal' : 'Municipal');

            // 1) Tenta buscar endereço real via Nominatim
            $endereco = $this->buscarEnderecoViaNominatim($latitude, $longitude);

            if (! $endereco) {
                // 2) Se falhar completamente → gera tudo com Faker
                $logradouro = $faker->streetName;
                $numero     = $faker->numberBetween(1, 9999);
                $bairro     = Arr::random($bairros);
                $cep        = sprintf(
                    '875%02d-%03d',
                    $faker->numberBetween(0, 99),
                    $faker->numberBetween(0, 999)
                );

                $endereco = [
                    'logradouro' => "Rua {$logradouro}",
                    'numero'     => (string) $numero,
                    'bairro'     => $bairro,
                    'cidade'     => 'Umuarama',
                    'estado'     => 'PR',
                    'cep'        => str_replace('-', '', $cep),
                ];
            } else {
                // 3) Se veio algo do Nominatim, garante que NADA obrigatório fique null

                // bairro nunca pode ser null por causa da constraint
                if (empty($endereco['bairro'])) {
                    $endereco['bairro'] = Arr::random($bairros);
                }

                // número vazio → coloca um aleatório ou "S/N"
                if (! isset($endereco['numero']) || $endereco['numero'] === '') {
                    $endereco['numero'] = (string) $faker->numberBetween(1, 9999);
                }

                // cidade/estado/cep com fallback
                if (empty($endereco['cidade'])) {
                    $endereco['cidade'] = 'Umuarama';
                }

                if (empty($endereco['estado'])) {
                    $endereco['estado'] = 'PR';
                }

                if (empty($endereco['cep'])) {
                    $cep = sprintf(
                        '875%02d%03d',
                        $faker->numberBetween(0, 99),
                        $faker->numberBetween(0, 999)
                    );
                    $endereco['cep'] = $cep;
                }
            }

            $payload[] = [
                'nome'        => $nome,
                'tipo'        => $tipo,
                'logradouro'  => $endereco['logradouro'] ?? null,
                'numero'      => (string) ($endereco['numero'] ?? ''),
                'bairro'      => $endereco['bairro'], // aqui agora é SEMPRE não-nulo
                'cidade'      => $endereco['cidade'] ?? 'Umuarama',
                'estado'      => $endereco['estado'] ?? 'PR',
                'cep'         => $endereco['cep'] ?? null,
                'complemento' => null,
                'latitude'    => $latitude,
                'longitude'   => $longitude,
                'raio'        => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        Escola::upsert(
            $payload,
            ['nome'], // chave única
            [
                'tipo','logradouro','numero','bairro','cidade','estado','cep','complemento',
                'latitude','longitude','raio','updated_at',
            ]
        );
    }

    /**
     * Faz reverse geocoding no Nominatim e retorna um array normalizado:
     * [
     *   'logradouro' => 'Rua X',
     *   'numero'     => '123',
     *   'bairro'     => 'Bairro Y',
     *   'cidade'     => 'Umuarama',
     *   'estado'     => 'PR',
     *   'cep'        => '87500000'
     * ]
     * ou null se falhar.
     */
    private function buscarEnderecoViaNominatim(float $lat, float $lng): ?array
    {
        try {
            $response = Http::withHeaders([
                    'User-Agent' => 'SME-Umuarama/1.0 (contato@sua-sme.gov.br)',
                ])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format'         => 'jsonv2',
                    'lat'            => $lat,
                    'lon'            => $lng,
                    'addressdetails' => 1,
                ]);

            if (! $response->ok()) {
                return null;
            }

            $data    = $response->json();
            $address = $data['address'] ?? null;

            if (! is_array($address) || empty($address)) {
                return null;
            }

            $road = $address['road']
                ?? $address['pedestrian']
                ?? $address['residential']
                ?? $address['footway']
                ?? null;

            $houseNumber = $address['house_number'] ?? null;

            $bairro = $address['suburb']
                ?? $address['neighbourhood']
                ?? $address['city_district']
                ?? $address['quarter']
                ?? null;

            $cidade = $address['city']
                ?? $address['town']
                ?? $address['village']
                ?? $address['municipality']
                ?? $address['county']
                ?? null;

            $estado = $address['state'] ?? null;

            $cep = $address['postcode'] ?? null;
            if ($cep) {
                $cep = preg_replace('/\D+/', '', $cep);
            }

            // Se nem rua tiver, considera falha (aí o caller cai pro Faker)
            if (! $road) {
                return null;
            }

            return [
                'logradouro' => "Rua {$road}",
                'numero'     => $houseNumber ? (string) $houseNumber : '',
                'bairro'     => $bairro,         // pode vir null, mas tratamos depois
                'cidade'     => $cidade,
                'estado'     => $estado,
                'cep'        => $cep,
            ];
        } catch (\Throwable $e) {
            // Em caso de time-out, erro de rede, etc → fallback pro Faker
            return null;
        }
    }
}
