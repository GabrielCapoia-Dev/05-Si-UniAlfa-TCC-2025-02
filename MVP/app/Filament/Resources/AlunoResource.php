<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlunoResource\Pages;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Serie;
use App\Models\Escola;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Filament\Resources\AlunoResource\Pages\ListAlunos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                            ->unique(ignoreRecord: true)
                            ->required(fn(string $context): bool => $context === 'create')
                            ->label('Nome:'),
                        Forms\Components\DatePicker::make('data_nascimento')
                            ->required(fn(string $context): bool => $context === 'create')
                            ->label('Data de Nascimento:'),
                        Forms\Components\TextInput::make('cgm')
                            ->required(fn(string $context): bool => $context === 'create')
                            ->unique(ignoreRecord: true)
                            ->label('cgm:'),
                        Forms\Components\Select::make('sexo')
                            ->required(fn(string $context): bool => $context === 'create')
                            ->label('Sexo:')
                            ->placeholder('Selecione')
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ]),
                    ]),
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\FileUpload::make('foto')
                                    ->label('Enviar Foto:')
                                    ->image()
                                    ->placeholder('Clique ou arraste uma imagem')
                                    ->directory('alunos')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->panelAspectRatio('1:1')
                                    ->columnSpan(3)
                                    ->getUploadedFileNameForStorageUsing(function ($file, $get) {
                                        $cgm = $get('cgm') ?? 'sem-cgm';
                                        $extension = $file->getClientOriginalExtension();
                                        return $cgm . '.' . strtolower($extension);
                                    }),
                                Forms\Components\Fieldset::make('Rotas')
                                    ->schema([
                                        Forms\Components\Select::make('rota')
                                            ->label('Rotas:')
                                            ->placeholder('Selecione')
                                            ->columnSpan(3)
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
                                            ->columnSpan(3)

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
                            ->columnSpan(3),
                        Forms\Components\Grid::make(9)
                            ->schema([
                                Forms\Components\Fieldset::make('Informações para Contato')
                                    ->schema([

                                        Forms\Components\TextInput::make('nome_responsavel')
                                            ->required(fn(string $context): bool => $context === 'create')
                                            ->label('Nome Responsalvel:'),
                                        Forms\Components\TextInput::make('telefone_responsavel')
                                            ->required(fn(string $context): bool => $context === 'create')
                                            ->label('Telefone Responsalvel:'),
                                        Forms\Components\TextInput::make('telefone_aluno')
                                            ->label('Telefone Aluno:'),
                                        Forms\Components\TextInput::make('telefone_alternativo')
                                            ->label('Telefone:'),
                                    ]),

                                Forms\Components\Fieldset::make('Informações de Endereço')
                                    ->schema([
                                        Forms\Components\Grid::make(9)
                                            ->schema([
                                                Forms\Components\TextInput::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->required(fn(string $context): bool => $context === 'create')
                                                    ->maxLength(255)
                                                    ->columnSpan(5)
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
                                                    ->required(fn(string $context): bool => $context === 'create')
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
                                                    ->required(fn(string $context): bool => $context === 'create')

                                                    ->maxLength(255)
                                                    ->columnSpan(3)
                                                    ->disabled(fn(callable $get) => blank($get('cep'))),

                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
                                                    ->required(fn(string $context): bool => $context === 'create')

                                                    ->maxLength(255)
                                                    ->columnSpan(2)
                                                    ->disabled(fn(callable $get) => blank($get('cep'))),

                                                Forms\Components\TextInput::make('estado')
                                                    ->label('Estado')
                                                    ->required(fn(string $context): bool => $context === 'create')

                                                    ->placeholder('ex.: PR, SP, RJ.')
                                                    ->maxLength(2)
                                                    ->columnSpan(1)
                                                    ->disabled(fn(callable $get) => blank($get('cep'))),

                                                Forms\Components\TextInput::make('complemento')
                                                    ->label('Complemento')
                                                    ->nullable()
                                                    ->columnSpan(2)
                                                    ->placeholder('Ex.: Próximo ao Supermercado')
                                                    ->maxLength(100),
                                            ]),

                                    ]),


                                Forms\Components\Grid::make(9)
                                    ->schema([
                                        Forms\Components\Fieldset::make('Informações da Escola')
                                            ->schema([
                                                Forms\Components\Select::make('id_escola')
                                                    ->label('Escola')
                                                    ->options(Escola::pluck('nome', 'id'))
                                                    ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                                        if ($record && $record->turma) {
                                                            $component->state($record->turma->id_escola);
                                                        }
                                                    })
                                                    ->searchable()
                                                    ->required()
                                                    ->reactive(),

                                                Forms\Components\Select::make('id_turma')
                                                    ->label('Turma')
                                                    ->options(function (callable $get, $record) {
                                                        $escolaId = $get('id_escola') ?? $record?->turma?->id_escola;

                                                        if (!$escolaId) {
                                                            return [];
                                                        }

                                                        return Turma::where('id_escola', $escolaId)
                                                            ->with('serie')
                                                            ->get()
                                                            ->mapWithKeys(fn($turma) => [
                                                                $turma->id => "{$turma->serie->nome} - {$turma->turma}"
                                                            ]);
                                                    })
                                                    ->default(fn($record) => $record?->id_turma)
                                                    ->searchable()
                                                    ->required()
                                                    ->disabled(
                                                        fn(callable $get, string $context) =>
                                                        $context === 'create' && blank($get('id_escola'))
                                                    )
                                                    ->placeholder('Selecione a escola primeiro'),
                                            ]),
                                    ])
                                    ->columnSpan(9),
                            ])->columnSpan(9),


                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma.escola.nome')
                    ->label('Escola')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('turma.turma')
                    ->label('Turma')
                    ->sortable()
                    ->formatStateUsing(
                        fn($record) =>
                        $record->turma && $record->turma->serie
                            ? "{$record->turma->serie->nome} - {$record->turma->turma}"
                            : '-'
                    )
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('data_nascimento')
                    ->label('Nascimento')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cgm')
                    ->label('CGM')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nome_responsavel')
                    ->label('Responsável')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefone_responsavel')
                    ->label('Tel. Responsável')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefone_aluno')
                    ->label('Tel. Aluno')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('telefone_alternativo')
                    ->label('Tel. Alternativo')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('logradouro')
                    ->label('Logradouro')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('numero')
                    ->label('Número')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bairro')
                    ->label('Bairro')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Cidade')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('estado')
                    ->label('UF')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cep')
                    ->label('CEP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('complemento')
                    ->label('Complemento')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                SelectFilter::make('id_escola')
                    ->label('Escola')
                    ->relationship('turma.escola', 'nome')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('id_serie')
                    ->label('Série')
                    ->options(Serie::pluck('nome', 'id'))
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('turma', fn($q) => $q->where('id_serie', $data['value']));
                        }
                    }),

                SelectFilter::make('id_turma')
                    ->label('Turma')
                    ->options(
                        Turma::with('serie')
                            ->get()
                            ->mapWithKeys(fn($turma) => [
                                $turma->id => "{$turma->serie->nome} - {$turma->turma}"
                            ])
                    )
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('id_turma', $data['value']);
                        }
                    }),


                Filter::make('ano_nascimento')
                    ->form([
                        Forms\Components\TextInput::make('ano')
                            ->label('Ano de Nascimento')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(now()->year)
                            ->placeholder('Ex: 2015'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['ano'])) {
                            $query->whereYear('data_nascimento', $data['ano']);
                        }
                    }),

                SelectFilter::make('bairro')
                    ->label('Bairro')
                    ->options(
                        fn() => Aluno::select('bairro')
                            ->distinct()
                            ->pluck('bairro', 'bairro')
                            ->filter()
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('visualizar')
                    ->label('Ver Detalhes')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->action(function (Aluno $record, $livewire) {
                        $livewire->dispatch('abrirDetalhesAluno', $record->id);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record, Tables\Actions\DeleteAction $action) {
                        // Pega a instância Livewire da página
                        $livewire = $action->getLivewire();

                        // Se for a ListAlunos e o card aberto for o mesmo aluno → fecha
                        if ($livewire instanceof ListAlunos) {
                            if ($livewire->alunoSelecionado && $livewire->alunoSelecionado->id === $record->id) {
                                $livewire->fecharDetalhesAluno();
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            $livewire = $action->getLivewire();

                            if ($livewire instanceof ListAlunos) {
                                $selectedId = $livewire->alunoSelecionado?->id;
                                if ($selectedId && $records->contains(fn($r) => $r->id === $selectedId)) {
                                    $livewire->fecharDetalhesAluno();
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('nome')
            ->striped()
            ->recordUrl(null)
            ->recordAction(function (Aluno $record, $livewire) {
                Tables\Actions\Action::make('selecionar')
                    ->label('Ver Detalhes')
                    ->icon('heroicon-o-eye')
                    ->action(
                        fn($record, $livewire) =>
                        $livewire->dispatch('mostrarAluno', id: $record->id)
                    );
            });
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
