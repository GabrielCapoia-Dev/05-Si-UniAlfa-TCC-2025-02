<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RotaResource\Pages;
use App\Forms\Components\Mapa;
use App\Models\Rota;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use App\Forms\Components\OrdenarParadas;
use Filament\Tables\Table;
use App\Models\Escola;
use App\Models\PontosDeParada;

class RotaResource extends Resource
{
    protected static ?string $model = Rota::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Rotas';
    protected static ?string $pluralModelLabel = 'Rotas';
    protected static ?string $modelLabel = 'Rota';
    protected static ?string $navigationGroup = 'Gerenciamento Escolar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([

                        Mapa::make('pontos')
                            ->label('Mapa da Rota')
                            ->rotaAtiva(true)
                            ->afterStateHydrated(function (Mapa $component, $state, $record) {
                                // Só em edição e se ainda não veio nada para 'pontos'
                                if (! $record || ! empty($state)) return;

                                $rows = PontosDeParada::with('escola:id,nome')
                                    ->where('id_rota', $record->id)
                                    ->orderBy('ordem')     // mantém a ordem salva!
                                    ->orderBy('id')
                                    ->get();

                                $pontos = $rows->map(function ($p) {
                                    return [
                                        'ordem'      => (int) $p->ordem,
                                        'latitude'   => (float) $p->latitude,
                                        'longitude'  => (float) $p->longitude,
                                        'tipo'       => $p->tipo,                  // 'ponto' | 'escola'
                                        'id_escola'  => $p->id_escola,
                                        'rotulo'     => $p->tipo === 'escola'
                                            ? ('Escola ' . optional($p->escola)->nome)
                                            : null,
                                        'raio'       => null,                      // ajuste se tiver coluna
                                    ];
                                })->values()->all();

                                $component->state($pontos);
                            })
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
                                    ->afterStateHydrated(function ($state, callable $get, callable $set, $record) {
                                        if ($record && ! empty($get('pontos'))) return;
                                        $pontos = $get('pontos') ?? [];
                                        $idsSelecionados = collect($state ?? [])->map(fn($v) => (int)$v)->all();

                                        if (empty($idsSelecionados)) return;

                                        $existentes = [];
                                        foreach ($pontos as $i => $p) {
                                            if (($p['tipo'] ?? null) === 'escola' && isset($p['id_escola'])) {
                                                $existentes[(int)$p['id_escola']] = true;
                                            }
                                        }

                                        $faltando = array_values(array_diff($idsSelecionados, array_keys($existentes)));
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
                                            foreach ($pontos as $i => &$p) $p['ordem'] = $i + 1;
                                            unset($p);
                                            $set('pontos', $pontos);
                                        }
                                    })
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
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->modifyQueryUsing(function ($query) {
                $query
                    ->withCount([
                        'pontosDeParada',
                        'escolas',
                        'pontosDeParada as pontos_count' => fn($q) => $q->where('tipo', 'ponto'),
                        'pontosDeParada as pontos_escola_count' => fn($q) => $q->where('tipo', 'escola'),
                    ]);
            })
            ->columns([
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
            ])
            ->filters([
                // ex.: filtrar por turno
                Tables\Filters\SelectFilter::make('turno')
                    ->options([
                        'Manhã' => 'Manhã',
                        'Tarde' => 'Tarde',
                        'Noite' => 'Noite',
                        'Integral' => 'Integral',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('visualizar')
                    ->label('Ver Detalhes')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->action(function (\App\Models\Rota $record, $livewire) {
                        $livewire->dispatch('abrirDetalhesRota', $record->id);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRotas::route('/'),
            'create' => Pages\CreateRota::route('/create'),
            'edit' => Pages\EditRota::route('/{record}/edit'),
        ];
    }
}
