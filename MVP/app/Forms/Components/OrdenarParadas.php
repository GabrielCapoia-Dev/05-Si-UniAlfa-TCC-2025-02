<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class OrdenarParadas extends Field
{
    protected string $view = 'forms.components.ordenar-paradas';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(true);
        
        $this->dehydrateStateUsing(function ($state) {
            if (!is_array($state)) {
                return [];
            }
            
            return collect($state)->map(function ($ponto, $index) {
                return [
                    'ordem' => $ponto['ordem'] ?? ($index + 1),
                    'latitude' => (float) ($ponto['latitude'] ?? 0),
                    'longitude' => (float) ($ponto['longitude'] ?? 0),
                    'tipo' => $ponto['tipo'] ?? 'ponto',
                    'id_escola' => isset($ponto['id_escola']) ? (int) $ponto['id_escola'] : null,
                    'nome_escola' => $ponto['rotulo'] ?? $ponto['nome_escola'] ?? null,
                    'raio' => $ponto['raio'] ?? null,
                ];
            })->values()->all();
        });
    }
}