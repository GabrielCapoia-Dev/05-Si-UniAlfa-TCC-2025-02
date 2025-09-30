<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class Mapa extends Field
{
    protected string $view = 'forms.components.mapa';

    // Define o estado inicial como array vazio
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
}