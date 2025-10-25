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

    public ?array $data = [];
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

    // pré-processamento dos dados do banco
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

    // pós-processamento do form já preenchido
    protected function afterFill(): void
    {
        $this->data = app(RotaService::class)->recomputarValorTotal($this->data ?? []);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $raw = $this->form->getRawState();
        $this->pontosTmp  = $raw['pontos']    ?? [];
        $this->escolasTmp = $raw['escola_id'] ?? [];

        unset($data['pontos'], $data['escola_id']);

        $data = app(RotaService::class)->mudarEstadoFormAntesDeSalvarEdit($data, $this->data ?? []);

        return $data;
    }

    protected function afterSave(): void
    {
        DB::transaction(function () {
            $this->record->escolas()->sync($this->escolasTmp ?? []);

            $existing = PontosDeParada::where('id_rota', $this->record->id)
                ->get()
                ->keyBy('id');
            $incoming = array_values($this->pontosTmp ?? []);
            $incomingIds = collect($incoming)->pluck('id')->filter()->map(fn($v) => (int) $v)->values();

            $toDeleteFirst = $existing->keys()->diff($incomingIds);
            if ($toDeleteFirst->isNotEmpty()) {
                PontosDeParada::whereIn('id', $toDeleteFirst)->delete();
                $existing = PontosDeParada::where('id_rota', $this->record->id)->get()->keyBy('id');
            }

            $OFFSET = 100000;
            foreach ($incoming as $idx => $p) {
                if (!empty($p['id']) && $existing->has((int) $p['id'])) {
                    $existing[(int) $p['id']]->update(['ordem' => $idx + 1 + $OFFSET]);
                }
            }
            foreach ($incoming as $idx => $p) {
                if (empty($p['id']) || !$existing->has((int) $p['id'])) {
                    $this->record->pontosDeParada()->create([
                        'latitude'  => (float) ($p['latitude']  ?? 0),
                        'longitude' => (float) ($p['longitude'] ?? 0),
                        'ordem'     => $idx + 1,
                        'tipo'      => (($p['tipo'] ?? 'ponto') === 'escola') ? 'escola' : 'ponto',
                        'id_escola' => (($p['tipo'] ?? 'ponto') === 'escola') ? ($p['id_escola'] ?? null) : null,
                    ]);
                }
            }
            foreach ($incoming as $idx => $p) {
                if (!empty($p['id']) && $existing->has((int) $p['id'])) {
                    $existing[(int) $p['id']]->fill([
                        'latitude'  => (float) ($p['latitude']  ?? 0),
                        'longitude' => (float) ($p['longitude'] ?? 0),
                        'ordem'     => $idx + 1,
                        'tipo'      => (($p['tipo'] ?? 'ponto') === 'escola') ? 'escola' : 'ponto',
                        'id_escola' => (($p['tipo'] ?? 'ponto') === 'escola') ? ($p['id_escola'] ?? null) : null,
                    ])->save();
                }
            }
        });
    }

    /** Chamado pelo JS do mapa */
    public function processarRota(array $payload): void
    {
        $this->data = app(RotaService::class)->processarRota($payload, $this->data ?? []);
    }

    // Livewire: dispara sempre que uma prop muda.
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
