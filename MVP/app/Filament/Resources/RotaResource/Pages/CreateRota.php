<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Services\RotaService;

class CreateRota extends CreateRecord
{
    protected static string $resource = RotaResource::class;

    protected array $pontosTmp = [];
    protected array $escolasTmp = [];

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $pontos  = $data['pontos']    ?? [];
        $escolas = $data['escola_id'] ?? [];

        if (count($pontos) < 2) {
            throw ValidationException::withMessages([
                'pontos' => 'Adicione ao menos 2 paradas para a rota.',
            ]);
        }

        if (empty($escolas)) {
            throw ValidationException::withMessages([
                'escola_id' => 'Selecione ao menos uma escola.',
            ]);
        }

        $this->pontosTmp  = $pontos;
        $this->escolasTmp = $escolas;

        unset($data['pontos'], $data['escola_id']);

        return $data;
    }


    public function processarRota(array $payload)
    {
        // atualiza apenas campos do form sem resetar pontos
        $this->form->fill([
            'distancia_total' => round($payload['distance'] / 1000, 2),
            'tempo_estimado'  => round($payload['duration'] / 60),
            'geometry'        => $payload['geometry'] ?? null,
            'waypoints'       => $payload['waypoints'] ?? null,
            'legs'            => $payload['legs'] ?? null,
        ], false); // false = mantém outros campos
    }



    protected function afterCreate(): void
    {
        DB::transaction(function () {
            if (!empty($this->escolasTmp)) {
                $this->record->escolas()->sync($this->escolasTmp);
            }

            $payload = [];
            foreach ($this->pontosTmp as $i => $p) {
                $lat = $p['latitude']  ?? null;
                $lng = $p['longitude'] ?? null;
                if ($lat === null || $lng === null) continue;

                $tipo      = ($p['tipo'] ?? 'ponto') === 'escola' ? 'escola' : 'ponto';
                $idEscola  = $tipo === 'escola' ? ($p['id_escola'] ?? null) : null;

                $payload[] = [
                    'latitude'   => (float) $lat,
                    'longitude'  => (float) $lng,
                    'ordem'      => (int) ($p['ordem'] ?? ($i + 1)), 
                    'tipo'       => $tipo,
                    'id_escola'  => $idEscola,
                ];
            }

            if ($payload) {
                $this->record->pontosDeParada()->createMany($payload);
            }
        });
    }
}
