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
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class RotaService
{

    protected array $pontosTmp = [];
    protected array $escolasTmp = [];

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
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('escolas_count')
                ->label('Escolas')
                ->numeric()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('pontos_count')
                ->label('Pontos')
                ->numeric()
                ->toggleable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('distancia_total')
                ->label('Distancia Total')
                ->numeric()
                ->sortable()
                ->suffix(' km')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('tempo_estimado')
                ->label('Tempo Estimado')
                ->numeric()
                ->sortable()
                ->suffix(' min')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('valor_por_km')
                ->label('Valor por Km')
                ->numeric()
                ->sortable()
                ->prefix('R$ ')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('valor_total')
                ->label('Valor Total')
                ->numeric()
                ->sortable()
                ->prefix('R$ ')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('escolas.nome')
                ->label('Escolas')
                ->badge()
                ->limitList(3)
                ->separator(', ')
                ->toggleable()
                ->toggleable(isToggledHiddenByDefault: true),

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
                                ->columnSpan(6),

                            Forms\Components\Select::make('turno')
                                ->options([
                                    'Manhã' => 'Manhã',
                                    'Tarde' => 'Tarde',
                                    'Noite' => 'Noite',
                                    'Integral' => 'Integral',
                                ])
                                ->required()
                                ->columnSpan(6),

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



                            Forms\Components\TextInput::make('distancia_total')
                                ->label('Distância Total')
                                ->numeric()
                                ->disabled()
                                ->suffix(' km')
                                ->readOnly()
                                ->columnSpan(6),

                            Forms\Components\TextInput::make('tempo_estimado')
                                ->label('Tempo Estimado')
                                ->numeric()
                                ->disabled()
                                ->suffix(' min')
                                ->readOnly()
                                ->columnSpan(6),

                            Forms\Components\TextInput::make('valor_por_km')
                                ->label('Valor por Km')
                                ->numeric()
                                ->prefix('R$ ')
                                ->live(debounce: 300)
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $km    = (float) ($get('distancia_total') ?? 0);
                                    $preco = (float) ($state ?? 0);

                                    $total = ($km > 0 && $preco > 0) ? round($km * $preco, 2) : 0.00;
                                    $set('valor_total', $total);
                                })
                                ->columnSpan(12),

                            Forms\Components\TextInput::make('valor_total')
                                ->label('Valor Total')
                                ->numeric()
                                ->prefix('R$ ')
                                ->readOnly()
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(12),
                        ])
                        ->columnSpan(5),
                    OrdenarParadas::make('ordenador_paradas')
                        ->statePath('pontos')
                        ->label('Ordenar Paradas')
                        ->dehydrated(true)
                        ->columnSpan(12),

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

    public function mudarEstadoFormDepoisDeSalvar($data): array
    {
        return $this->atualizarForm($data);
    }

    private function atualizarForm($data): array
    {
        $pontos  = $data['pontos']    ?? [];
        $escolas = $data['escola_id'] ?? [];

        if (count($pontos) < 2) {
            throw ValidationException::withMessages([
                'pontos' => 'Adicione ao menos 2 paradas para a rota.',
            ]);
        }

        if (empty($escolas)) {
            throw ValidationException::withMessages([
                'escola_id' => 'Selecione ao menos uma escola.',
            ]);
        }

        $this->pontosTmp  = $pontos;
        $this->escolasTmp = $escolas;

        unset($data['pontos'], $data['escola_id']);

        $data['distancia_total'] = $data['distancia_total'] ?? null;
        $data['tempo_estimado']  = $data['tempo_estimado']  ?? null;
        $data['geometry']        = $data['geometry']        ?? null;
        $data['waypoints']       = $data['waypoints']       ?? null;
        $data['legs']            = $data['legs']            ?? null;

        if (!$this->temParadasOrdenadas($data)) {
            $data['valor_total'] = 0.00;
        } else {
            $km    = (float)($data['distancia_total'] ?? 0);
            $preco = (float)($data['valor_por_km']    ?? $data['valor_por_km'] ?? 0);
            $data['valor_total'] = ($km > 0 && $preco > 0) ? round($km * $preco, 2) : 0.00;
        }

        return $data;
    }


    private function temParadasOrdenadas($data): bool
    {
        if (!$data) {
            return false;
        }
        $ordem = $data['ordenar_paradas'] ?? $data['pontos'] ?? [];
        if (!is_array($ordem)) return false;

        $validos = array_values(array_filter(
            $ordem,
            fn($p) =>
            is_array($p) && isset($p['latitude'], $p['longitude'])
        ));

        return count($validos) >= 2;
    }

    public function recomputarValorTotal(array $data): array
    {
        if (!$this->temParadasOrdenadas($data)) {
            $data['valor_total'] = 0.00;
            return $data;
        }

        $km    = $this->parseNumber($data['distancia_total'] ?? 0);
        $preco = $this->parseNumber($data['valor_por_km']    ?? 0);

        $data['valor_total'] = ($km > 0 && $preco > 0) ? round($km * $preco, 2) : 0.00;
        return $data;
    }

    public function processarRota(array $payload, ?array $data): array
    {
        $data = $data ?? [];

        $metros   = (float)($payload['distance']  ?? 0);
        $segundos = (float)($payload['duration'] ?? 0);

        $km  = round($metros / 1000, 2);
        $min = (int) round($segundos / 60);

        $data['distancia_total'] = $km ?: null;
        $data['tempo_estimado']  = $min ?: null;

        $data['geometry']  = $payload['geometry']  ?? null;
        $data['waypoints'] = $payload['waypoints'] ?? null;
        $data['legs']      = $payload['legs']      ?? null;

        return $this->recomputarValorTotal($data);
    }

    public function criarPontosTransaction($data, $record): void
    {
        $pontos  = $data['pontos']    ?? [];
        $escolas = $data['escola_id'] ?? [];

        $this->pontosTmp  = $pontos;
        $this->escolasTmp = $escolas;

        DB::transaction(function () use ($record) {
            if (!empty($this->escolasTmp)) {
                $record->escolas()->sync($this->escolasTmp);
            }

            $payload = [];
            foreach ($this->pontosTmp as $i => $p) {
                $lat = $p['latitude']  ?? null;
                $lng = $p['longitude'] ?? null;
                if ($lat === null || $lng === null) continue;

                $tipo      = ($p['tipo'] ?? 'ponto') === 'escola' ? 'escola' : 'ponto';
                $idEscola  = $tipo === 'escola' ? ($p['id_escola'] ?? null) : null;

                $payload[] = [
                    'latitude'   => (float) $lat,
                    'longitude'  => (float) $lng,
                    'ordem'      => (int) ($p['ordem'] ?? ($i + 1)),
                    'tipo'       => $tipo,
                    'id_escola'  => $idEscola,
                ];
            }

            if ($payload) {
                $record->pontosDeParada()->createMany($payload);
            }
        });
    }

    /** Zera dist/tempo/geo e valor_total quando não há 2+ pontos. */
    public function zerarEstadoQuandoSemPontos(array $state): array
    {
        $state['distancia_total'] = null;
        $state['tempo_estimado']  = null;
        $state['geometry']        = null;
        $state['waypoints']       = null;
        $state['legs']            = null;
        $state['valor_total']     = 0.00;
        return $state;
    }

    /**
     * Normaliza o array $data antes de salvar na EDIÇÃO,
     * hidratando dist/tempo/geo e consolidando valor_total (ou zerando).
     * Use também no CREATE se preferir — a assinatura aceita $state.
     */
    public function mudarEstadoFormAntesDeSalvarEdit(array $data, array $state): array
    {
        // traga valores calculados do $state (úteis se inputs estão disabled/readOnly)
        $data['distancia_total'] = $state['distancia_total'] ?? null;
        $data['tempo_estimado']  = $state['tempo_estimado']  ?? null;
        $data['geometry']        = $state['geometry']        ?? null;
        $data['waypoints']       = $state['waypoints']       ?? null;
        $data['legs']            = $state['legs']            ?? null;

        if (!$this->temParadasOrdenadas($state)) {
            $data['valor_total'] = 0.00;
        } else {
            $km    = (float)($data['distancia_total'] ?? 0);
            $preco = (float)($data['valor_por_km']    ?? $state['valor_por_km'] ?? 0);
            $data['valor_total'] = ($km > 0 && $preco > 0) ? round($km * $preco, 2) : 0.00;
        }

        return $data;
    }

    // dentro de App\Services\RotaService

    private function parseNumber(mixed $v): float
    {
        if (is_null($v) || $v === '') return 0.0;
        if (is_float($v) || is_int($v)) return (float) $v;
        $s = (string) $v;

        // remove espaços / NBSP
        $s = str_replace(["\u{00A0}", ' '], '', $s);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            // Formato brasileiro: 1.234,56 -> 1234.56
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // Se só tem vírgula, trate como decimal
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }
}
