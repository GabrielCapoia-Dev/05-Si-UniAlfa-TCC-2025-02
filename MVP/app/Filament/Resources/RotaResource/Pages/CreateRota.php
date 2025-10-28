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
        return app(RotaService::class)->mudarEstadoFormAntesDeSalvar(
            $data,
            $this->data ?? []
        );
    }

    public function processarRota(array $payload): void
    {
        $this->data = app(RotaService::class)->processarRota($payload, $this->data ?? []);
    }

    protected function afterCreate(): void
    {
        $data = $this->data ?? $this->form->getState();
        $record = $this->record;

        app(RotaService::class)
            ->criarPontosTransaction($data, $record);
    }

    //  Livewire: dispara sempre que uma prop muda.
    public function updated($name, $value): void
    {
        if ($name === 'data.pontos') {
            $qtd = is_array($value) ? count(array_filter($value)) : 0;

            if ($qtd < 2) {
                $this->data = app(RotaService::class)
                    ->zerarEstadoQuandoSemPontos($this->data ?? []);
            } else {
                $this->data = app(RotaService::class)
                    ->recomputarValorTotal($this->data ?? []);
            }
        }

        if ($name === 'data.valor_por_km') {
            $this->data = app(RotaService::class)
                ->recomputarValorTotal($this->data ?? []);
        }
    }
}
