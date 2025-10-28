<?php

namespace App\Filament\Resources\RotaResource\Pages;

use App\Filament\Resources\RotaResource;
use App\Models\Rota;
use App\Models\PontosDeParada;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRotas extends ListRecords
{
    protected static string $resource = RotaResource::class;
    protected static string $view = 'components.layouts.list-rotas';

    // estado do painel
    public ?Rota $rotaSelecionada = null;
    public array $rotaPontos = [];   // pontos ordenados p/ o mapa
    public array $rotaEscolas = [];  // escolas (nome/id)
    public array $stats = [];        // contadores diversos

    protected $listeners = ['abrirDetalhesRota'];

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery()
            ->withCount([
                'pontosDeParada',
                'escolas',
                'pontosDeParada as pontos_count' => fn($q) => $q->where('tipo', 'ponto'),
                'pontosDeParada as pontos_escola_count' => fn($q) => $q->where('tipo', 'escola'),
            ]);
    }

    public function abrirDetalhesRota(int $id): void
    {
        $rota = Rota::with(['escolas:id,nome'])
            ->withCount(['pontosDeParada', 'escolas'])
            ->find($id);

        if (! $rota) {
            $this->fecharDetalhesRota();
            return;
        }

        $this->rotaSelecionada = $rota;

        // escolas p/ listar
        $this->rotaEscolas = $rota->escolas
            ->map(fn($e) => ['id' => $e->id, 'nome' => $e->nome])
            ->values()
            ->all();

        // pontos ordenados p/ o mapa
        $this->rotaPontos = PontosDeParada::with('escola:id,nome')
            ->where('id_rota', $rota->id)
            ->orderBy('ordem')->orderBy('id')
            ->get()
            ->map(function ($p) {
                return [
                    'id'        => (int) $p->id,
                    'ordem'     => (int) $p->ordem,
                    'latitude'  => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'tipo'      => $p->tipo,
                    'rotulo'    => $p->tipo === 'escola' ? ('Escola ' . optional($p->escola)->nome) : ('Ponto ' . (int)$p->ordem),
                ];
            })
            ->values()
            ->all();

        // stats
        $this->stats = [
            'paradas'        => (int) $rota->pontos_de_parada_count,
            'escolas'        => (int) $rota->escolas_count,
            'pontos'         => (int) ($rota->pontos_count ?? 0),
            'paradas_escola' => (int) ($rota->pontos_escola_count ?? 0),
        ];

        $this->dispatch('$refresh');
        $this->dispatch('rota-detalhes-opened');
    }

    public function fecharDetalhesRota(): void
    {
        $this->rotaSelecionada = null;
        $this->rotaPontos = [];
        $this->rotaEscolas = [];
        $this->stats = [];
    }
}
