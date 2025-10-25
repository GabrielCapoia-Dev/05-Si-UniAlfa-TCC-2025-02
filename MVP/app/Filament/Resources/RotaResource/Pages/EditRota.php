<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use App\Models\PontosDeParada;
use App\Services\RotaService;

class EditRota extends EditRecord
{
    protected static string $resource = RotaResource::class;

    // ✅ estado do formulário (necessário para reatividade)
    public ?array $data = [];

    /** Vamos guardar os pontos do form para usar no afterSave */
    protected array $pontosTmp = [];
    protected array $escolasTmp = [];

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rows = PontosDeParada::with('escola:id,nome')
            ->where('id_rota', $this->record->id)
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        $data['pontos'] = $rows->map(fn($p) => [
            'id'        => (int) $p->id,
            'ordem'     => (int) $p->ordem,
            'latitude'  => (float) $p->latitude,
            'longitude' => (float) $p->longitude,
            'tipo'      => $p->tipo,
            'id_escola' => $p->id_escola,
            'rotulo'    => $p->tipo === 'escola' ? ('Escola ' . optional($p->escola)->nome) : null,
            'raio'      => null,
        ])->values()->all();

        $data['escola_id'] = $this->record->escolas()->pluck('escolas.id')->all();

        return $data;
    }

    /** Depois de preencher o form com os dados do registro, garanta coerência do valor_total */
    protected function afterFill(): void
    {
        $this->data = app(RotaService::class)->recomputarValorTotal($this->data ?? []);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // guarda os arrays crus para o afterSave (pontos e escolas)
        $raw = $this->form->getRawState();
        $this->pontosTmp  = $raw['pontos']    ?? [];
        $this->escolasTmp = $raw['escola_id'] ?? [];

        unset($data['pontos'], $data['escola_id']);

        // ✅ aplica a mesma regra do Create: hidrata dist/tempo/geo e valor_total coerente
        $data = app(RotaService::class)->mudarEstadoFormAntesDeSalvarEdit($data, $this->data ?? []);

        return $data;
    }

    protected function afterSave($data, $record): void
    {
        app(RotaService::class)->salvarRota($data, $record);
    }

    /** Chamado pelo JS do mapa (this.$wire.call('processarRota', payload)) */
    public function processarRota(array $payload): void
    {
        $this->data = app(RotaService::class)->processarRota($payload, $this->data ?? []);
    }

    /**
     * Reatividade:
     * - Se 'data.pontos' < 2: zera dist/tempo/geo e valor_total
     * - Se 'data.valor_por_km' mudar: recalcula valor_total
     */
    public function updated($name, $value): void
    {
        if ($name === 'data.pontos') {
            $qtd = is_array($value) ? count(array_filter($value)) : 0;
            if ($qtd < 2) {
                $this->data = app(RotaService::class)->zerarEstadoQuandoSemPontos($this->data ?? []);
            } else {
                $this->data = app(RotaService::class)->recomputarValorTotal($this->data ?? []);
            }
        }

        if ($name === 'data.valor_por_km') {
            $this->data = app(RotaService::class)->recomputarValorTotal($this->data ?? []);
        }
    }
}
