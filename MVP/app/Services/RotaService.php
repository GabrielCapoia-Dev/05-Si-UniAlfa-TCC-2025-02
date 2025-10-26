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
use App\Models\PontosDeParada;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\Escola;

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
                        ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                            $this->syncAll($get, $set, $livewire, 'pontos');
                        })
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
                                ])
                                ->required()
                                ->columnSpan(6),

                            Forms\Components\Select::make('escola_id')
                                ->label('Escolas')
                                ->relationship('escolas', 'nome')
                                ->multiple()
                                ->searchable()
                                ->columnSpan(12)
                                ->dehydrated(true)
                                ->afterStateHydrated(
                                    fn($state, $get, $set, $record) =>
                                    $this->preencheEscolasSelecionadasNosPontos($state, $get, $set, $record)
                                )
                                ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                                    $this->syncAll($get, $set, $livewire, 'escola');
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
                        ->afterStateUpdated(function ($state, callable $get, callable $set, $livewire) {
                            $this->syncEscolasFromPontos($state, $get, $set, $livewire);
                        })
                        ->columnSpan(12),


                ]),
        ];
    }

    //** Forçar re-render de do livewire */
    private function forceUpdatePontos(callable $set, array $pontos, $livewire = null): void
    {
        $set('pontos', []);
        $set('pontos', array_values($pontos));

        $livewire?->dispatch('pontos-updated');
        $livewire?->dispatch('ordenador-paradas-refresh');
    }

    //** Sincronizar escolas com o componente de ordenação */
    public function syncEscolasFromPontos($state, callable $get, callable $set, $livewire = null): void
    {
        $pontos = is_array($state) ? $state : ($get('pontos') ?? []);

        $idsPontos = [];
        foreach ($pontos as $p) {
            if (($p['tipo'] ?? '') === 'escola' && isset($p['id_escola'])) {
                $id = (int) $p['id_escola'];
                if ($id) $idsPontos[$id] = true;
            }
        }
        $idsNovos = array_values(array_keys($idsPontos));

        $idsAtuais = collect($get('escola_id') ?? [])
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        if ($idsNovos !== $idsAtuais) {
            $set('escola_id', $idsNovos);
            $livewire?->dispatch('pontos-updated');
        }
    }

    //** Formata um array de pontos para garantir que vai salvar da forma correta */
    private function buildPontosNormalized(array $pontos): array
    {
        $norm = [];
        foreach ($pontos as $p) {
            if (!is_array($p) || !isset($p['latitude'], $p['longitude'])) {
                continue;
            }
            $tipo = (($p['tipo'] ?? 'ponto') === 'escola') ? 'escola' : 'ponto';

            $norm[] = [
                'latitude'  => (float) $p['latitude'],
                'longitude' => (float) $p['longitude'],
                'tipo'      => $tipo,
                'id_escola' => $tipo === 'escola'
                    ? (isset($p['id_escola']) ? (int) $p['id_escola'] : null)
                    : null,
                'rotulo'    => $p['rotulo'] ?? ($tipo === 'escola' && !empty($p['id_escola'])
                    ? ('Escola #' . (int) $p['id_escola'])
                    : null),
                'ordem'     => 0,
            ];
        }
        foreach ($norm as $i => &$x) $x['ordem'] = $i + 1;
        unset($x);

        return $norm;
    }

    //** Extrai os ids das escolas do array de pontos */
    private function escolaIdsFromPontos(array $pontos): array
    {
        $ids = [];
        foreach ($pontos as $p) {
            if (($p['tipo'] ?? '') === 'escola' && !empty($p['id_escola'])) {
                $ids[(int) $p['id_escola']] = true;
            }
        }
        return array_values(array_keys($ids));
    }

    //** Preenche os campos para o carregamento do formulário */
    private function applyState(callable $set, array $state): void
    {
        $set('escola_id', $state['escola_id'] ?? []);
        $set('pontos', []);
        $set('pontos', array_values($state['pontos'] ?? []));

        if (array_key_exists('valor_total', $state)) {
            $set('valor_total', $state['valor_total']);
        }
    }

    //** Sincroniza todos os campos para o carregamento do formulário */
    public function syncAll(callable $get, callable $set, $livewire = null, string $source = 'pontos'): void
    {
        $state = [
            'pontos'          => $this->buildPontosNormalized($get('pontos') ?? []),
            'escola_id'       => collect($get('escola_id') ?? [])->map(fn($v) => (int) $v)->filter()->values()->all(),
            'distancia_total' => $get('distancia_total'),
            'tempo_estimado'  => $get('tempo_estimado'),
            'geometry'        => $get('geometry'),
            'waypoints'       => $get('waypoints'),
            'legs'            => $get('legs'),
            'valor_por_km'    => $get('valor_por_km'),
        ];

        if ($source === 'escola') {
            $sel = array_flip($state['escola_id']);
            $p = array_values(array_filter($state['pontos'], function ($x) use ($sel) {
                if (($x['tipo'] ?? '') !== 'escola') return true;
                $id = (int) ($x['id_escola'] ?? 0);
                return isset($sel[$id]);
            }));

            $present = $this->escolaIdsFromPontos($p);
            $missing = array_values(array_diff($state['escola_id'], $present));

            if ($missing) {
                $escolas = Escola::whereIn('id', $missing)
                    ->get(['id', 'nome', 'latitude', 'longitude']);
                foreach ($escolas as $esc) {
                    if ($esc->latitude !== null && $esc->longitude !== null) {
                        $p[] = [
                            'latitude'   => (float) $esc->latitude,
                            'longitude'  => (float) $esc->longitude,
                            'ordem'      => 0,
                            'tipo'       => 'escola',
                            'id_escola'  => (int) $esc->id,
                            'rotulo'     => 'Escola ' . $esc->nome,
                        ];
                    }
                }
            }
            $state['pontos'] = $this->buildPontosNormalized($p);
        } elseif ($source === 'pontos') {
            $state['escola_id'] = $this->escolaIdsFromPontos($state['pontos']);
        } elseif ($source === 'init') {
            if (empty($state['escola_id'])) {
                $state['escola_id'] = $this->escolaIdsFromPontos($state['pontos']);
            }
            $state['pontos'] = $this->buildPontosNormalized($state['pontos']);
        } elseif ($source === 'preco') {
        }

        $tem2ouMais = count($state['pontos']) >= 2;
        $km    = $this->parseNumber($state['distancia_total'] ?? 0);
        $preco = $this->parseNumber($state['valor_por_km'] ?? 0);
        $state['valor_total'] = ($tem2ouMais && $km > 0 && $preco > 0) ? round($km * $preco, 2) : 0.00;

        $this->applyState($set, $state, $livewire);
    }

    //** Valida a composição da rota. Se falhar, lança uma notificação. */
    private function validarComposicaoMinimaOuFalhar(array $state): void
    {
        $pontos   = $state['pontos']    ?? [];
        $escolasS = $state['escola_id'] ?? [];

        $validos = array_values(array_filter(
            $pontos,
            fn($p) => is_array($p) && isset($p['latitude'], $p['longitude'])
        ));
        $qtdValidos = count($validos);

        $temEscola = false;
        $temPonto  = false;
        foreach ($validos as $p) {
            $tipo = $p['tipo'] ?? 'ponto';
            if ($tipo === 'escola') $temEscola = true;
            else $temPonto = true;
            if ($temEscola && $temPonto) break;
        }

        $temEscolaSelecionada = is_array($escolasS) && count(array_filter($escolasS)) > 0;

        $erros = [];
        if ($qtdValidos < 2) {
            $erros[] = 'Inclua pelo menos 2 paradas no mapa.';
        }
        if (!$temEscola) {
            $erros[] = 'Inclua pelo menos 1 escola entre as paradas.';
        }
        if (!$temPonto) {
            $erros[] = 'Inclua pelo menos 1 ponto de parada (aluno/parada).';
        }
        if (!$temEscolaSelecionada) {
            $erros[] = 'Selecione pelo menos 1 escola na lista de escolas.';
        }

        if (!empty($erros)) {
            $mensagem = "Rota incompleta:\n- " . implode("\n- ", $erros);

            Notification::make()
                ->title('Não foi possível salvar')
                ->body($mensagem)
                ->danger()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'pontos'     => 'Para salvar a rota, é necessário: pelo menos 1 escola e 1 ponto.',
                'escola_id'  => $temEscolaSelecionada ? null : 'Selecione ao menos uma escola.',
            ]);
        }
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

    /** Preenche o array de escolas selecionadas nos pontos da rota do registro atual. */
    private function preencheEscolasSelecionadasNosPontos($estado, callable $obter, callable $definir, $registro)
    {
        $pontos = $obter('pontos') ?? [];
        $idsSelecionadas = collect($estado ?? [])
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values()
            ->all();

        $idsFromPontos = collect($pontos)
            ->filter(fn($p) => ($p['tipo'] ?? null) === 'escola' && !empty($p['id_escola']))
            ->pluck('id_escola')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        if ($registro && empty($idsSelecionadas) && !empty($idsFromPontos)) {
            $idsSelecionadas = $idsFromPontos;
            $definir('escola_id', $idsSelecionadas);
        }

        $pontos = array_values(array_filter($pontos, function ($p) use ($idsSelecionadas) {
            if (($p['tipo'] ?? '') !== 'escola') return true;
            return in_array((int) ($p['id_escola'] ?? 0), $idsSelecionadas, true);
        }));

        $presentes = [];
        foreach ($pontos as $p) {
            if (($p['tipo'] ?? '') === 'escola' && isset($p['id_escola'])) {
                $presentes[(int) $p['id_escola']] = true;
            }
        }
        $faltando = array_values(array_diff($idsSelecionadas, array_keys($presentes)));

        if (!empty($faltando)) {
            $escolas = \App\Models\Escola::whereIn('id', $faltando)->get(['id', 'nome', 'latitude', 'longitude']);
            foreach ($escolas as $esc) {
                if ($esc->latitude !== null && $esc->longitude !== null) {
                    $pontos[] = [
                        'latitude'   => (float) $esc->latitude,
                        'longitude'  => (float) $esc->longitude,
                        'ordem'      => 0,
                        'tipo'       => 'escola',
                        'id_escola'  => (int) $esc->id,
                        'rotulo'     => 'Escola ' . $esc->nome,
                    ];
                }
            }
        }

        foreach ($pontos as $i => &$p) $p['ordem'] = $i + 1;
        unset($p);

        $this->forceUpdatePontos($definir, $pontos, null);

        request()->session();
        if (function_exists('filament')) {
            filament()->getCurrentPanel();
        }
        return $pontos;
    }

    //* Verifica se a rota tem paradas ordenadas.
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

    // Recalcula o valor total da rota.
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

    // Processa rota recebida do JS do mapa.
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

    // Cria pontos de rota, se houver, e sincroniza com escolas.
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

    // Zera estado quando nenhuma rota tem pontos e reza o valor total.
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

    // Muda o estado do formulário antes de salvar uma rota editada.
    public function mudarEstadoFormAntesDeSalvarEdit(array $data, array $state): array
    {
        $this->validarComposicaoMinimaOuFalhar($state);

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

    // Formata pontos.
    private function parseNumber(mixed $value): float
    {
        if (is_null($value) || $value === '') return 0.0;
        if (is_float($value) || is_int($value)) return (float) $value;

        $s = (string) $value;
        $s = str_replace(["\u{00A0}", ' '], '', $s);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }
}
