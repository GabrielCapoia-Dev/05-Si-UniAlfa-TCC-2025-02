<?php

namespace App\Filament\Resources\PontosDeParadaResource\Pages;

use App\Filament\Resources\PontosDeParadaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Http;

class CreatePontosDeParada extends CreateRecord
{
    protected static string $resource = PontosDeParadaResource::class;


    public function mapClicked(array $coords)
    {

        $lat = $coords['lat'];
        $lng = $coords['lng'];

        // Consulta Nominatim para endereço
        $nominatim = Http::withHeaders([
            'User-Agent' => 'MeuApp/1.0 (meuemail@dominio.com)'
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'jsonv2',
            'addressdetails' => 1,
        ]);

        if ($nominatim->successful()) {
            $data = $nominatim->json();
            $address = $data['address'] ?? [];


            $this->form->fill([
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'logradouro' => $address['road'] ?? null,
                'bairro' => $address['suburb'] ?? $address['neighbourhood'] ?? null,
                'cidade' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'uf' => $address['state'] ?? null,
                'cep' => $address['postcode'] ?? null,
                'complemento' => $data['name'] ?? null,
            ]);
        }
    }
}