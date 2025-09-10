<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class LeafletCirclePicker extends Field
{
    protected string $view = 'forms.components.leaflet-circle-picker';


    public static function defaultCss(): array
    {
        return [
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        ];
    }

    public static function defaultJs(): array
    {
        return [
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        ];
    }

    protected int $defaultRadius = 2000;
    protected float $defaultLat = -23.7637;
    protected float $defaultLng = -53.2967;

    public function defaultLat(float $lat): static
    {
        $this->defaultLat = $lat;
        return $this;
    }

    public function defaultLng(float $lng): static
    {
        $this->defaultLng = $lng;
        return $this;
    }

    public function defaultRadius(int $radius): static
    {
        $this->defaultRadius = $radius;
        return $this;
    }

    public function getDefaultLat(): float
    {
        return $this->defaultLat;
    }

    public function getDefaultLng(): float
    {
        return $this->defaultLng;
    }

    public function getDefaultRadius(): int
    {
        return $this->defaultRadius;
    }

    public function getHelperText(): ?string
    {
        return $this->helperText ?? 'Clique no mapa ou arraste o marcador para definir o ponto de parada';
    }
}