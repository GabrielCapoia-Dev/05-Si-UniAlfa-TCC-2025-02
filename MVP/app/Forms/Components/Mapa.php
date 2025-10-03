<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class Mapa extends Field
{
    protected string $view = 'forms.components.mapa';

    protected bool $rotaAtiva = true; // por padrão mantém o modo rota

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

    /**
     * Define se o cálculo de rota estará ativo (padrão: true)
     */
    public function rotaAtiva(bool $valor = true): static
    {
        $this->rotaAtiva = $valor;
        return $this->extraAttributes([
            'rota-ativa' => $valor ? '1' : '0',
        ]);
    }
}
