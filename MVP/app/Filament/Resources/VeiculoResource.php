<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VeiculoResource\Pages;
use App\Filament\Resources\VeiculoResource\RelationManagers;
use App\Models\Veiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VeiculoResource extends Resource
{
    protected static ?string $model = Veiculo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome do Veiculo')
                    ->required(),

                Forms\Components\TextInput::make('placa')
                    ->label('Placa do Veículo')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rule('regex:/^[A-Z]{3}-?[A-Z0-9]{4}$/')
                    ->helperText('Formato válido: ABC-1234 ou ABC1D23 (Mercosul).')
                    ->validationMessages([
                        'regex' => 'A placa deve estar no formato ABC-1234 ou ABC1D23.',
                    ])
                    ->placeholder('Ex.: ABC-1234 ou ABC1D23')
                    ->afterStateUpdated(fn($state, callable $set) => $set('placa', strtoupper($state))),


                Forms\Components\TextInput::make('assentos')
                    ->label('Quantidade de Assentos')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1),

                Forms\Components\TextInput::make('ano')
                    ->label('Ano do Veiculo')
                    ->required()
                    ->numeric()
                    ->length(4)
                    ->maxValue(date('Y'))
                    ->minValue(1900)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome do Veículo')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('placa')
                    ->label('Placa')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('assentos')
                    ->label('Assentos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ano')
                    ->label('Ano')
                    ->sortable(),

            ])

            ->filters([
                Tables\Filters\Filter::make('assentos')
                    ->form([

                        Forms\Components\TextInput::make('assentos_min')
                            ->label('Assentos Mínimos')
                            ->numeric(),

                        Forms\Components\TextInput::make('assentos_max')
                            ->label('Assentos Máximos')
                            ->numeric(),

                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['assentos_min'],
                                fn($q) => $q->where(
                                    'assentos',
                                    '>=',
                                    $data['assentos_min']
                                )
                            )

                            ->when(
                                $data['assentos_max'],
                                fn($q) => $q->where(
                                    'assentos',
                                    '<=',
                                    $data['assentos_max']
                                )
                            );
                    }),

                Tables\Filters\Filter::make('ano')
                    ->form([
                        Forms\Components\TextInput::make('ano_min')
                            ->label('Ano Mínimo')->numeric(),
                        Forms\Components\TextInput::make('ano_max')
                            ->label('Ano Máximo')->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['ano_min'], fn($q) => $q->where('ano', '>=', $data['ano_min']))
                            ->when($data['ano_max'], fn($q) => $q->where('ano', '<=', $data['ano_max']));
                    }),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVeiculos::route('/'),
        ];
    }
}