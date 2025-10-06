<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

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

        // Guarda para o afterCreate()
        $this->pontosTmp  = $pontos;
        $this->escolasTmp = $escolas;

        unset($data['pontos'], $data['escola_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            // Pivot escolas
            if (!empty($this->escolasTmp)) {
                $this->record->escolas()->sync($this->escolasTmp);
            }

            // Pontos
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
                    'ordem'      => (int) ($p['ordem'] ?? ($i + 1)), // fallback seguro
                    'tipo'       => $tipo,
                    'id_escola'  => $idEscola,
                ];
            }

            if ($payload) {
                // Se editar no futuro, pode limpar antes:
                // $this->record->pontosDeParada()->delete();
                $this->record->pontosDeParada()->createMany($payload);
            }
        });
    }
}
