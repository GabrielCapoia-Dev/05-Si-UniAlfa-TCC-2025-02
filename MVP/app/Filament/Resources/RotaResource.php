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


class RotaResource extends Resource
{
    protected static ?string $model = Rota::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Grid::make(12)
                ->schema([

                    // MAPA: rota ativa
                    Mapa::make('pontos')
                        ->label('Mapa da Rota')
                        ->rotaAtiva(true)
                        ->columnSpan(7),

                    Grid::make(12)
                        ->schema([

                            Forms\Components\TextInput::make('nome')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(12),

                            // Seleção de escolas
                            Forms\Components\Select::make('escola_id')
                                ->label('Escolas')
                                ->relationship('escolas', 'nome')
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->columnSpan(12)
                                ->afterStateHydrated(function ($state, callable $get, callable $set) {
                                    // Ao carregar o registro, garante que escolas selecionadas apareçam como paradas (se ainda não estiverem)
                                    $pontos = $get('pontos') ?? [];
                                    $idsSelecionados = collect($state ?? [])->map(fn($v) => (int)$v)->all();

                                    if (empty($idsSelecionados)) return;

                                    // indexar escolas já presentes no array de pontos
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
                                                    'ordem'      => 0, // ajustar depois
                                                    'tipo'       => 'escola',
                                                    'id_escola'  => (int)$esc->id,
                                                    'rotulo'     => 'Escola ' . $esc->nome,
                                                ];
                                            }
                                        }
                                        // reindex ordens
                                        foreach ($pontos as $i => &$p) $p['ordem'] = $i + 1;
                                        unset($p);
                                        $set('pontos', $pontos);
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    // Quando muda a seleção, sincroniza os pontos do tipo 'escola'
                                    $pontos = $get('pontos') ?? [];
                                    $idsSelecionados = collect($state ?? [])->map(fn($v) => (int)$v)->all();

                                    // Remove escolas não selecionadas
                                    $pontos = array_values(array_filter($pontos, function ($p) use ($idsSelecionados) {
                                        if (($p['tipo'] ?? '') !== 'escola') return true;
                                        return in_array((int)($p['id_escola'] ?? 0), $idsSelecionados, true);
                                    }));

                                    // Adiciona as que faltam
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

                                    // Reindex ordens
                                    foreach ($pontos as $i => &$p) $p['ordem'] = $i + 1;
                                    unset($p);

                                    $set('pontos', $pontos);
                                }),

                            // Card para reordenar (usa o MESMO estado 'pontos')
                            OrdenarParadas::make('pontos')
                                ->label('Ordenar Paradas')
                                ->columnSpan(12),

                            // Turno etc (o que já tinha)
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
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('turno'),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
