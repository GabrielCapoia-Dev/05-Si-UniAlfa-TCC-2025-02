<?php

namespace Database\Seeders;

use App\Models\Escola;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class EscolaSeeder extends Seeder
{
    // bounding box de Umuarama-PR (aprox.)
    private const LAT_MIN = -23.84628485;
    private const LAT_MAX = -23.75628485;
    private const LNG_MIN = -53.40628485;
    private const LNG_MAX = -53.20628485;

    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        $municipais = [
            'Escola Municipal Dr. Ângelo Moreira da Fonseca',
            'Escola Municipal Evangélica',
            'Escola Municipal Jardim União',

        ];

        $cmeis = [
            'CMEI Cecília Meireles',
            'CMEI Cora Coralina',
            'CMEI Graciliano Ramos',
        ];

        $estaduais = [
            'Colégio Estadual Profª Hilda Trautwein Kamal',
            'Escola Estadual Izabel',
            'Escola Estadual Jardim Canadá',
        ];

        $todos = array_values(array_unique(array_merge($municipais, $cmeis, $estaduais)));
        shuffle($todos);
        $selecionados = array_slice($todos, 0, 50);

        $bairros = [
            'Centro','Zona I','Zona II','Zona VI','Zona VII','Jardim São Cristóvão','Parque Danielle',
            'Parque Jabuticabeira','Jardim Panorama','Conjunto Guarani','Parque Industrial',
            'Conjunto Ouro Branco','Cohapar I','Parque Dom Pedro I','Parque San Remo','Parque Vitória Régia',
            'Jardim Alphaville','Jardim Birigui','Parque das Laranjeiras','Distrito de Lovat','Serra dos Dourados',
            'Santa Eliza','Jardim América','Parque Bonfim','Porto Belo','Dom Bosco','Tropical'
        ];

        $payload = [];

        foreach ($selecionados as $nome) {
            $tipo = str_contains(Str::lower($nome), 'colégio')
                 || str_contains(Str::lower($nome), 'escola estadual')
                 || str_contains(Str::lower($nome), 'ceebja')
                ? 'Estadual'
                : (str_contains(Str::lower($nome), 'cmei') || str_contains(Str::lower($nome), 'cei') ? 'Municipal' : 'Municipal');

            $logradouro = $faker->streetName;
            $numero     = $faker->numberBetween(1, 9999);
            $bairro     = Arr::random($bairros);
            $cep        = sprintf('875%02d-%03d', $faker->numberBetween(0, 99), $faker->numberBetween(0, 999));

            $lat = $this->randFloat(self::LAT_MIN, self::LAT_MAX);
            $lng = $this->randFloat(self::LNG_MIN, self::LNG_MAX);

            $payload[] = [
                'nome'        => $nome,
                'tipo'        => $tipo,
                'logradouro'  => "Rua {$logradouro}",
                'numero'      => (string) $numero,
                'bairro'      => $bairro,
                'cidade'      => 'Umuarama',
                'estado'      => 'PR',
                'cep'         => str_replace('-', '', $cep),
                'complemento' => null,
                'latitude'    => round($lat, 6),
                'longitude'   => round($lng, 6),
                'raio'        => null, 
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        Escola::upsert(
            $payload,
            ['nome'],
            [
                'tipo','logradouro','numero','bairro','cidade','estado','cep','complemento',
                'latitude','longitude','raio','updated_at'
            ]
        );
    }

    private function randFloat(float $min, float $max): float
    {
        return $min + (lcg_value() * ($max - $min));
    }
}
