<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EscolaResource\Pages;
use App\Models\Escola;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;

class EscolaResource extends Resource
{
    protected static ?string $model = Escola::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Escolas';

    protected static ?string $pluralModelLabel = 'Escolas';

    protected static ?string $modelLabel = 'Escola';

    protected static ?string $navigationGroup = 'Gerenciamento Escolar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Fieldset::make('Informações de Endereço')
                    ->schema([
                        Forms\Components\Grid::make(8)
                            ->schema([
                                Forms\Components\TextInput::make('logradouro')
                                    ->label('Logradouro')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4)
                                    ->disabled(fn(callable $get) => blank($get('cep'))),

                                Forms\Components\TextInput::make('numero')
                                    ->label('Número')
                                    ->nullable()
                                    ->mask('999999')
                                    ->rule('regex:/^[0-9]{0,6}$/')
                                    ->maxLength(6)
                                    ->columnSpan(2),


                                Forms\Components\TextInput::make('cep')
                                    ->label('CEP')
                                    ->required()
                                    ->mask('99999-999')
                                    ->rule('regex:/^\d{5}-\d{3}$/')
                                    ->maxLength(9)
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $cep = preg_replace('/[^0-9]/', '', $state);
                                        if (strlen($cep) !== 8) {
                                            return;
                                        }

                                        try {
                                            $response = Http::get("https://viacep.com.br/ws/{$cep}/json/");
                                            if ($response->successful() && !$response->json('erro')) {
                                                $data = $response->json();
                                                $set('logradouro', $data['logradouro'] ?? '');
                                                $set('bairro', $data['bairro'] ?? '');
                                                $set('cidade', $data['localidade'] ?? '');
                                                $set('estado', $data['uf'] ?? '');
                                            }
                                        } catch (\Exception $e) {
                                            logger()->error("Erro ao consultar CEP: {$e->getMessage()}");
                                        }
                                    }),
                            ]),

                        Forms\Components\Grid::make(8)
                            ->schema([
                                Forms\Components\TextInput::make('bairro')
                                    ->label('Bairro')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3)
                                    ->disabled(fn(callable $get) => blank($get('cep'))),

                                Forms\Components\TextInput::make('cidade')
                                    ->label('Cidade')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3)
                                    ->disabled(fn(callable $get) => blank($get('cep'))),

                                Forms\Components\TextInput::make('estado')
                                    ->label('Estado')
                                    ->required()
                                    ->placeholder('ex.: PR, SP, RJ.')
                                    ->maxLength(2)
                                    ->columnSpan(2)
                                    ->disabled(fn(callable $get) => blank($get('cep'))),
                            ]),

                        Forms\Components\TextInput::make('complemento')
                            ->label('Complemento Endereço')
                            ->nullable()
                            ->placeholder('Ex.: Próximo ao Supermercado')
                            ->maxLength(100),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('logradouro')
                    ->label('Logradouro')
                    ->wrap()
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('bairro')
                    ->label('Bairro')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('UF')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cep')
                    ->label('CEP')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('numero')
                    ->label('Número')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('complemento')
                    ->label('Complemento')
                    ->wrap()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),


                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('viewTurmas')
                    ->label('Ver Turmas')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => route('filament.admin.resources.turmas.index', [
                        'tableFilters' => [
                            'id_escola' => [
                                'value' => $record->id,
                            ],
                        ],
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])

            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Exportar')
                    ->defaultFormat('xlsx')
                    ->directDownload()
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
            'index' => Pages\ManageEscolas::route('/'),
        ];
    }
}
