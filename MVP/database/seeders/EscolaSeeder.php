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
            'Escola Municipal Malba Tahan',
            'Escola Municipal Manuel Bandeira',
            'Escola Municipal Ouro Branco',
            'Escola Municipal Padre José de Anchieta',
            'Escola Municipal Papa Pio XII',
            'Escola Municipal Paulo Freire',
            'Escola Municipal Profª Analides de Oliveira Caruso',
            'Escola Municipal Profª Maria Augusta Amaral Picelli',
            'Escola Municipal Rui Barbosa',
            'Escola Municipal São Cristóvão',
            'Escola Municipal São Francisco de Assis',
            'Escola Municipal Sebastião de Mattos',
            'Escola Municipal Senador Souza Naves',
            'Escola Municipal Serra dos Dourados',
            'Escola Municipal Tempo Integral',
            'Escola Municipal Vinícius de Morais',
        ];

        $cmeis = [
            'CMEI Cecília Meireles',
            'CMEI Cora Coralina',
            'CMEI Graciliano Ramos',
            'CMEI Helena Kolody',
            'CMEI Jardim Birigui',
            'CMEI Madre Paulina',
            'CMEI Maria Arlete Alves dos Santos',
            'CMEI Maria Montessori',
            'CMEI Ignácio Urbainski',
            'CMEI Professora Maria Yokohama Watanabe',
            'CMEI Nelly Gonçalves',
            'CMEI Rachel de Queiroz',
            'CMEI Ranice Benedito de Araújo Teixeira',
            'CMEI Rubem Alves',
            'CMEI São Cristóvão',
            'CMEI São Francisco de Assis',
            'CMEI São Paulo Apóstolo',
            'CMEI Tarsila do Amaral',
            'CMEI Vilmar Silveira',
            'CEI Anjo da Guarda',
        ];

        $estaduais = [
            'Colégio Estadual Bento Mossurunga',
            'CEEBJA Umuarama',
            'Escola Estadual Durval Seifert',
            'Colégio Estadual Profª Hilda Trautwein Kamal',
            'Escola Estadual Izabel',
            'Escola Estadual Jardim Canadá',
            'Colégio Estadual Jardim Cruzeiro',
            'Colégio Estadual Vereador José Balan',
            'Colégio Estadual Lourenço Filho (Serra dos Dourados)',
            'Colégio Estadual Lovat',
            'Colégio Estadual Pe. Manuel da Nóbrega',
            'Colégio Estadual Monteiro Lobato',
            'Colégio Estadual Parque Jabuticabeira',
            'Colégio Estadual Prof. Paulo A. Tomazinho',
            'Colégio Estadual Pedro II',
            'Colégio Estadual Santa Eliza',
            'Colégio Estadual Tiradentes',
            'Colégio Agrícola de Umuarama',
            'Colégio Estadual Dra. Zilda Arns',
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
