<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use App\Models\PontosDeParada;

class EditRota extends EditRecord
{
    protected static string $resource = RotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    /** Vamos guardar os pontos do form para usar no afterSave */
    protected array $pontosTmp = [];
    protected array $escolasTmp = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rows = PontosDeParada::with('escola:id,nome')
            ->where('id_rota', $this->record->id)
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        $data['pontos'] = $rows->map(fn($p) => [
            'id'        => (int) $p->id,            // 👈 incluímos o ID!
            'ordem'     => (int) $p->ordem,
            'latitude'  => (float) $p->latitude,
            'longitude' => (float) $p->longitude,
            'tipo'      => $p->tipo,                // 'ponto' | 'escola'
            'id_escola' => $p->id_escola,
            'rotulo'    => $p->tipo === 'escola' ? ('Escola ' . optional($p->escola)->nome) : null,
            'raio'      => null,
        ])->values()->all();

        $data['escola_id'] = $this->record->escolas()->pluck('escolas.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // pegue o estado atual do form para os arrays que não são fillables do model
        $this->pontosTmp  = $this->form->getRawState()['pontos']    ?? [];
        $this->escolasTmp = $this->form->getRawState()['escola_id'] ?? [];

        // remova para não tentar salvar em 'rotas'
        unset($data['pontos'], $data['escola_id']);

        return $data; // aqui ficam só 'nome', 'turno', etc.
    }

    protected function afterSave(): void
    {
        DB::transaction(function () {
            $this->record->escolas()->sync($this->escolasTmp ?? []);

            $existing = PontosDeParada::where('id_rota', $this->record->id)
                ->get()
                ->keyBy('id');
            $seen = [];

            foreach (array_values($this->pontosTmp ?? []) as $idx => $p) {
                $attrs = [
                    'latitude'  => (float) ($p['latitude']  ?? 0),
                    'longitude' => (float) ($p['longitude'] ?? 0),
                    'ordem'     => $idx + 1,
                    'tipo'      => (($p['tipo'] ?? 'ponto') === 'escola') ? 'escola' : 'ponto',
                    'id_escola' => (($p['tipo'] ?? 'ponto') === 'escola') ? ($p['id_escola'] ?? null) : null,
                ];

                if (!empty($p['id']) && $existing->has($p['id'])) {
                    $existingPoint = $existing[$p['id']];
                    $existingPoint->fill($attrs);
                    $existingPoint->save();
                    $seen[] = (int) $p['id'];
                } else {
                    $this->record->pontosDeParada()->create($attrs);
                }
            }

            $toDelete = $existing->keys()->diff($seen);
            if ($toDelete->isNotEmpty()) {
                PontosDeParada::whereIn('id', $toDelete)->delete();
            }
        });
    }
}
