<?php

namespace App\Filament\Resources\PontosDeParadaResource\Pages;

use App\Filament\Resources\PontosDeParadaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Http;

class CreatePontosDeParada extends CreateRecord
{
    protected static string $resource = PontosDeParadaResource::class;

    /**
     * Método chamado quando o usuário clica no mapa
     * 🔹 Deve ser público para o LeafletMap conseguir chamar
     */
    public function mapClicked(array $coords)
    {
        $lat = $coords['lat'];
        $lng = $coords['lng'];

        $nominatim = Http::withHeaders([
            'User-Agent' => 'MeuApp/1.0 (meuemail@dominio.com)',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat'            => $lat,
            'lon'            => $lng,
            'format'         => 'jsonv2',
            'addressdetails' => 1,
        ]);

        if ($nominatim->successful()) {
            $data = $nominatim->json();
            $address = $data['address'] ?? [];

            $this->form->fill([
                'latitude'    => $lat,
                'longitude'   => $lng,
                'logradouro'  => $address['road'] ?? null,
                'bairro'      => $address['suburb'] ?? $address['neighbourhood'] ?? null,
                'cidade'      => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'uf'          => $address['state'] ?? null,
                'cep'         => $address['postcode'] ?? null,
                'complemento' => $data['name'] ?? null,
                'raio'        => $this->form->getState('raio') ?? 500, // garante valor padrão
            ]);
        }
    }

    /**
     * Antes de criar o registro, garante que latitude, longitude e raio estejam preenchidos
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se não vier latitude/longitude do mapa, faz geocoding pelo endereço
        if (empty($data['latitude']) || empty($data['longitude'])) {
            $endereco = "{$data['logradouro']}, {$data['bairro']}, {$data['cidade']}, {$data['uf']}, {$data['cep']}";

            $response = Http::withHeaders([
                'User-Agent' => 'MeuApp/1.0 (meuemail@dominio.com)',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q'      => $endereco,
                'format' => 'json',
                'limit'  => 1,
            ]);

            if ($response->successful() && isset($response[0])) {
                $data['latitude']  = $response[0]['lat'];
                $data['longitude'] = $response[0]['lon'];
            }
        }

        // Garante que o raio tenha valor
        if (empty($data['raio'])) {
            $data['raio'] = 500; // padrão
        }

        return $data;
    }
}
