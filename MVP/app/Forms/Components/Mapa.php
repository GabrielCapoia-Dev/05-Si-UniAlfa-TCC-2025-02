<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class Mapa extends Field
{
    protected string $view = 'forms.components.mapa';

    protected bool $rotaAtiva = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (Mapa $component, $state) {
            if (is_null($state)) {
                $component->state([]);
            }
        });
    }
    
    public function rotaAtiva(bool $valor = true): static
    {
        $this->rotaAtiva = $valor;
        return $this->extraAttributes([
            'rota-ativa' => $valor ? '1' : '0',
        ]);
    }
}
