<?php

namespace App\Livewire;

use Filament\Forms\Components\Field;

class LeafletMap extends Field
{
    protected string $view = 'forms.components.leaflet-map';

    protected int $defaultRadius = 500;

    public function defaultRadius(int $radius): static
    {
        $this->defaultRadius = $radius;

        $this->extraAttributes(['data-default-radius' => $radius]);

        return $this;
    }

    public function getDefaultRadius(): int
    {
        return $this->defaultRadius;
    }
}
