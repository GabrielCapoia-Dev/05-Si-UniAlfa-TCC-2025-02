<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlunoResource\Pages;
use App\Models\Aluno;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;


class AlunoResource extends Resource
{
    protected static ?string $model = Aluno::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Alunos';

    protected static ?string $pluralModelLabel = 'Alunos';

    protected static ?string $modelLabel = 'Aluno';

    protected static ?string $navigationGroup = 'Gerenciamento Escolar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(5)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->columnSpan(2)
                            ->label('Nome:'),
                        Forms\Components\DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento:'),
                        Forms\Components\TextInput::make('cgm')
                            ->label('cgm:'),
                        Forms\Components\Select::make('sexo')
                            ->label('Sexo:')
                            ->placeholder('Selecione')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ]),
                    ]),
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Fieldset::make('Foto')
                                    ->schema([
                                        Forms\Components\FileUpload::make('foto')
                                            ->label('Upload:')
                                            ->image()
                                            ->loadingIndicatorPosition('left')
                                            ->panelAspectRatio('1.53:1')    
                                            ->panelLayout('integrated')
                                            ->removeUploadedFileButtonPosition('right')
                                            ->uploadButtonPosition('left')
                                            ->uploadProgressIndicatorPosition('left')
                                            ->imageEditorAspectRatios([
                                                '4:3',
                                            ])
                                            ->columnSpan(4),
                                    ]),
                                Forms\Components\Fieldset::make('Rotas')
                                    ->schema([
                                        Forms\Components\Select::make('rota')
                                            ->label('Rotas:')
                                            ->placeholder('Selecione')
                                            ->columnSpan(4)
                                            ->options([
                                                'teste' => 'Rota 1',
                                                'teste' => 'Rota 2',
                                                'teste' => 'Rota 3',
                                                'teste' => 'Rota 4',
                                                'teste' => 'Rota 5',
                                                'teste' => 'Rota 6',
                                            ]),
                                        Forms\Components\Select::make('ponto')
                                            ->label('Ponto de Parada:')
                                            ->placeholder('Selecione')
                                            ->columnSpan(4)

                                            ->options([
                                                'teste' => 'Ponto 1',
                                                'teste' => 'Ponto 2',
                                                'teste' => 'Ponto 3',
                                                'teste' => 'Ponto 4',
                                                'teste' => 'Ponto 5',
                                                'teste' => 'Ponto 6',
                                            ]),
                                    ])
                            ])
                            ->columnSpan(4),
                        Forms\Components\Grid::make(8)
                            ->schema([
                                Forms\Components\Fieldset::make('Informações pessoais')
                                    ->schema([

                                        Forms\Components\TextInput::make('nome_responsavel')
                                            ->label('Nome Responsalvel:'),
                                        Forms\Components\TextInput::make('telefone_responsavel')
                                            ->label('Telefone Responsalvel:'),
                                        Forms\Components\TextInput::make('telefone_aluno')
                                            ->label('Telefone Aluno:'),
                                        Forms\Components\TextInput::make('telefone_alternativo')
                                            ->label('Telefone:'),
                                    ]),

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


                            ])->columnSpan(8),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_nascimento')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cgm')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sexo'),
                Tables\Columns\TextColumn::make('nome_responsavel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefone_responsavel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefone_aluno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefone_alternativo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('raio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('logradouro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bairro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cep')
                    ->searchable(),
                Tables\Columns\TextColumn::make('numero')
                    ->searchable(),
                Tables\Columns\TextColumn::make('complemento')
                    ->searchable(),
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
            'index' => Pages\ListAlunos::route('/'),
            'create' => Pages\CreateAluno::route('/create'),
            'edit' => Pages\EditAluno::route('/{record}/edit'),
        ];
    }
}