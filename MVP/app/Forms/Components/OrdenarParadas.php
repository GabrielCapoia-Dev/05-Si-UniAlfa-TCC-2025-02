<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class OrdenarParadas extends Field
{
    protected string $view = 'forms.components.ordenar-paradas';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }
}
