<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlunoResource\Pages;
use App\Filament\Resources\AlunoResource\Pages\ListAlunos;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Serie;
use App\Models\Escola;
use App\Models\Rota;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                // ======= DADOS PRINCIPAIS =======
                Forms\Components\Grid::make(5)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome:')
                            ->columnSpan(2)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento:')
                            ->required(),

                        Forms\Components\TextInput::make('cgm')
                            ->label('CGM:')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        Forms\Components\Select::make('sexo')
                            ->label('Sexo:')
                            ->placeholder('Selecione')
                            ->required()
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ]),
                    ]),

                // ======= FOTO E ROTAS =======
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\FileUpload::make('foto')
                                    ->label('Enviar Foto:')
                                    ->image()
                                    ->directory('alunos')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->panelAspectRatio('1:1')
                                    ->columnSpan(3)
                                    ->getUploadedFileNameForStorageUsing(function ($file, $get) {
                                        $cgm = $get('cgm') ?? 'sem-cgm';
                                        $extension = strtolower($file->getClientOriginalExtension());
                                        return "{$cgm}.{$extension}";
                                    }),

                                Forms\Components\Fieldset::make('Rotas')
                                    ->schema([
                                        Forms\Components\Select::make('id_rota')
                                            ->label('Rota de transporte')
                                            ->options(function (Forms\Get $get) {
                                                $turmaId = $get('id_turma');
                                                if (!$turmaId) return [];

                                                $escolaId = Turma::whereKey($turmaId)->value('id_escola');
                                                if (!$escolaId) return [];

                                                return Rota::query()
                                                    ->whereHas('escolas', fn($q) => $q->where('escolas.id', $escolaId))
                                                    ->orderBy('nome')
                                                    ->pluck('nome', 'id');
                                            })
                                            ->disabled(fn(Forms\Get $get) => blank($get('id_turma')))
                                            ->visible(fn() => Auth::user()?->is_admin ?? false)
                                            ->helperText('Lista apenas rotas vinculadas à escola da turma selecionada.')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->visible(fn() => Auth::user()?->is_admin ?? false),
                            ])
                            ->columnSpan(3),

                        // ======= CONTATO, ENDEREÇO E ESCOLA =======
                        Forms\Components\Grid::make(9)
                            ->schema([

                                // ======= CONTATOS =======
                                Forms\Components\Fieldset::make('Informações para Contato')
                                    ->schema([
                                        Forms\Components\TextInput::make('nome_responsavel')
                                            ->label('Nome do Responsável:')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('telefone_responsavel')
                                            ->label('Telefone do Responsável:')
                                            ->required()
                                            ->numeric()
                                            ->maxLength(11),

                                        Forms\Components\TextInput::make('telefone_aluno')
                                            ->label('Telefone do Aluno:')
                                            ->nullable()
                                            ->numeric()
                                            ->maxLength(11),

                                        Forms\Components\TextInput::make('telefone_alternativo')
                                            ->label('Telefone Alternativo:')
                                            ->nullable()
                                            ->numeric()
                                            ->maxLength(11),
                                    ]),

                                // ======= ENDEREÇO =======
                                Forms\Components\Fieldset::make('Endereço')
                                    ->schema([
                                        Forms\Components\Grid::make(9)
                                            ->schema([
                                                Forms\Components\TextInput::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->maxLength(255)
                                                    ->columnSpan(5)
                                                    ->disabled(fn(Forms\Get $get) => blank($get('cep')))
                                                    ->required(),

                                                Forms\Components\TextInput::make('numero')
                                                    ->label('Número')
                                                    ->maxLength(6)
                                                    ->nullable()
                                                    ->mask('999999'),

                                                Forms\Components\TextInput::make('cep')
                                                    ->label('CEP')
                                                    ->mask('99999-999')
                                                    ->rules(['regex:/^\d{5}-\d{3}$/'])
                                                    ->validationMessages([
                                                        'regex' => 'O CEP deve estar no formato 00000-000',
                                                    ])
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $cep = preg_replace('/[^0-9]/', '', $state);
                                                        if (strlen($cep) !== 8) return;

                                                        try {
                                                            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
                                                            if ($response->successful() && !$response->json('erro')) {
                                                                $data = $response->json();
                                                                $set('logradouro', $data['logradouro'] ?? '');
                                                                $set('bairro', $data['bairro'] ?? '');
                                                                $set('cidade', $data['localidade'] ?? '');
                                                                $set('estado', $data['uf'] ?? '');
                                                            }
                                                        } catch (\Exception $e) {
                                                            Log::error("Erro ao consultar CEP: {$e->getMessage()}");
                                                        }
                                                    })
                                                    ->columnSpan(2),
                                            ]),

                                        Forms\Components\Grid::make(8)
                                            ->schema([
                                                Forms\Components\TextInput::make('bairro')
                                                    ->label('Bairro')
                                                    ->maxLength(255)
                                                    ->required(),

                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
                                                    ->maxLength(255)
                                                    ->required(),

                                                Forms\Components\TextInput::make('estado')
                                                    ->label('UF')
                                                    ->maxLength(2)
                                                    ->placeholder('PR, SP, RJ...')
                                                    ->required(),

                                                Forms\Components\TextInput::make('complemento')
                                                    ->label('Complemento')
                                                    ->maxLength(100)
                                                    ->nullable()
                                                    ->placeholder('Ex.: Próximo ao Supermercado'),
                                            ]),
                                    ]),

                                // ======= ESCOLA =======
                                Forms\Components\Fieldset::make('Informações da Escola')
                                    ->schema([
                                        Forms\Components\Select::make('id_escola')
                                            ->label('Escola')
                                            ->options(fn() => Escola::pluck('nome', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->default(fn() => Auth::user()?->id_escola)
                                            ->required()
                                            ->reactive()
                                            ->disabled(fn() => !(Auth::user()?->is_admin ?? false))
                                            ->afterStateUpdated(fn($state, callable $set) => $set('id_turma', null)),

                                        Forms\Components\Select::make('id_turma')
                                            ->label('Turma')
                                            ->options(function (Forms\Get $get, $record) {
                                                $escolaId = $get('id_escola') ?? $record?->turma?->id_escola;
                                                if (!$escolaId) return [];

                                                return Turma::with('serie')->get()
                                                    ->filter(fn($t) => $t->serie)
                                                    ->mapWithKeys(fn($turma) => [
                                                        $turma->id => "{$turma->serie->nome} - {$turma->turma}",
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->disabled(fn(Forms\Get $get) => blank($get('id_escola')))
                                            ->reactive()
                                            ->placeholder('Selecione a escola primeiro'),
                                    ]),
                            ])
                            ->columnSpan(9),
                    ]),
            ]);
    }

    // ================== TABELA ==================
    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('nome')->label('Nome')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('turma.escola.nome')->label('Escola')->sortable()->formatStateUsing(
                    fn($record) => optional($record->turma?->escola)->nome ?? '-'
                ),
                Tables\Columns\TextColumn::make('turma.turma')->label('Turma')->formatStateUsing(
                    fn($record) => optional($record->turma?->serie)->nome 
                        ? optional($record->turma?->serie)->nome . ' - ' . ($record->turma?->turma ?? '-') 
                        : '-'
                ),
                Tables\Columns\TextColumn::make('cgm')->label('CGM')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('telefone_responsavel')->label('Telefone Resp.'),
                Tables\Columns\TextColumn::make('telefone_aluno')->label('Telefone Aluno'),
                Tables\Columns\TextColumn::make('cidade')->label('Cidade'),
                Tables\Columns\TextColumn::make('estado')->label('UF'),
            ])
            ->filters([
                SelectFilter::make('id_escola')->label('Escola')->relationship('turma.escola', 'nome')->searchable(),
                SelectFilter::make('id_serie')
                    ->label('Série')
                    ->options(Serie::pluck('nome', 'id'))
                    ->query(fn(Builder $query, array $data) =>
                        !empty($data['value'])
                            ? $query->whereHas('turma', fn($q) => $q->where('id_serie', $data['value']))
                            : null
                    ),
                SelectFilter::make('id_turma')
                    ->label('Turma')
                    ->options(
                        Turma::with('serie')->get()->filter(fn($t) => $t->serie)->mapWithKeys(fn($turma) => [
                            $turma->id => "{$turma->serie->nome} - {$turma->turma}",
                        ])
                    )
                    ->query(fn(Builder $query, array $data) =>
                        !empty($data['value']) ? $query->where('id_turma', $data['value']) : null
                    ),
                Filter::make('ano_nascimento')
                    ->form([
                        Forms\Components\TextInput::make('ano')
                            ->label('Ano de Nascimento')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(now()->year),
                    ])
                    ->query(fn(Builder $query, array $data) =>
                        filled($data['ano'])
                            ? $query->whereYear('data_nascimento', $data['ano'])
                            : null
                    ),
                SelectFilter::make('bairro')
                    ->label('Bairro')
                    ->options(fn() => Aluno::select('bairro')->distinct()->pluck('bairro', 'bairro')->filter()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('nome')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
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
