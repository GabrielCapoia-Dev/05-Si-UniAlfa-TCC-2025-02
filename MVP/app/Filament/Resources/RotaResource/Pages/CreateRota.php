<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\RotaService;

class CreateRota extends CreateRecord
{
    protected static string $resource = RotaResource::class;

    public ?array $data = [];

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(RotaService::class)->mudarEstadoFormDepoisDeSalvar($data);
    }

    public function processarRota(array $payload, $data): void
    {
        app(RotaService::class)->processarRota($payload, $data);
    }

    protected function afterCreate($data, $record): void
    {
        app(RotaService::class)->criarPontosTransaction($data, $record);
    }

    /**
     * Livewire: dispara sempre que uma prop muda.
     * Aqui monitoramos 'data.pontos' e 'data.valor_por_km'.
     */
    public function updated($name, $value): void
    {
        if ($name === 'data.pontos') {
            $qtd = is_array($value) ? count(array_filter($value)) : 0;
            if ($qtd < 2) {
                $this->data['distancia_total'] = null;
                $this->data['tempo_estimado']  = null;
                $this->data['geometry']        = null;
                $this->data['waypoints']       = null;
                $this->data['legs']            = null;
                $this->data['valor_total']     = 0.00;
            } else {
                $this->recomputarValorTotal();
            }
        }

        if ($name === 'data.valor_por_km') {
            $this->recomputarValorTotal();
        }
    }


}
