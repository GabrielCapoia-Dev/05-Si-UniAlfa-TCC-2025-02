<?php

namespace App\Services;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\Mapa;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use App\Forms\Components\OrdenarParadas;
use App\Models\Escola;
use App\Models\PontosDeParada;

class RotaService
{

    //** Configura a tabela completa (paginações, colunas, filtros, ações, ordenação). */
    public function configurarTabela(Table $table, ?User $user): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->modifyQueryUsing(fn(Builder $query) => $this->aplicarContadores($query))
            ->columns($this->colunasTabela())
            ->filters($this->filtrosTabela($user))
            ->actions($this->acoesTabela($user))
            ->bulkActions($this->acoesEmMassa($user))
            ->defaultSort('updated_at', 'desc')
            ->striped();

    }

    //** Modifica a query da tabela para retornar os contadores dos dados */
    private function aplicarContadores(Builder $query): Builder
    {
        // ACHO QUE ESSE METODO VAI AJUDAR COM A EXPORTAÇÃO DE RELATORIOS ESTRAGEGICOS
        return $query
            ->withCount([
                'pontosDeParada',
                'escolas',
                'pontosDeParada as pontos_count' => fn($q) => $q->where('tipo', 'ponto'),
                'pontosDeParada as pontos_escola_count' => fn($q) => $q->where('tipo', 'escola'),
            ]);
    }

    //** Retorna as colunas da tabela */
    private function colunasTabela(): array
    {
        return [
            Tables\Columns\TextColumn::make('nome')
                ->searchable()
                ->sortable(),

            Tables\Columns\BadgeColumn::make('turno')
                ->colors([
                    'success' => 'Manhã',
                    'warning' => 'Tarde',
                    'info'    => 'Noite',
                    'primary' => 'Integral',
                ])
                ->sortable(),

            Tables\Columns\TextColumn::make('pontos_de_parada_count')
                ->label('Paradas')
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('escolas_count')
                ->label('Escolas')
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('pontos_count')
                ->label('Pontos')
                ->numeric()
                ->toggleable(),

            Tables\Columns\TextColumn::make('escolas.nome')
                ->label('Escolas')
                ->badge()
                ->limitList(3)
                ->separator(', ')
                ->toggleable(),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    //** Retorna os filtros da tabela */
    private function filtrosTabela($record): array
    {
        return [
            Tables\Filters\SelectFilter::make('turno')
                ->options([
                    'Manhã' => 'Manhã',
                    'Tarde' => 'Tarde',
                    'Noite' => 'Noite',
                    'Integral' => 'Integral',
                ]),
        ];
    }

    //** Retorna as acoes da tabela */
    private function acoesTabela($record): array
    {
        return [
            Tables\Actions\Action::make('visualizar')
                ->label('Ver Detalhes')
                ->icon('heroicon-m-eye')
                ->color('info')
                ->action(function (\App\Models\Rota $record, $livewire) {
                    $livewire->dispatch('abrirDetalhesRota', $record->id);
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    //** Retorna as acoes em massa da tabela */
    private function acoesEmMassa($record): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),
        ];
    }


    //** Configura o formulário completo */
    public function configurarFormulario(Form $form): Form
    {
        return $form->schema($this->schemaFormulario());
    }

    /** Define todo o schema do formulário: mapa, campos de rota e seletores auxiliares. */
    private function schemaFormulario(): array
    {
        return [
            Grid::make(12)
                ->schema([

                    Mapa::make('pontos')
                        ->label('Mapa da Rota')
                        ->rotaAtiva(true)
                        ->afterStateHydrated(fn(Mapa $component, $state, $record) => $this->preencheMapaComPontosDaRota($component, $state, $record))
                        ->columnSpan(7),

                    Grid::make(12)
                        ->schema([
                            Forms\Components\TextInput::make('nome')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(12),

                            Forms\Components\Select::make('escola_id')
                                ->label('Escolas')
                                ->relationship('escolas', 'nome')
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->columnSpan(12)
                                ->dehydrated(true)
                                ->afterStateHydrated(fn($state, $get, $set, $record) => $this->preencheEscolasSelecionadasNosPontos($state, $get, $set, $record))
                                ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                                    $pontos = $get('pontos') ?? [];
                                    $idsSelecionados = collect($state ?? [])->map(fn($v) => (int)$v)->all();

                                    $pontos = array_values(array_filter($pontos, function ($p) use ($idsSelecionados) {
                                        if (($p['tipo'] ?? '') !== 'escola') return true;
                                        return in_array((int)($p['id_escola'] ?? 0), $idsSelecionados, true);
                                    }));

                                    $presentes = [];
                                    foreach ($pontos as $p) {
                                        if (($p['tipo'] ?? '') === 'escola' && isset($p['id_escola'])) {
                                            $presentes[(int)$p['id_escola']] = true;
                                        }
                                    }
                                    $faltando = array_values(array_diff($idsSelecionados, array_keys($presentes)));

                                    if (!empty($faltando)) {
                                        $escolas = Escola::whereIn('id', $faltando)->get(['id', 'nome', 'latitude', 'longitude']);
                                        foreach ($escolas as $esc) {
                                            if ($esc->latitude !== null && $esc->longitude !== null) {
                                                $pontos[] = [
                                                    'latitude'   => (float)$esc->latitude,
                                                    'longitude'  => (float)$esc->longitude,
                                                    'ordem'      => 0,
                                                    'tipo'       => 'escola',
                                                    'id_escola'  => (int)$esc->id,
                                                    'rotulo'     => 'Escola ' . $esc->nome,
                                                ];
                                            }
                                        }
                                    }

                                    foreach ($pontos as $i => &$p) $p['ordem'] = $i + 1;
                                    unset($p);

                                    $set('pontos', $pontos);
                                    $livewire->dispatch('pontos-updated');
                                }),


                            OrdenarParadas::make('ordenador_paradas')
                                ->statePath('pontos')
                                ->label('Ordenar Paradas')
                                ->dehydrated(true)
                                ->columnSpan(12),

                            Forms\Components\Select::make('turno')
                                ->options([
                                    'Manhã' => 'Manhã',
                                    'Tarde' => 'Tarde',
                                    'Noite' => 'Noite',
                                    'Integral' => 'Integral',
                                ])
                                ->required()
                                ->columnSpan(12),
                        ])
                        ->columnSpan(5),

                    Forms\Components\TextInput::make('distancia_total')
                        ->label('Distância Total (km)')
                        ->readOnly()
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('tempo_estimado')
                        ->label('Tempo Estimado (min)')
                        ->readOnly()
                        ->columnSpan(6),

                ]),
        ];
    }

    public function atualizarRota($rota, array $payload)
    {
        dd($payload, $rota);
        $rota->geometry = $payload['geometry'];
        $rota->waypoints = $payload['waypoints'];
        $rota->legs = $payload['legs'];
        $rota->save();
    }


    /** Preenche o mapa com os pontos da rota do registro atual e retorna o array de pontos. */
    private function preencheMapaComPontosDaRota(Mapa $mapa, $estado, $registro)
    {
        if (!$registro || !empty($estado)) return;

        $linhasPontos = PontosDeParada::with('escola:id,nome')
            ->where('id_rota', $registro->id)
            ->orderBy('ordem')
            ->orderBy('id')
            ->get();

        $pontos = $linhasPontos->map(function ($ponto) {
            return [
                'ordem'     => (int) $ponto->ordem,
                'latitude'  => (float) $ponto->latitude,
                'longitude' => (float) $ponto->longitude,
                'tipo'      => $ponto->tipo,
                'id_escola' => $ponto->id_escola,
                'rotulo'    => $ponto->tipo === 'escola'
                    ? ('Escola ' . optional($ponto->escola)->nome)
                    : null,
                'raio'      => null,
            ];
        })->values()->all();

        $mapa->state($pontos);

        return $pontos;
    }

    /** Garante que as escolas selecionadas virem pontos no mapa (cria faltantes e reordena). */
    private function preencheEscolasSelecionadasNosPontos($estado, callable $obter, callable $definir, $registro)
    {
        if ($registro && !empty($obter('pontos'))) return;

        $pontos = $obter('pontos') ?? [];
        $idsEscolasSelecionadas = collect($estado ?? [])->map(fn($v) => (int) $v)->all();

        if (empty($idsEscolasSelecionadas)) return;

        $escolasJaNosPontos = [];
        foreach ($pontos as $ponto) {
            if (($ponto['tipo'] ?? null) === 'escola' && isset($ponto['id_escola'])) {
                $escolasJaNosPontos[(int) $ponto['id_escola']] = true;
            }
        }

        $idsEscolasFaltantes = array_values(array_diff($idsEscolasSelecionadas, array_keys($escolasJaNosPontos)));
        if (!empty($idsEscolasFaltantes)) {
            $escolas = Escola::whereIn('id', $idsEscolasFaltantes)->get(['id', 'nome', 'latitude', 'longitude']);
            foreach ($escolas as $escola) {
                if ($escola->latitude !== null && $escola->longitude !== null) {
                    $pontos[] = [
                        'latitude'   => (float) $escola->latitude,
                        'longitude'  => (float) $escola->longitude,
                        'ordem'      => 0,
                        'tipo'       => 'escola',
                        'id_escola'  => (int) $escola->id,
                        'rotulo'     => 'Escola ' . $escola->nome,
                    ];
                }
            }
            foreach ($pontos as $i => &$ponto) $ponto['ordem'] = $i + 1;
            unset($ponto);

            $definir('pontos', $pontos);
        }

        return $pontos;
    }
}
